<?php
/**
 * The Tale Jade Parser.
 *
 * Contains the parser that takes tokens from the lexer
 * and converts it to an Abstract Syntax Tree (AST)
 *
 * This file is part of the Tale Jade Template Engine for PHP
 *
 * LICENSE:
 * The code of this file is distributed under the MIT license.
 * If you didn't receive a copy of the license text, you can
 * read it here http://licenses.talesoft.io/2015/MIT.txt
 *
 * @category   Presentation
 * @package    Tale\Jade
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Talesoft (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.0
 * @link       http://jade.talesoft.io/docs/files/Parser.html
 * @since      File available since Release 1.0
 */

namespace Tale\Jade;

use Tale\ConfigurableTrait;
use Tale\Jade\Lexer\TokenInterface;
use Tale\Jade\Parser\Node;
use Tale\Jade\Parser\Exception;
use Tale\Jade\Lexer\Token\AssignmentToken;
use Tale\Jade\Lexer\Token\AttributeEndToken;
use Tale\Jade\Lexer\Token\AttributeStartToken;
use Tale\Jade\Lexer\Token\AttributeToken;
use Tale\Jade\Lexer\Token\BlockToken;
use Tale\Jade\Lexer\Token\CaseToken;
use Tale\Jade\Lexer\Token\ClassToken;
use Tale\Jade\Lexer\Token\CodeToken;
use Tale\Jade\Lexer\Token\CommentToken;
use Tale\Jade\Lexer\Token\ConditionalToken;
use Tale\Jade\Lexer\Token\DoToken;
use Tale\Jade\Lexer\Token\DoctypeToken;
use Tale\Jade\Lexer\Token\EachToken;
use Tale\Jade\Lexer\Token\ExpansionToken;
use Tale\Jade\Lexer\Token\ExpressionToken;
use Tale\Jade\Lexer\Token\FilterToken;
use Tale\Jade\Lexer\Token\ForToken;
use Tale\Jade\Lexer\Token\IdToken;
use Tale\Jade\Lexer\Token\ImportToken;
use Tale\Jade\Lexer\Token\IndentToken;
use Tale\Jade\Lexer\Token\MixinCallToken;
use Tale\Jade\Lexer\Token\MixinToken;
use Tale\Jade\Lexer\Token\NewLineToken;
use Tale\Jade\Lexer\Token\OutdentToken;
use Tale\Jade\Lexer\Token\TagToken;
use Tale\Jade\Lexer\Token\TextToken;
use Tale\Jade\Lexer\Token\VariableToken;
use Tale\Jade\Lexer\Token\WhenToken;
use Tale\Jade\Lexer\Token\WhileToken;
use Tale\Jade\Parser\Node\AssignmentNode;
use Tale\Jade\Parser\Node\AttributeNode;
use Tale\Jade\Parser\Node\BlockNode;
use Tale\Jade\Parser\Node\CaseNode;
use Tale\Jade\Parser\Node\CodeNode;
use Tale\Jade\Parser\Node\CommentNode;
use Tale\Jade\Parser\Node\ConditionalNode;
use Tale\Jade\Parser\Node\DoctypeNode;
use Tale\Jade\Parser\Node\DoNode;
use Tale\Jade\Parser\Node\EachNode;
use Tale\Jade\Parser\Node\ElementNode;
use Tale\Jade\Parser\Node\ExpressionNode;
use Tale\Jade\Parser\Node\FilterNode;
use Tale\Jade\Parser\Node\ForNode;
use Tale\Jade\Parser\Node\ImportNode;
use Tale\Jade\Parser\Node\MixinCallNode;
use Tale\Jade\Parser\Node\MixinNode;
use Tale\Jade\Parser\Node\TextNode;
use Tale\Jade\Parser\Node\VariableNode;
use Tale\Jade\Parser\Node\WhenNode;
use Tale\Jade\Parser\Node\WhileNode;
use Tale\Jade\Parser\State;

