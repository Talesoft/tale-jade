<?php

namespace Tale\Jade;

class Parser
{

    private $_options;
    private $_lexer;


    private $_level;
    private $_subLevel;
    /** @var \Generator */
    private $_tokens;
    /** @var \Tale\Jade\Node */
    private $_document;
    private $_currentParent;
    private $_current;
    private $_last;
    private $_inMixin;
    private $_mixinLevel;


    public function __construct(array $options = null, Lexer $lexer = null)
    {

        $this->_options = array_replace([
            'lexer' => []
        ], $options ? $options : []);
        $this->_lexer = $lexer ? $lexer : new Lexer($this->_options['lexer']);
    }

    /**
     * @return array
     */
    public function getOptions()
    {

        return $this->_options;
    }

    /**
     * @return \Tale\Jade\Lexer
     */
    public function getLexer()
    {

        return $this->_lexer;
    }

    public function parse($input)
    {

        $this->_level = 0;
        $this->_subLevel = 0;
        $this->_nodes = [];
        $this->_tokens = $this->_lexer->lex($input);
        $this->_document = $this->createNode('document', ['line' => 0, 'offset' => 0]);
        $this->_currentParent = $this->_document;
        $this->_current = null;
        $this->_last = null;
        $this->_inMixin = false;
        $this->_mixinLevel = null;

        while ($this->hasTokens()) {

            $this->handleToken();
            $this->nextToken();
        }

        return $this->_document;
    }

    protected function handleToken(array $token = null)
    {

        $token = $token ? $token : $this->getToken();

        $method = 'handle'.ucfirst($token['type']);

        if (!method_exists($this, $method))
            $this->throwException(
                "Unexpected token, no handler found",
                $token
            );
        else
            call_user_func([$this, $method], $token);
    }

    protected function lookUp(array $types)
    {

        while ($this->hasTokens()) {

            $token = $this->getToken();
            if (in_array($token['type'], $types, true))
                yield $token;
            else
                break;

            $this->nextToken();
        }
    }

    protected function lookUpNext(array $types)
    {

        return $this->nextToken()->lookUp($types);
    }

    protected function expect(array $types)
    {

        foreach ($this->lookUp($types) as $token) {

            return $token;
        }

        return null;
    }

    protected function expectNext(array $types)
    {

        return $this->nextToken()->expect($types);
    }

    protected function expectEnd(array $relatedToken = null)
    {

        foreach ($this->lookUpNext(['newLine']) as $token) {

            $this->handleToken($token);
            return;
        }

        $this->throwException(
            "The statement should end here.",
            $relatedToken
        );
    }

    protected function hasTokens()
    {

        return $this->_tokens->key() !== null;
    }

    protected function nextToken()
    {

        $this->_tokens->next();

        return $this;
    }

    protected function getToken()
    {

        return $this->_tokens->current();
    }

    protected function createNode($name, array $token = null)
    {

        $token = $token ? $token : ['line' => $this->_lexer->getLine(), 'offset' => $this->_lexer->getOffset()];
        $node = new Node($name, $token['line'], $token['offset']);
        return $node;
    }

    protected function createElement(array $token = null)
    {

        $node = $this->createNode('element', $token);
        $node->tag = null;
        $node->attributes = [];
        $node->assignments = [];

        return $node;
    }

    protected function handleAssignment(array $token)
    {

        if (!$this->_current)
            $this->_current = $this->createElement();

        if (!in_array($this->_current->type, ['element', 'mixinCall']))
            $this->throwException(
                "Assignments can only happen on elements and mixinCalls"
            );

        $node = $this->createNode('assignment', $token);
        $node->name = $token['name'];
        $this->_current->assignments[] = $node;

        if ($this->expectNext(['attributeStart'])) {

            $element = $this->_current;
            $this->_current = $node;
            $this->handleToken();
            $this->_current = $element;
        } else
            $this->throwException(
                "Assignments require a parameter block"
            );
    }

