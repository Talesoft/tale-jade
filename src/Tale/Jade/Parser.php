<?php

namespace Tale\Jade;

use Tale\Jade\Lexer\Token\AssignmentToken;
use Tale\Jade\Lexer\Token\AttributeEndToken;
use Tale\Jade\Lexer\Token\AttributeStartToken;
use Tale\Jade\Lexer\Token\AttributeToken;
use Tale\Jade\Lexer\Token\BlockToken;
use Tale\Jade\Lexer\Token\ClassToken;
use Tale\Jade\Lexer\Token\CommentToken;
use Tale\Jade\Lexer\Token\ConditionalToken;
use Tale\Jade\Lexer\Token\ExpressionToken;
use Tale\Jade\Lexer\Token\IdToken;
use Tale\Jade\Lexer\Token\ImportToken;
use Tale\Jade\Lexer\Token\IndentToken;
use Tale\Jade\Lexer\Token\MixinCallToken;
use Tale\Jade\Lexer\Token\MixinToken;
use Tale\Jade\Lexer\Token\NewLineToken;
use Tale\Jade\Lexer\Token\OutdentToken;
use Tale\Jade\Lexer\Token\TagToken;
use Tale\Jade\Lexer\Token\TextToken;
use Tale\Jade\Lexer\TokenBase;
use Tale\Jade\Parser\Exception;
use Tale\Jade\Parser\Node\AttributeNode;
use Tale\Jade\Parser\Node\DocumentNode;
use Tale\Jade\Parser\Node\ElementNode;
use Tale\Jade\Parser\Node\ExpressionNode;
use Tale\Jade\Parser\Node\MixinCallNode;
use Tale\Jade\Parser\Node\MixinNode;
use Tale\Jade\Parser\Node\TextNode;
use Tale\Jade\Parser\NodeBase;
use Tale\Util\PathUtil;

class Parser
{

    private $_options;
    private $_lexer;

    private $_level;
    private $_levelNodes;
    /** @var \Tale\Jade\Parser\NodeBase[] */
    private $_nodes;
    /** @var \Tale\Jade\Parser\BlockNode[] */
    private $_blocks;
    /** @var \Tale\Jade\Parser\MixinNode[] */
    private $_mixins;
    /** @var \Tale\Jade\Parser\NodeBase */
    private $_currentNode;
    /** @var \Tale\Jade\Parser\NodeBase */
    private $_lastNode;
    /** @var \Tale\Jade\Parser\NodeBase */
    private $_newNode;


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
        $this->_levelNodes = [];
        $this->_nodes = [];
        $this->_blocks = [];
        $this->_mixins = [];
        $this->_currentNode = new DocumentNode();
        $this->_lastNode = null;
        $this->_newNode = null;

        foreach ($this->_lexer->lex($input) as $token) {

            $method = 'handle'.basename(get_class($token), 'Token');

            if (method_exists($this, $method))
                call_user_func([$this, $method], $token);
            else {

                echo "UNHANDLED ".get_class($token)."\n";
                /*$this->throwException(
                    "Token ".get_class($token)." not handled",
                    $token
                );*/
            }
        }