/**
 * Takes tokens from the Lexer and creates an AST out of it.
 *
 * This class takes generated tokens from the Lexer sequentially
 * and produces an Abstract Syntax Tree (AST) out of it
 *
 * The AST is an object-tree containing Node-instances
 * with parent/child relations
 *
 * This AST is passed to the compiler to generate PHTML out of it
 *
 * Usage example:
 * <code>
 *
 *     use Tale\Jade\Parser;
 *
 *     $parser = new Parser();
 *
 *     echo $parser->parse($jadeInput);
 *     //Prints a human-readable dump of the parsed nodes
 *
 * </code>
 *
 * @category   Presentation
 * @package    Tale\Jade
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Talesoft (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.0
 * @link       http://jade.talesoft.io/docs/classes/Tale.Jade.Parser.html
 * @since      File available since Release 1.0
 */
class Parser
{
    use ConfigurableTrait;

    /**
     * The lexer used in this parser instance.
     *
     * @var Lexer
     */
    private $_lexer;

    /**
     * @var State
     */
    private $_state;

    /**
     * @var callable[]
     */
    private $_handlers;

    /**
     * Creates a new parser instance.
     *
     * The parser will run the provided input through the lexer
     * and generate an AST out of it.
     *
     * The AST will be an object-tree consisting of \Tale\Jade\Parser\Node instances
     *
     * You can take the AST and either compile it with the Compiler or handle it yourself
     *
     * Possible options are:
     *
     * lexerOptions:   The options for the lexer
     *
     * @param array|null $options the options array
     * @param Lexer|null $lexer   an existing lexer instance (lexer-option will be ignored)
     */
    public function __construct(array $options = null, Lexer $lexer = null)
    {

        $this->defineOptions([
            'lexerOptions' => [],
            'stateClassName' => State::class,
            'handlers' => [
                AssignmentToken::class => [$this, 'handleAssignment'],
                AttributeEndToken::class => [$this, 'handleAttributeEnd'],
                AttributeStartToken::class => [$this, 'handleAttributeStart'],
                AttributeToken::class => [$this, 'handleAttribute'],
                BlockToken::class => [$this, 'handleBlock'],
                CaseToken::class => [$this, 'handleCase'],
                ClassToken::class => [$this, 'handleClass'],
                CodeToken::class => [$this, 'handleCode'],
                CommentToken::class => [$this, 'handleComment'],
                ConditionalToken::class => [$this, 'handleConditional'],
                DoToken::class => [$this, 'handleDo'],
                DoctypeToken::class => [$this, 'handleDoctype'],
                EachToken::class => [$this, 'handleEach'],
                ExpansionToken::class => [$this, 'handleExpansion'],
                ExpressionToken::class => [$this, 'handleExpression'],
                FilterToken::class => [$this, 'handleFilter'],
                ForToken::class => [$this, 'handleFor'],
                IdToken::class => [$this, 'handleId'],
                ImportToken::class => [$this, 'handleImport'],
                IndentToken::class => [$this, 'handleIndent'],
                MixinCallToken::class => [$this, 'handleMixinCall'],
                MixinToken::class => [$this, 'handleMixin'],
                NewLineToken::class => [$this, 'handleNewLine'],
                OutdentToken::class => [$this, 'handleOutdent'],
                TagToken::class => [$this, 'handleTag'],
                TextToken::class => [$this, 'handleText'],
                VariableToken::class => [$this, 'handleVariable'],
                WhenToken::class => [$this, 'handleWhen'],
                WhileToken::class => [$this, 'handleWhile']
            ]
        ], $options);

        $this->_lexer = $lexer ?: new Lexer($this->getOption('lexerOptions'));
        $this->_state = null;
        $this->_handlers = [];

        foreach ($this->getOption('handlers') as $className => $handler)
            $this->setHandler($className, $handler);
    }

    /**
     * Returns the currently used Lexer instance.
     *
     * @return Lexer
     */
    public function getLexer()
    {

        return $this->_lexer;
    }

    public function setHandler($className, $handler)
    {

        if (!is_callable($handler))
            throw new \InvalidArgumentException(
                "Argument 2 of Parser->setHandler needs to be valid callback"
            );

        $this->_handlers[$className] = $handler;

        return $this;
    }