    protected function handleAttribute(array $token)
    {

        if (!$this->_current)
            $this->_current = $this->createElement();

        $node = $this->createNode('attribute', $token);
        $node->name = $token['name'];
        $node->value = $token['value'];
        $node->escaped = $token['escaped'];

        if (!$node->name && in_array($this->_current->type, ['element', 'mixin']))
            $this->throwException('Attributes in elements and mixins need a name', $token);

        if ($this->_current->type === 'mixinCall' && !$node->value) {

            $node->value = $node->name;
            $node->name = null;
        }

        $this->_current->attributes[] = $node;
    }

    protected function handleAttributeStart(array $token)
    {

        if (!$this->_current)
            $this->_current = $this->createElement();

        if (!in_array($this->_current->type, ['element', 'assignment', 'import', 'mixin', 'mixinCall']))
            $this->throwException(
                "Attributes can only be placed on element, assignment, import, mixin and mixinCall"
            );

        foreach ($this->lookUpNext(['attribute']) as $subToken) {

            $this->handleToken($subToken);
        }

        if (!$this->expect(['attributeEnd']))
            $this->throwException(
                "Attribute list not ended",
                $token
            );
    }

    protected function handleAttributeEnd(array $token)
    {

    }

    protected function handleBlock(array $token)
    {

        $node = $this->createNode('block', $token);
        $node->name = isset($token['name']) ? $token['name'] : null;
        $node->mode = isset($token['mode']) ? $token['mode'] : null;

        if (!$node->name && !$this->_inMixin)
            $this->throwException(
                "Blocks outside a mixin always need a name"
            );

        $this->_current = $node;

        $this->expectEnd($token);
    }

    protected function handleClass(array $token)
    {

        if (!$this->_current)
            $this->_current = $this->createElement();

        if (!in_array($this->_current->type, ['element', 'mixinCall']))
            $this->throwException("Classes can only be used on elements and mixin calls", $token);

        $attr = $this->createNode('attribute', $token);
        $attr->name = 'class';
        $attr->value = $token['name'];
        $attr->escaped = false;
        $this->_current->attributes[] = $attr;
    }

    protected function handleComment(array $token)
    {

        $node = $this->createNode('comment', $token);
        $node->rendered = $token['rendered'];

        $this->_current = $node;
    }

    protected function handleCase(array $token)
    {

        $node = $this->createNode('case', $token);
        $node->subject = $token['subject'];
        $this->_current = $node;
    }

    protected function handleConditional(array $token)
    {

        $node = $this->createNode('conditional', $token);
        $node->subject = $token['subject'];
        $node->conditionType = $token['conditionType'];

        $this->_current = $node;
    }

    protected function handleDo(array $token)
    {

        $node = $this->createNode('do', $token);
        $this->_current = $node;
    }

    protected function handleDoctype(array $token)
    {

        $node = $this->createNode('doctype', $token);
        $node->name = $token['name'];

        $this->_current = $node;
    }

    protected function handleEach(array $token)
    {

        $node = $this->createNode('each', $token);
        $node->subject = $token['subject'];
        $node->itemName = $token['itemName'];
        $node->keyName = isset($token['keyName']) ? $token['keyName'] : null;

        $this->_current = $node;
    }

    protected function handleExpression(array $token)
    {

        $node = $this->createNode('expression', $token);
        $node->escaped = $token['escaped'];
        $node->return = $token['return'];

        if ($this->_current) {

            if (!in_array($this->_current->type, ['element']))
                $this->throwException(
                    "Only elements can have expressions appended",
                    $token
                );

            $this->_current->append($node);

            if ($this->expectNext(['text'])) {

                $old = $this->_current;
                $this->_current = $node;
                $this->handleToken();
                $this->_current = $old;
            }

        } else
            $this->_current = $node;
    }

    protected function handleFilter(array $token)
    {

        $node = $this->createNode('filter', $token);
        $node->name = $token['name'];
        $this->_current = $node;
    }

    protected function handleId(array $token)
    {

        if (!$this->_current)
            $this->_current = $this->createElement();

        if (!in_array($this->_current->type, ['element', 'mixinCall']))
            $this->throwException("IDs can only be used on elements and mixin calls", $token);

        $attr = $this->createNode('attribute', $token);
        $attr->name = 'id';
        $attr->value = $token['name'];
        $attr->escaped = false;
        $this->_current->attributes[] = $attr;
    }

