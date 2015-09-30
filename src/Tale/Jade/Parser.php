<?php

namespace Tale\Jade;

class Parser
{

    private $_options;
    private $_lexer;


    private $_level;
    /** @var array */
    private $_parents;
    /** @var \Generator */
    private $_tokens;
    /** @var \Tale\Jade\Node */
    private $_document;
    private $_currentParent;
    private $_current;
    private $_last;


    public function __construct(array $options = null, Lexer $lexer = null)
    {

        $this->_options = array_replace([
            'lexer' => []
        ], $options ? $options : []);
        $this->_lexer = $lexer ? $lexer : new Lexer($this->_options['lexer']);
    }

    public function parse($input)
    {

        $this->_level = 0;
        $this->_nodes = [];
        $this->_parents = [];
        $this->_tokens = $this->_lexer->lex($input);
        $this->_document = $this->createNode('document');
        $this->_currentParent = $this->_document;
        $this->_current = null;
        $this->_last = null;

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
            var_dump('UNHANDLED '.$token['type']);
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

    protected function expect(array $types)
    {

        foreach ($this->lookUp($types) as $token) {

            return $token;
        }

        return null;
    }

    protected function expectEnd(array $relatedToken = null)
    {

        foreach ($this->lookUp(['newLine']) as $token) {

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

        var_dump("TOK", $this->_tokens->current());
        return $this->_tokens->current();
    }

    protected function createNode($name)
    {

        $node = new Node($name);
        return $node;
    }

    protected function createElement()
    {

        $node = $this->createNode('element');
        $node->tag = null;
        $node->attributes = [];
        $node->assignments = [];

        return $node;
    }

    protected function handleAssignment(array $token)
    {

        if (!$this->_current)
            $this->_current = $this->createElement();

        if ($this->_current->type !== 'element')
            $this->throwException(
                "Assignments can only happen on elements"
            );

        $node = $this->createNode('assignment');
        $node->name = $token['name'];
        $this->_current->children[] = $node;
        $this->_current->assignments[] = $node;

        if ($token = $this->expect(['attributeStart'])) {

            $element = $this->_current;
            $this->_current = $node;
            $this->handleToken($token);
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

        $node = $this->createNode('attribute');
        $node->name = $token['name'];
        $node->value = $token['value'];
        $node->escaped = $token['escaped'];

        $this->_current->attributes[] = $node;
    }

    protected function handleAttributeStart(array $token)
    {

        if (!$this->_current)
            $this->_current = $this->createElement();

        if (!in_array($this->_current->type, ['element', 'import', 'mixin', 'mixinCall']))
            $this->throwException(
                "Attributes can only be placed on element, import, mixin and mixin-calls"
            );

        foreach ($this->lookUp(['attribute']) as $subToken) {

            var_dump('ATTR', $subToken);
            $this->handleToken($subToken);
        }

        //Skip the attribute end!
        $this->nextToken();
        var_dump('CUR', $this->getToken());
    }

    protected function handleAttributeEnd(array $token)
    {

    }

    protected function handleBlock(array $token)
    {

        $node = $this->createNode('block');
        $node->name = $token['name'];
        $node->insertType = $token['insertType'];

        $this->_current = $node;

        $this->expectEnd($token);
    }

    protected function handleClass(array $token)
    {

        if (!$this->_current)
            $this->_current = $this->createElement();

        if (!in_array($this->_current->type, ['element', 'mixinCall']))
            $this->throwException("Classes can only be used on elements and mixin calls", $token);

        $attr = $this->createNode('attribute');
        $attr->name = 'class';
        $attr->value = $token['name'];
        $attr->escaped = false;
        $this->_current->attributes[] = $attr;
    }

    protected function handleComment(array $token)
    {

        $node = $this->createNode('comment');

        if ($token['rendered'])
            $node->rendered = true;

        $this->_current = $node;
    }

    protected function handleConditional(array $token)
    {

        $node = $this->createNode('conditional');
        $node->type = $token['type'];

        $this->_current = $node;
    }

    protected function handleExpression(array $token)
    {

        $node = $this->createNode('expression');
        $node->escaped = $token['escaped'];

        $this->_current = $node;
    }

    protected function handleId(array $token)
    {

        if (!$this->_current)
            $this->_current = $this->createElement();

        if (!in_array($this->_current->type, ['element', 'mixinCall']))
            $this->throwException("IDs can only be used on elements and mixin calls", $token);

        $attr = $this->createNode('attribute');
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

        $node = $this->createNode('import');
        $node->importType = $token['importType'];
        $node->path = $token['path'];
        $node->filter = $token['filter'];

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

        if (!isset($this->_parents[$this->_level]))
            $this->_parents[$this->_level] = [];

        $this->_parents[$this->_level][] = $this->_last;
        $this->_currentParent = $this->_last;
    }

    protected function handleTag(array $token)
    {

        if (!$this->_current)
            $this->_current = $this->createElement();

        if ($this->_current->type !== 'element')
            $this->throwException("Tags can only be used on elements", $token);

        $this->_current->tag = $token['name'];
    }

    protected function handleMixin(array $token)
    {

        $node = $this->createNode('mixin');
        $node->name = $token['name'];

        $this->_current = $node;
    }

    protected function handleMixinCall(array $token)
    {

        $node = $this->createNode('mixinCall');
        $node->name = $token['name'];

        $this->_current = $node;
    }

    protected function handleNewLine(array $token)
    {

        if ($this->_current) {

            $this->_currentParent->children[] = $this->_current;
            $this->_last = $this->_current;
            $this->_current = null;
        }
    }

    protected function handleOutdent(array $token)
    {

        $this->_level -= $token['levels'];

        return isset($this->_parents[$this->_level])
             ? end($this->_parents[$this->_level])
             : end(current($this->_parents));
    }


    protected function handleText(array $token)
    {

        $node = $this->createNode('text');
        $node->value = $token['value'];
        if ($this->_current)
            $this->_current->children[] = $node;
        else
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