    /**
     * Parses the provided input-string to an AST.
     *
     * The Abstract Syntax Tree (AST) will be an object-tree consisting
     * of \Tale\Jade\Parser\Node instances.
     *
     * You can either let the compiler compile it or compile it yourself
     *
     * The root-node will always be of type 'document',
     * from there on it can contain several kinds of nodes
     *
     * @param string $input the input jade string that is to be parsed
     *
     * @return Node the root-node of the parsed AST
     */
    public function parse($input)
    {

        $stateClassName = $this->getOption('stateClassName');

        if (!is_a($stateClassName, State::class, true))
            throw new \InvalidArgumentException(
                'stateClassName needs to be a valid '.State::class.' sub class'
            );

        $this->_state = new $stateClassName(
            $this->_lexer->lex($input)
        );

        //While we have tokens, handle current token, then go to next token
        //rinse and repeat
        while ($this->_state->hasTokens()) {

            $this->handle();
            $this->_state->nextToken();
        }

        $document = $this->_state->getDocumentNode();
        $this->_state = null;

        //Some work after parsing needed
        /*
        //Resolve expansions/outer nodes
        if (isset($node->expands)) {

            $current = $node;
            while (isset($current->expands)) {

                $expandedNode = $current->expands;
                unset($current->expands);

                $current->parent->insertBefore($current, $expandedNode);
                $current->parent->remove($current);
                $expandedNode->append($current);
                $current = $expandedNode;
            }

            return $this->compileNode($current);
        }*/

        //Return the final document node with all its awesome child nodes
        return $document;
    }

    /**
     * Handles any kind of token returned by the lexer.
     *
     * The token handler is translated according to the `handlers` option
     *
     * If no token is passed, it will take the current token
     * in the lexer's token generator
     *
     * @param TokenInterface $token a token or the current lexer's generator token
     *
     * @throws Exception when no token handler has been found
     */
    public function handle(TokenInterface $token = null)
    {

        if (!$this->_state)
            throw new Exception(
                "Failed to handle token: No parsing process active"
            );

        $token = $token ? $token : $this->_state->getToken();
        $className = get_class($token);

        if (!isset($this->_handlers[$className]))
            $this->_state->throwException(
                "Unexpected token `$className`, no handler registered",
                $token
            );

        $handler = $this->_handlers[$className];
        call_user_func($handler, $token, $this->_state);
    }

    /**
     * Parses an <assignment>-token into element assignments.
     *
     * If no there is no $_current element, a new one is created
     *
     * Assignments are possible on elements and mixinCalls only
     *
     * After an assignment, an attribute block is required
     *
     * @param AssignmentToken $token the <assignment>-token
     * @param State $state the parser state
     *
     * @throws Exception
     */
    protected function handleAssignment(AssignmentToken $token, State $state)
    {

        if (!$state->getCurrentNode())
            $state->setCurrentNode($state->createNode(ElementNode::class, $token));

        if (!$state->currentNodeIs([ElementNode::class, MixinCallNode::class]))
            $state->throwException(
                "Assignments can only happen on elements and mixinCalls",
                $token
            );

        /** @var AssignmentNode $node */
        $node = $state->createNode(AssignmentNode::class, $token);
        $node->setName($token->getName());

        /** @var ElementNode|MixinCallNode $current */
        $current = $state->getCurrentNode();
        $current->getAssignments()->appendChild($node);

        if ($state->expectNext([AttributeStartToken::class])) {

            $state->setCurrentNode($node);
            //Will trigger iteration of consecutive attribute tokens
            //in handleAttributeStart with $node as the target
            $this->handle();
            $state->setCurrentNode($current);
        }
    }

    /**
     * Parses an <attribute>-token into an attribute-node.
     *
     * That node is appended to the $_current element.
     *
     * If no $_current element exists, a new one is created
     *
     * Attributes in elements and mixins always need a valid name
     *
     * @param AttributeToken $token the <attribute>-token
     * @param State $state the parser state
     *
     * @throws Exception
     */
    protected function handleAttribute(AttributeToken $token, State $state)
    {

        if (!$state->getCurrentNode())
            $state->setCurrentNode($state->createNode(ElementNode::class, $token));

        /** @var AttributeNode $node */
        $node = $state->createNode(AttributeNode::class, $token);
        $node->setName($token->getName());
        $node->setValue($token->getValue());
        $node->setIsEscaped($token->isEscaped());
        $node->setIsChecked($token->isChecked());

        $name = $node->getName();
        $value = $node->getValue();

        if ($state->currentNodeIs([MixinCallNode::class]) && ($value === '' || $value === null)) {

            $node->setValue($node->getName());
            $node->setName(null);
        }

        /** @var ElementNode|MixinCallNode $current */
        $current = $state->getCurrentNode();
        $current->getAttributes()->appendChild($node);
    }