        return $this->_currentNode->getRoot();
    }

    protected function nodeIs(NodeBase $node, array $names)
    {

        foreach ($names as $name) {

            $className = __NAMESPACE__.'\\Parser\\Node\\'.ucfirst($name).'Node';
            if (is_a($node, $className, true))
                return true;
        }
    }

    protected function currentIs(...$names)
    {

        return $this->nodeIs($this->_currentNode, $names);
    }

    protected function lastIs(...$names)
    {

        return $this->nodeIs($this->_lastNode, $names);
    }

    protected function newIs(...$names)
    {

        return $this->nodeIs($this->_newNode, $names);
    }

    protected function createNode($name, $setLast = true)
    {

        $className = __NAMESPACE__.'\\Parser\\Node\\'.ucfirst($name).'Node';
        $node = new $className();
        $this->_nodes[] = $node;

        if ($setLast)
            $this->_lastNode = $node;

        if ($node instanceof MixinNode)
            $this->_mixins[] = $node;

        return $node;
    }

    protected function pushNode(NodeBase $node)
    {

        if ($this->_newNode)
            $this->_newNode->appendChild($node);
        else
            $this->_newNode = $node;
    }

    protected function handleAssignment(AssignmentToken $token)
    {

        if (!$this->_newNode)
            $this->_newNode = $this->createNode('element');

        if (!$this->newIs('element'))
            $this->throwException(
                "Assignments can only happen on Elements"
            );

        $node = $this->createNode('assignment');
        $node->setName($token->getName());
        $this->_newNode->appendAssignment($node);
    }

    protected function handleAttribute(AttributeToken $token)
    {

        if (!$this->_newNode)
            $this->_newNode = $this->createNode('element');

        $targetNode = $this->_newNode;
        if ($this->lastIs('assignment')) {

            $targetNode = $this->_lastNode;
        } else if (!$this->newIs('element', 'mixin', 'mixinCall', 'import')) {

            $this->throwException(
                "Attributes can only be set on elements, assignments, mixins and imports",
                $token
            );
        } else if($this->newIs('element') && !$token->hasName()) {

            $this->throwException(
                "Failed to set attribute: Missing attribute name",
                $token
            );
        }

        /** @var \Tale\Jade\Parser\Node\AttributeNode $node */
        $node = $this->createNode('attribute', false);
        if ($token->hasName())
            $node->setName($token->getName());

        if ($token->hasValue())
            $node->setValue($token->getValue());

        $targetNode->appendAttribute($node);
    }

    protected function handleAttributeStart(AttributeStartToken $token)
    {
    }

    protected function handleAttributeEnd(AttributeEndToken $token)
    {
    }

    protected function handleBlock(BlockToken $token)
    {

        $node = $this->createNode('block');
        $node->setName($token->getName());
        $node->setType($token->getType());
        $this->pushNode($node);
    }

    protected function handleClass(ClassToken $token)
    {

        if (!$this->_newNode)
            $this->_newNode = $this->createNode('element');

        if (!$this->newIs('element', 'mixinCall'))
            $this->throwException("Classes can only be used on elements and mixin calls", $token);

        $attr = $this->createNode('attribute', false);
        $attr->setName('class');
        $attr->setValue($token->getName());
        $this->_newNode->appendAttribute($attr);
    }

    protected function handleComment(CommentToken $token)
    {

        $node = $this->createNode('comment');
        if ($token->isRendered())
            $node->render();
        $this->pushNode($node);
    }

    protected function handleConditional(ConditionalToken $token)
    {

        $node = $this->createNode('conditional');
        $node->setType($token->getType());
        $this->pushNode($node);
    }

    protected function handleExpression(ExpressionToken $token)
    {

        $node = $this->createNode('expression');
        if ($token->isEscaped())
            $node->escape();

        $this->pushNode($node);
    }

    protected function handleId(IdToken $token)
    {

        if (!$this->_newNode)
            $this->_newNode = $this->createNode('element');

        if (!$this->newIs('element', 'mixinCall'))
            $this->throwException("IDs can only be used on elements and mixin calls", $token);

        $attr = $this->createNode('attribute', false);
        $attr->setName('id');
        $attr->setValue($token->getName());
        $this->_newNode->appendAttribute($attr);
    }

    protected function handleImport(ImportToken $token)
    {

        //Did you notice that "include" and "extend" are actually the same thing?
        //The only difference (for the parser) is, that extend will probably
        //be at indent 0 and PROBABLY the first instruction at all

        if ($token->getType() === 'extends' && $this->_lastNode)
            $this->throwException(
                "extends should be the very first instruction in a jade file",
                $token
            );

        if ($this->_newNode)
            $this->throwException(
                "extend/include should be the very first statement on a line",
                $token
            );

        if (!$token->hasPath())
            $this->throwException(
                "No path given",
                $token
            );

        /** @var \Tale\Jade\Parser\Node\ImportNode $node */
        $node = $this->createNode('import');
        $node->setType($token->getType());
        $node->setPath($token->getPath());
        $node->setFilter($token->getFilter());

        $this->_newNode = $node;
    }

    protected function handleIndent(IndentToken $token)
    {

        if ($this->lastIs('import'))
            $this->throwException(
                'The instruction can\'t have children',
                $token
            );

        if ($this->_lastNode)
            $this->_currentNode = $this->_lastNode;
    }

    protected function handleTag(TagToken $token)
    {

        if (!$this->_newNode)
            $this->_newNode = $this->createNode('element');

        if (!$this->newIs('element'))
            $this->throwException("Tags can only be used on elements", $token);

        $this->_newNode->setTag($token->getName());
    }

    protected function handleMixin(MixinToken $token)
    {

        $this->_newNode = $this->createNode('mixin');
        $this->_newNode->setName($token->getName());
    }

    protected function handleMixinCall(MixinCallToken $token)
    {

        $this->_newNode = $this->createNode('mixinCall');
        $this->_newNode->setName($token->getName());
    }

    protected function handleNewLine(NewLineToken $token)
    {

        if ($this->_newNode) {

            $this->_currentNode->appendChild($this->_newNode);
            $this->_lastNode = $this->_newNode;
            $this->_newNode = null;
        }
    }

    protected function handleOutdent(OutdentToken $token)
    {

        for ($i = 0; $i < $token->getLevels(); $i++)
            if ($this->_currentNode->hasParent())
                $this->_currentNode = $this->_currentNode->getParent();
    }


    protected function handleText(TextToken $token)
    {

        $node = $this->createNode('text');
        $node->setValue($token->getValue());
        if ($this->_newNode)
            $this->_newNode->appendChild($node);
        else
            $this->_newNode = $node;
    }


    protected function throwException($message, TokenBase $relatedToken = null)
    {

        if ($relatedToken)
            $message .= "\n(".basename(get_class($relatedToken), 'Token')
                        .' at '.$relatedToken->getLine()
                        .':'.$relatedToken->getOffset().')';

        throw new Exception(
            "Failed to parse Jade: $message"
        );
    }
}