    protected function handleImport(array $token)
    {

        //Did you notice that "include" and "extend" are actually the same thing?
        //The only difference (for the parser) is, that extend will probably
        //be at indent 0 and PROBABLY the first instruction at all

        if ($this->_current)
            $this->throwException(
                "extend/include should be the very first statement on a line",
                $token
            );

        $node = $this->createNode('import', $token);
        $node->importType = $token['importType'];
        $node->path = $token['path'];
        $node->filter = $token['filter'];
        $node->attributes = [];
        $node->assignments = [];

        $this->_current = $node;
    }

    protected function handleIndent(array $token)
    {

        $this->_level += $token['levels'];

        if (!$this->_last)
            return;

        if (in_array($this->_last->type, ['import']))
            $this->throwException(
                'The instruction can\'t have children',
                $token
            );

        $this->_currentParent = $this->_last;
    }

    protected function handleTag(array $token)
    {

        if (!$this->_current)
            $this->_current = $this->createElement();

        if ($this->_current->type !== 'element')
            $this->throwException("Tags can only be used on elements", $token);

        if ($this->_current->tag)
            $this->throwException('This element already has a tag name', $token);

        $this->_current->tag = $token['name'];
    }

    protected function handleMixin(array $token)
    {

        if ($this->_inMixin)
            $this->throwException(
                "Failed to define mixin: Mixins cant be nested"
            );

        $node = $this->createNode('mixin', $token);
        $node->name = $token['name'];
        $node->attributes = [];
        $node->assignments = [];

        $this->_inMixin = true;
        $this->_mixinLevel = $this->_level;

        $this->_current = $node;
    }

    protected function handleMixinCall(array $token)
    {

        $node = $this->createNode('mixinCall', $token);
        $node->name = $token['name'];
        $node->attributes = [];
        $node->assignments = [];

        $this->_current = $node;
    }

    protected function handleNewLine(array $token = null)
    {

        if ($this->_current) {

            $this->_currentParent->append($this->_current);
            $this->_last = $this->_current;
            $this->_current = null;
        }

        if ($this->_subLevel > 0)
            $this->handleOutdent(['levels' => 0]);
    }

    protected function handleOutdent(array $token)
    {

        $levels = $token['levels'] + $this->_subLevel;
        $this->_level -= $levels;
        $this->_subLevel = 0;

        if ($this->_inMixin && $this->_level <= $this->_mixinLevel) {

            $this->_inMixin = false;
            $this->_mixinLevel = null;
        }

        while ($levels-- > 0)
            $this->_currentParent = $this->_currentParent->parent;
    }

    protected function handleSub(array $token)
    {

        if (!$this->_current)
            $this->throwException(
                "Sub accesssor needs an element to work on",
                $token
            );

        if ($this->_current->type === 'element' && !$token['withSpace'] && $this->expectNext(['tag'])) {

            $token = $this->getToken();
            $this->_current->tag .= ':'.$token['name'];
            return;
        }

        //We just fake some tokens
        $this->handleNewLine(); //Stores the element in the current _parentNode
        $this->handleIndent(['levels' => 1]); //Sets the left element as _parentNode
        $this->_subLevel++;
    }


    protected function handleText(array $token)
    {

        $node = $this->createNode('text', $token);
        $node->value = $token['value'];
        if ($this->_current) {

            $this->_current->append($node);
        } else
            $this->_current = $node;
    }

    protected function handleWhen(array $token)
    {

        $node = $this->createNode('when', $token);
        $node->subject = $token['subject'];
        $node->default = $token['default'];
        $this->_current = $node;
    }

    protected function handleWhile(array $token)
    {

        $node = $this->createNode('while', $token);
        $node->subject = $token['subject'];
        $this->_current = $node;
    }


    protected function throwException($message, array $relatedToken = null)
    {

        if ($relatedToken)
            $message .= "\n(".$relatedToken['type']
                        .' at '.$relatedToken['line']
                        .':'.$relatedToken['offset'].')';

        throw new ParseException(
            "Failed to parse Jade: $message"
        );
    }
}