    /**
     * Handles an <attributeStart>-token.
     *
     * Attributes can only start on elements, assignments, imports, mixins and mixinCalls
     *
     * After that, all following <attribute>-tokens are handled.
     * After that, an <attributeEnd>-token is expected
     * (When I think about it, the Lexer kind of does that already)
     *
     * @param AttributeStartToken $token the <attributeStart>-token
     * @param State $state the parser state
     *
     * @throws Exception
     */
    protected function handleAttributeStart(AttributeStartToken $token, State $state)
    {

        if (!$state->getCurrentNode())
            $state->setCurrentNode($state->createNode(ElementNode::class, $token));

        if (!$state->currentNodeIs([
            ElementNode::class, AssignmentNode::class,
            ImportNode::class, VariableNode::class,
            MixinNode::class, MixinCallNode::class
        ]))
            $state->throwException(
                "Attributes can only be placed on element, assignment, "
                ."import, variable, mixin and mixinCall",
                $token
            );

        foreach ($state->lookUpNext([AttributeToken::class]) as $subToken) {

            $this->handle($subToken);
        }

        if (!$state->expect([AttributeEndToken::class]))
            $state->throwException(
                "Attribute list not closed",
                $token
            );
    }

    /**
     * Handles an <attributeEnd>-token.
     *
     * It does nothing (right now?)
     *
     * @param AttributeEndToken $token the <attributeEnd>-token
     * @param State $state the parser state
     */
    protected function handleAttributeEnd(AttributeEndToken $token, State $state)
    {
        //Nothing to do here.
    }

    /**
     * Handles a <block>-token and parses it into a block-node.
     *
     * Blocks outside a mixin always need a name! (That's what $_inMixin is for)
     *
     * @param BlockToken $token the <block>-token
     * @param State $state the parser state
     *
     * @throws Exception
     */
    protected function handleBlock(BlockToken $token, State $state)
    {

        /** @var BlockNode $node */
        $node = $state->createNode(BlockNode::class, $token);
        $node->setName($token->getName());
        $node->setMode($token->getMode());
        $state->setCurrentNode($node);
    }

    /**
     * Handles a <class>-token and parses it into an element.
     *
     * If there's no $_current-node, a new one is created
     *
     * It will be converted to a regular <attribute>-node on the element
     * (There is no class-node)
     *
     * Classes can only exist on elements and mixinCalls
     *
     * @param ClassToken $token the <class>-token
     * @param State $state the parser state
     *
     * @throws Exception
     */
    protected function handleClass(ClassToken $token, State $state)
    {

        if (!$state->getCurrentNode())
            $state->setCurrentNode($state->createNode(ElementNode::class, $token));

        if (!$state->currentNodeIs([ElementNode::class, MixinCallNode::class]))
            $state->throwException(
                "Classes can only be used on elements and mixin calls",
                $token
            );

        //We actually create a fake class attribute
        /** @var AttributeNode $attr */
        $attr = $state->createNode(AttributeNode::class, $token);
        $attr->setName('class');
        $attr->setValue($token->getName());
        $attr->unescape()->uncheck();

        /** @var ElementNode|MixinCallNode $current */
        $current = $state->getCurrentNode();
        $current->getAttributes()->appendChild($attr);
    }

    /**
     * Handles a <comment>-token and parses it into a comment-node.
     *
     * The comment node is set as the $_current element
     *
     * @param CommentToken $token the <comment>-token
     * @param State $state the parser state
     */
    protected function handleComment(CommentToken $token, State $state)
    {

        /** @var CommentNode $node */
        $node = $state->createNode(CommentNode::class, $token);
        $node->setIsVisible($token->isVisible());
        $state->setCurrentNode($node);
    }

    /**
     * Handles a <case>-token and parses it into a case-node.
     *
     * @param CaseToken $token the <case>-token
     * @param State $state the parser state
     */
    protected function handleCase(CaseToken $token, State $state)
    {

        /** @var CaseNode $node */
        $node = $state->createNode(CaseNode::class, $token);
        $node->setSubject($token->getSubject());
        $state->setCurrentNode($node);
    }

    /**
     * Handles a <conditional>-token and parses it into a conditional-node.
     *
     * @param ConditionalToken $token the <conditional>-token
     * @param State $state the parser state
     */
    protected function handleConditional(ConditionalToken $token, State $state)
    {

        /** @var ConditionalNode $node */
        $node = $state->createNode(ConditionalNode::class, $token);
        $node->setSubject($token->getSubject());
        $node->setName($token->getName());
        $state->setCurrentNode($node);
    }

    /**
     * Handles a <do>-token and parses it into a do-node.
     *
     * @param DoToken $token the <do>-token
     * @param State $state the parser state
     */
    protected function handleDo(DoToken $token, State $state)
    {

        $node = $state->createNode(DoNode::class, $token);
        $state->setCurrentNode($node);
    }

    /**
     * Handles a <doctype>-token and parses it into a doctype-node.
     *
     * @param DoctypeToken $token the <doctype>-token
     * @param State $state the parser state
     */
    protected function handleDoctype(DoctypeToken $token, State $state)
    {

        /** @var DoctypeNode $node */
        $node = $state->createNode(DoctypeNode::class, $token);
        $node->setName($token->getName());
        $state->setCurrentNode($node);
    }

    /**
     * Handles an <each>-token and parses it into an each-node.
     *
     * @param EachToken $token the <each>-token
     * @param State $state the parser state
     */
    protected function handleEach(EachToken $token, State $state)
    {

        /** @var EachNode $node */
        $node = $state->createNode(EachNode::class, $token);
        $node->setSubject($token->getSubject());
        $node->setItem($token->getItem());
        $node->setKey($token->getKey());
        $state->setCurrentNode($node);
    }

    /**
     * Handles an <expression>-token into an expression-node.
     *
     * If there's a $_current-element, the expression gets appended
     * to the $_current-element. If not, the expression itself
     * becomes the $_current element
     *
     * @param ExpressionToken $token the <expression>-token
     * @param State $state the parser state
     *
     * @throws Exception
     */
    protected function handleExpression(ExpressionToken $token, State $state)
    {

        /** @var ExpressionNode $node */
        $node = $state->createNode(ExpressionNode::class, $token);
        $node->setIsEscaped($token->isEscaped());
        $node->setIsChecked($token->isChecked());
        $node->setValue($token->getValue());

        if ($state->getCurrentNode())
            $state->getCurrentNode()->appendChild($node);
        else
            $state->setCurrentNode($node);
    }

    /**
     * Handles an <code>-token into an code-node.
     *
     * @param CodeToken $token the <code>-token
     * @param State $state the parser state
     *
     * @throws Exception
     */
    protected function handleCode(CodeToken $token, State $state)
    {

        /** @var CodeNode $node */
        $node = $state->createNode(CodeNode::class, $token);
        $node->setValue($token->getValue());
        $node->setIsBlock($token->isBlock());
        $state->setCurrentNode($node);
    }

    /**
     * Handles a <filter>-token and parses it into a filter-node.
     *
     * @param FilterToken $token the <filter>-token
     * @param State $state the parser state
     */
    protected function handleFilter(FilterToken $token, State $state)
    {

        /** @var FilterNode $node */
        $node = $state->createNode(FilterNode::class, $token);
        $node->setName($token->getName());
        $state->setCurrentNode($node);
    }

    /**
     * Handles an <id>-token and parses it into an element.
     *
     * If no $_current element exists, a new one is created
     *
     * IDs can only exist on elements an mixin calls
     *
     * They will get converted to attribute-nodes and appended to the current element
     *
     * @param IdToken $token the <id>-token
     * @param State $state the parser state
     *
     * @throws Exception
     */
    protected function handleId(IdToken $token, State $state)
    {

        if (!$state->getCurrentNode())
            $state->setCurrentNode($state->createNode(ElementNode::class));

        if (!$state->currentNodeIs([ElementNode::class, MixinCallNode::class]))
            $state->throwException(
                'IDs can only be used on elements and mixin calls',
                $token
            );

        /** @var AttributeNode $attr */
        $attr = $state->createNode(AttributeNode::class, $token);
        $attr->setName('id');
        $attr->setValue($token->getName());
        $attr->unescape()->uncheck();

        /** @var ElementNode|MixinCallNode $current */
        $current = $state->getCurrentNode();
        $current->getAttributes()->appendChild($attr);
    }

    /**
     * Handles a <variable>-token and parses it into a variable assignment.
     *
     * @param VariableToken $token the <variable>-token
     * @param State $state the parser state
     *
     * @throws Exception
     */
    protected function handleVariable(VariableToken $token, State $state)
    {

        /** @var VariableNode $node */
        $node = $state->createNode(VariableNode::class);
        $node->setName($token->getName());
        $state->setCurrentNode($node);
    }

    /**
     * Handles an <import>-token and parses it into an import-node.
     *
     * Notice that "extends" and "include" are basically the same thing.
     * The only difference is that "extends" can only exist at the very
     * beginning of a jade-block
     *
     * Only "include" can have filters, though.
     * This gets checked in the Compiler, not here
     *
     * @param ImportToken $token the <import>-token
     * @param State $state the parser state
     *
     * @throws Exception
     */
    protected function handleImport(ImportToken $token, State $state)
    {

        if ($token->getName() === 'extends' && $state->getDocumentNode()->hasChildren())
            $state->throwException(
                "extends should be the very first statement in a document",
                $token
            );

        /** @var ImportNode $node */
        $node = $state->createNode(ImportNode::class, $token);
        $node->setName($token->getName());
        $node->setPath($token->getPath());
        $node->setFilter($token->getFilter());
        $state->setCurrentNode($node);
    }

    /**
     * Handles an <indent>-token.
     *
     * The $_level will be increased by 1 for each <indent>
     *
     * If there's no $_last element (which is set on a newLine), we do nothing
     * (because there's nothing to indent into)
     *
     * The $_last node is set as the $_currentParent node and acts as a parent-node
     * for further created nodes (They get appended in handleNewLine)
     *
     * import-nodes can't be indented into, because they can't have children (poor imports :'( )
     *
     * The opposite of this is, obviously, handleOutdent with <outdent>-tokens
     *
     * @param IndentToken $token the <indent>-token
     * @param State $state the parser state
     *
     * @throws Exception
     */
    protected function handleIndent(IndentToken $token, State $state)
    {

        $state->enter();
    }

    /**
     * Handles a <tag>-token and parses it into a tag-node.
     *
     * If no $_current element exists, a new one is created
     * A tag can only exist once on an element
     * Only elements can have tags
     *
     * @param TagToken $token the <tag>-token
     * @param State $state the parser state
     *
     * @throws Exception
     */
    protected function handleTag(TagToken $token, State $state)
    {

        if (!$state->getCurrentNode())
            $state->setCurrentNode($state->createNode(ElementNode::class, $token));

        if (!$state->currentNodeIs([ElementNode::class]))
            $state->throwException(
                'Tags can only be used on elements',
                $token
            );

        /** @var ElementNode $current */
        $current = $state->getCurrentNode();

        if ($current->getName())
            $state->throwException(
                'The element already has a tag name',
                $token
            );

        $current->setName($token->getName());
    }

    /**
     * Handles a <mixin>-token and parses it into a mixin-node.
     *
     * Mixins can't be inside other mixins.
     * We use $_inMixin and $_mixinLevel for that
     * $_mixinLevel gets reset in handleOutdent
     *
     * @param MixinToken $token the <mixin>-token
     * @param State $state the parser state
     *
     * @throws Exception
     */
    protected function handleMixin(MixinToken $token, State $state)
    {

        /** @var MixinNode $node */
        $node = $state->createNode(MixinNode::class, $token);
        $node->setName($token->getName());
        $state->setCurrentNode($node);
    }

    /**
     * Handles a <mixinCall>-token and parses it into a mixinCall-node.
     *
     * @param MixinCallToken $token the <mixinCall>-token
     * @param State $state the parser state
     */
    protected function handleMixinCall(MixinCallToken $token, State $state)
    {

        /** @var MixinCallNode $node */
        $node = $state->createNode(MixinCallNode::class, $token);
        $node->setName($token->getName());
        $state->setCurrentNode($node);
    }

    /**
     * Handles a <newLine>-token.
     *
     * If there's no $_current element, it does nothing
     * If there is one it:
     *
     * 1. Checks if we have an $_expansion. If we do, append it to $_current and reset $_expansion
     * 2. Appends the $_current element to the $_currentParent
     * 3. Set's the $_last element to the $_current element
     * 4. Resets $_current to null
     *
     * @param NewLineToken $token the <newLine>-token or null
     * @param State $state the parser state
     */
    protected function handleNewLine(NewLineToken $token, State $state)
    {

        $state->store();
    }

    /**
     * Handles an <outdent>-token.
     *
     * Decreases the current $_level by 1
     *
     * Sets the $_currentParent to the ->parent of $_currentParent
     * (Walking up the tree by 1)
     *
     * If we're in a mixin and we're at or below our mixin-level again,
     * we're not in a mixin anymore
     *
     * @param OutdentToken $token the <outdent>-token
     * @param State $state the parser state
     */
    protected function handleOutdent(OutdentToken $token, State $state)
    {

        $state->leave();
    }

    /**
     * Handles an <expansion>-token.
     *
     * If there's no current element, we don't expand anything and throw an exception
     *
     * If there's no space behind the : and the next token is a <tag>-token,
     * we don't treat this as an expansion, but rather as a tag-extension
     * (a:b === <a:b></a:b>, a: b === <a><b></b></a>)
     * This is important for XML and XML-namespaces
     *
     * Notice that, right now, any element that can also land in $_current can be expanded
     * (so almost all elements there are)
     * It just makes no sense for some elements ("extends", "include")
     *
     * $_current is reset after the expansion so that we can collect the expanding element
     * and handle it on a newLine or in an indent
     *
     * @param ExpansionToken $token the <expansion>-token
     * @param State $state the parser state
     *
     * @throws Exception
     */
    protected function handleExpansion(ExpansionToken $token, State $state)
    {

        if (!$state->getCurrentNode())
            $state->throwException(
                "Expansion needs an element to work on",
                $token
            );

        if (!$state->currentNodeIs([ElementNode::class]) && !$token->hasSpace()) {

            if (!$state->expectNext([TagToken::class])) {
                $state->throwException(
                    sprintf(
                        "Expected tag name or expansion after double colon, "
                        ."%s received",
                        basename(get_class($state->getToken()), 'Token')
                    ),
                    $token
                );
            }

            /** @var TagToken $token */
            $token = $state->getToken();
            /** @var ElementNode $current */
            $current = $state->getCurrentNode();
            $current->setName($current->getName().':'.$token->getName());

            return;
        }

        //Make sure to keep the expansion saved
        if ($state->getOuterNode())
            $state->getCurrentNode()->setOuterNode($state->getOuterNode());

        $state->setOuterNode($state->getCurrentNode());
        $state->setCurrentNode(null);
    }


    /**
     * Handles a <text>-token and parses it into a text-node.
     *
     * If there's a $_current element, we append it to that element,
     * if not, it becomes the $_current element
     *
     * @param TextToken $token the <text>-token
     * @param State $state the parser state
     */
    protected function handleText(TextToken $token, State $state)
    {

        /** @var TextNode $node */
        $node = $state->createNode(TextNode::class, $token);
        $node->setValue($token->getValue());
        $node->setLevel($token->getLevel());
        $node->setIsEscaped($token->isEscaped());

        if ($state->getCurrentNode()) {

            $state->getCurrentNode()->appendChild($node);
        } else
            $state->setCurrentNode($node);
    }

    /**
     * Handles a <when>-token and parses it into a when-node.
     *
     * @param WhenToken $token the <when>-token
     * @param State $state the parser state
     */
    protected function handleWhen(WhenToken $token, State $state)
    {

        /** @var WhenNode $node */
        $node = $state->createNode(WhenNode::class, $token);
        $node->setSubject($token->getSubject());
        $node->setName($token->getName());
        $state->setCurrentNode($node);
    }

    /**
     * Handles a <while>-token and parses it into a while-node.
     *
     * @param WhileToken $token the <while>-token
     * @param State $state the parser state
     */
    protected function handleWhile(WhileToken $token, State $state)
    {

        /** @var WhileNode $node */
        $node = $state->createNode(WhileNode::class, $token);
        $node->setSubject($token->getSubject());
        $state->setCurrentNode($node);
    }


    /**
     * Handles a <for>-token and parses it into a for-node.
     *
     * @param ForToken $token the <while>-token
     * @param State $state the parser state
     */
    protected function handleFor(ForToken $token, State $state)
    {

        /** @var ForNode $node */
        $node = $state->createNode(ForNode::class, $token);
        $node->setSubject($token->getSubject());
        $state->setCurrentNode($node);
    }
}