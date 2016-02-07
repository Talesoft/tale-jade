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
     * The level we're currently on.
     *
     * This does not equal the Lexer-level or Compiler-level,
     * it's an internal level to get the child/parent-relation between
     * nodes right
     *
     * @var int
     */
    private $_level;

    /**
     * The Generator returned by the ->lex() method of the lexer.
     *
     * @var \Generator
     */
    private $_tokens;

    /**
     * The root node of the currently parsed document.
     *
     * @var Node
     */
    private $_document;

    /**
     * The parent that currently found childs are appended to.
     *
     * When an <outdent>-token is encountered, it moves one parent up
     * ($_currentParent->parent becomes the new $_currentParent)
     *
     * @var Node
     */
    private $_currentParent;

    /**
     * The current element in the queue.
     *
     * Will be appended to $_currentParent when a <newLine>-token is encountered
     * It will become the current parent, if an <indent>-token is encountered
     *
     * @var Node
     */
    private $_current;

    /**
     * The last element that was completely put together.
     *
     * Will be set on a <newLine>-token ($_current will become last)
     *
     * @var Node
     */
    private $_last;

    /**
     * States if we're in a mixin or not.
     *
     * Used to check for the mixin-block and nested mixins
     *
     * @var bool
     */
    private $_inMixin;

    /**
     * The level we're on inside a mixin.
     *
     * Used to check for the mixin-block and nested mixins
     *
     * @var int
     */
    private $_mixinLevel;

    /**
     * Stores an expanded node to attach it to the expanding node later.
     *
     * @var Node
     */
    private $_expansion;


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

        $this->_lexer = $lexer ? $lexer : new Lexer($this->_options['lexerOptions']);
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

    /**
     * Parses the provided input-string to an AST.
     *
     * The Abstract Syntax Tree (AST) will be an object-tree consisting of \Tale\Jade\Parser\Node instances.
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

        $this->_level = 0;
        $this->_tokens = $this->_lexer->lex($input);
        $this->_document = $this->createNode('document');
        $this->_currentParent = $this->_document;
        $this->_current = null;
        $this->_last = null;
        $this->_inMixin = false;
        $this->_mixinLevel = null;
        $this->_expansion = null;

        //Fix HHVM generators needing ->next() before ->current()
        //This will actually work as expected, no node will be skipped
        //HHVM always needs a first ->next() (I don't know if this is a bug or
        //expected behaviour)
        if (defined('HHVM_VERSION')) {

            $this->_tokens->next();
        }

        //While we have tokens, handle current token, then go to next token
        //rinse and repeat
        while ($this->hasTokens()) {

            $this->handleToken();
            $this->nextToken();
        }

        //Return the final document node with all its awesome child nodes
        return $this->_document;
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
    protected function handleToken(TokenInterface $token = null)
    {

        $token = $token ? $token : $this->getToken();
        $className = get_class($token);

        if (!isset($this->_options['handlers'][$className]))
            $this->throwException(
                "Unexpected token `$className`, no handler registered",
                $token
            );

        $handler = $this->_options['handlers'][$className];
        if (!is_callable($handler))
            $this->throwException(
                "Unexpected token `$className`, registered handler is not ".
                "a valid callback",
                $token
            );

        call_user_func($handler, $token);
    }

    /**
     * Yields tokens as long as the given types match.
     *
     * Yields tokens of the given types until
     * a token is encountered, that is not given
     * in the types-array
     *
     * @param array $types the token types that are allowed
     *
     * @return \Generator
     */
    protected function lookUp(array $types)
    {

        while ($this->hasTokens()) {

            $token = $this->getToken();
            if (in_array(get_class($token), $types, true))
                yield $token;
            else
                break;

            $this->nextToken();
        }
    }

    /**
     * Moves on the token generator by one and does ->lookUp().
     *
     * @see Parser->nextToken
     * @see Parser->lookUp
     *
     * @param array $types the types that are allowed
     *
     * @return \Generator
     */
    protected function lookUpNext(array $types)
    {

        return $this->nextToken()->lookUp($types);
    }

    /**
     * Returns the token of the given type if it is in the token queue.
     *
     * If the given token in the queue is not of the given type,
     * this method returns null
     *
     * @param array $types the types that are expected
     *
     * @return Node|null
     */
    protected function expect(array $types)
    {

        foreach ($this->lookUp($types) as $token) {

            return $token;
        }

        return null;
    }

    /**
     * Moves the generator on by one and does ->expect().
     *
     * @see Parser->nextToken
     * @see Parser->expect
     *
     * @param array $types the types that are expected
     *
     * @return Node|null
     */
    protected function expectNext(array $types)
    {

        return $this->nextToken()->expect($types);
    }

    /**
     * Returns true, if there are still tokens left to be generated.
     *
     * If the lexer-generator still has tokens to generate,
     * this returns true and false, if it doesn't
     *
     * @see \Generator->valid
     *
     * @return bool
     */
    protected function hasTokens()
    {

        return $this->_tokens->valid();
    }

    /**
     * Moves the generator on by one token.
     *
     * (It calls ->next() on the generator, look at the PHP doc)
     *
     * @see \Generator->next
     *
     * @return $this
     */
    protected function nextToken()
    {

        $this->_tokens->next();

        return $this;
    }

    /**
     * Returns the current token in the lexer generator.
     *
     * @see \Generator->current
     *
     * @return array the token array (always _one_ token, as an array)
     */
    protected function getToken()
    {

        return $this->_tokens->current();
    }


    /**
     * Creates a new node instance with the given type.
     *
     * If a token is given, the location in the code of that token
     * is also passed to the Node instance
     *
     * If no token is passed, a dummy-token with the current
     * lexer's offset and line is created
     *
     * Notice that nodes are expando-objects, you can add properties on-the-fly
     * and retrieve them as an array later
     *
     * @param string     $type  the type the node should have
     * @param TokenInterface $token the token to relate this node to
     *
     * @return Node The newly created node
     */
    protected function createNode($type, TokenInterface $token = null)
    {

        $token = $token ? $token : new TextToken(0, 0, 0);

        return new Node($type, $token->getLine(), $token->getOffset());
    }

    /**
     * Creates an element-node with the properties it should have consistently.
     *
     * This will create the following properties on the Node instance:
     *
     * @todo Do this for a bunch of other elements as well, maybe all, maybe a centralized way?
     *
     * @param TokenInterface $token the token to relate this element to
     *
     * @return Node the newly created element-node
     */
    protected function createElement(TokenInterface $token = null)
    {

        $node = $this->createNode('element', $token);
        $node->tag = null;
        $node->attributes = [];
        $node->assignments = [];

        return $node;
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
     *
     * @throws Exception
     */
    protected function handleAssignment(AssignmentToken $token)
    {

        if (!$this->_current)
            $this->_current = $this->createElement();

        if (!in_array($this->_current->type, ['element', 'mixinCall']))
            $this->throwException(
                "Assignments can only happen on elements and mixinCalls"
            );

        $node = $this->createNode('assignment', $token);
        $node->name = $token->getName();
        $this->_current->assignments[] = $node;

        if ($this->expectNext([AttributeStartToken::class])) {

            $element = $this->_current;
            $this->_current = $node;
            $this->handleToken();
            $this->_current = $element;
        } else
            $this->throwException(
                "Assignments require a parameter block"
            );
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
     *
     * @throws Exception
     */
    protected function handleAttribute(AttributeToken $token)
    {

        if (!$this->_current)
            $this->_current = $this->createElement();

        $node = $this->createNode('attribute', $token);
        $node->name = $token->getName();
        $node->value = $token->getValue();
        $node->escaped = $token->isEscaped();
        $node->unchecked = $token->isChecked();

        if (!$node->name && in_array($this->_current->type, ['element', 'mixin']))
            $this->throwException('Attributes in elements and mixins need a name', $token);

        if ($this->_current->type === 'mixinCall' && !$node->value) {

            $node->value = $node->name;
            $node->name = null;
        }

        $this->_current->attributes[] = $node;
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
     *
     * @throws Exception
     */
    protected function handleAttributeStart(AttributeStartToken $token)
    {

        if (!$this->_current)
            $this->_current = $this->createElement();

        if (!in_array($this->_current->type, ['element', 'assignment', 'import', 'variable', 'mixin', 'mixinCall']))
            $this->throwException(
                "Attributes can only be placed on element, assignment, import, variable, mixin and mixinCall"
            );

        foreach ($this->lookUpNext([AttributeToken::class]) as $subToken) {

            $this->handleToken($subToken);
        }

        if (!$this->expect([AttributeEndToken::class]))
            $this->throwException(
                "Attribute list not ended",
                $token
            );
    }

    /**
     * Handles an <attributeEnd>-token.
     *
     * It does nothing (right now?)
     *
     * @param AttributeEndToken $token the <attributeEnd>-token
     */
    protected function handleAttributeEnd(AttributeEndToken $token)
    {

    }

    /**
     * Handles a <block>-token and parses it into a block-node.
     *
     * Blocks outside a mixin always need a name! (That's what $_inMixin is for)
     *
     * @param BlockToken $token the <block>-token
     *
     * @throws Exception
     */
    protected function handleBlock(BlockToken $token)
    {

        $node = $this->createNode('block', $token);
        $node->name = $token->getName();
        $node->mode = $token->getMode();

        if (!$node->name && !$this->_inMixin)
            $this->throwException(
                "Blocks outside a mixin always need a name"
            );

        $this->_current = $node;
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
     *
     * @throws Exception
     */
    protected function handleClass(ClassToken $token)
    {

        if (!$this->_current)
            $this->_current = $this->createElement();

        if (!in_array($this->_current->type, ['element', 'mixinCall']))
            $this->throwException("Classes can only be used on elements and mixin calls", $token);

        $attr = $this->createNode('attribute', $token);
        $attr->name = 'class';
        $attr->value = $token->getName();
        $attr->escaped = false;
        $attr->checked = false;
        $this->_current->attributes[] = $attr;
    }

    /**
     * Handles a <comment>-token and parses it into a comment-node.
     *
     * The comment node is set as the $_current element
     *
     * @param CommentToken $token the <comment>-token
     */
    protected function handleComment(CommentToken $token)
    {

        $node = $this->createNode('comment', $token);
        $node->rendered = $token->isRendered();

        $this->_current = $node;
    }

    /**
     * Handles a <case>-token and parses it into a case-node.
     *
     * @param CaseToken $token the <case>-token
     */
    protected function handleCase(CaseToken $token)
    {

        $node = $this->createNode('case', $token);
        $node->subject = $token->getSubject();
        $this->_current = $node;
    }

    /**
     * Handles a <conditional>-token and parses it into a conditional-node.
     *
     * @param ConditionalToken $token the <conditional>-token
     */
    protected function handleConditional(ConditionalToken $token)
    {

        $node = $this->createNode('conditional', $token);
        $node->subject = $token->getSubject();
        $node->conditionType = $token->getName();

        $this->_current = $node;
    }

    /**
     * Handles a <do>-token and parses it into a do-node.
     *
     * @param DoToken $token the <do>-token
     */
    protected function handleDo(DoToken $token)
    {

        $node = $this->createNode('do', $token);
        $this->_current = $node;
    }

    /**
     * Handles a <doctype>-token and parses it into a doctype-node.
     *
     * @param DoctypeToken $token the <doctype>-token
     */
    protected function handleDoctype(DoctypeToken $token)
    {

        $node = $this->createNode('doctype', $token);
        $node->name = $token->getName();

        $this->_current = $node;
    }

    /**
     * Handles an <each>-token and parses it into an each-node.
     *
     * @param EachToken $token the <each>-token
     */
    protected function handleEach(EachToken $token)
    {

        $node = $this->createNode('each', $token);
        $node->subject = $token->getSubject();
        $node->itemName = $token->getItemName();
        $node->keyName = $token->getKeyName();

        $this->_current = $node;
    }

    /**
     * Handles an <expression>-token into an expression-node.
     *
     * If there's a $_current-element, the expression gets appended
     * to the $_current-element. If not, the expression itself
     * becomes the $_current element
     *
     * @param ExpressionToken $token the <expression>-token
     *
     * @throws Exception
     */
    protected function handleExpression(ExpressionToken $token)
    {

        $node = $this->createNode('expression', $token);
        $node->escaped = $token->isEscaped();
        $node->unchecked = $token->isChecked();
        $node->value = $token->getValue();

        if ($this->_current)
            $this->_current->append($node);
        else
            $this->_current = $node;
    }

    /**
     * Handles an <code>-token into an code-node.
     *
     * @param CodeToken $token the <code>-token
     *
     * @throws Exception
     */
    protected function handleCode(CodeToken $token)
    {

        $node = $this->createNode('code', $token);
        $node->value = $token->getValue();
        $node->block = $token->isBlock();

        $this->_current = $node;
    }

    /**
     * Handles a <filter>-token and parses it into a filter-node.
     *
     * @param FilterToken $token the <filter>-token
     */
    protected function handleFilter(FilterToken $token)
    {

        $node = $this->createNode('filter', $token);
        $node->name = $token->getName();
        $this->_current = $node;
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
     *
     * @throws Exception
     */
    protected function handleId(IdToken $token)
    {

        if (!$this->_current)
            $this->_current = $this->createElement();

        if (!in_array($this->_current->type, ['element', 'mixinCall']))
            $this->throwException("IDs can only be used on elements and mixin calls", $token);

        $attr = $this->createNode('attribute', $token);
        $attr->name = 'id';
        $attr->value = $token->getName();
        $attr->escaped = false;
        $attr->checked = false;
        $this->_current->attributes[] = $attr;
    }

    /**
     * Handles a <variable>-token and parses it into a variable assignment.
     *
     * @param VariableToken $token the <variable>-token
     *
     * @throws Exception
     */
    protected function handleVariable(VariableToken $token)
    {

        $node = $this->createNode('variable');
        $node->name = $token->getName();
        $node->attributes = [];
        $this->_current = $node;
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
     *
     * @throws Exception
     */
    protected function handleImport(ImportToken $token)
    {

        if ($token->getName() === 'extends' && count($this->_document->children) > 0)
            $this->throwException(
                "extends should be the very first statement in a document",
                $token
            );

        $node = $this->createNode('import', $token);
        $node->importType = $token->getName();
        $node->path = $token->getPath();
        $node->filter = $token->getFilter();
        $node->attributes = [];
        $node->assignments = [];

        $this->_current = $node;
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
     * @todo Are there other nodes that shouldn't have children?
     *
     * @param IndentToken $token the <indent>-token
     *
     * @throws Exception
     */
    protected function handleIndent(IndentToken $token = null)
    {

        $this->_level++;

        if (!$this->_last)
            return;

        if (in_array($this->_last->type, ['import', 'expression', 'doctype']))
            $this->throwException(
                'The '.$this->_last->type.' instruction can\'t have children',
                $token
            );

        $this->_currentParent = $this->_last;
    }

    /**
     * Handles a <tag>-token and parses it into a tag-node.
     *
     * If no $_current element exists, a new one is created
     * A tag can only exist once on an element
     * Only elements can have tags
     *
     * @todo Maybe multiple tags could combine with :? Would be ugly and senseless to write a(...)b tho
     *
     * @param TagToken $token the <tag>-token
     *
     * @throws Exception
     */
    protected function handleTag(TagToken $token)
    {

        if (!$this->_current)
            $this->_current = $this->createElement();

        if ($this->_current->type !== 'element')
            $this->throwException("Tags can only be used on elements", $token);

        if ($this->_current->tag)
            $this->throwException('This element already has a tag name', $token);

        $this->_current->tag = $token->getName();
    }

    /**
     * Handles a <mixin>-token and parses it into a mixin-node.
     *
     * Mixins can't be inside other mixins.
     * We use $_inMixin and $_mixinLevel for that
     * $_mixinLevel gets reset in handleOutdent
     *
     * @param MixinToken $token the <mixin>-token
     *
     * @throws Exception
     */
    protected function handleMixin(MixinToken $token)
    {

        if ($this->_inMixin)
            $this->throwException(
                "Failed to define mixin: Mixins cant be nested"
            );

        $node = $this->createNode('mixin', $token);
        $node->name = $token->getName();
        $node->attributes = [];
        $node->assignments = [];

        $this->_inMixin = true;
        $this->_mixinLevel = $this->_level;

        $this->_current = $node;
    }

    /**
     * Handles a <mixinCall>-token and parses it into a mixinCall-node.
     *
     * @param MixinCallToken $token the <mixinCall>-token
     */
    protected function handleMixinCall(MixinCallToken $token)
    {

        $node = $this->createNode('mixinCall', $token);
        $node->name = $token->getName();
        $node->attributes = [];
        $node->assignments = [];

        $this->_current = $node;
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
     */
    protected function handleNewLine(NewLineToken $token = null)
    {

        if ($this->_current) {

            //Is there any expansion?
            if ($this->_expansion) {

                //Tell the current element who expands it
                $this->_current->expands = $this->_expansion;
                $this->_expansion = null;
            }

            //Append to current parent
            $this->_currentParent->append($this->_current);
            $this->_last = $this->_current;
            $this->_current = null;
        }
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
     */
    protected function handleOutdent(OutdentToken $token = null)
    {

        $this->_level--;

        $this->_currentParent = $this->_currentParent->parent;

        if ($this->_inMixin && $this->_level <= $this->_mixinLevel) {

            $this->_inMixin = false;
            $this->_mixinLevel = null;
        }
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
     *
     * @throws Exception
     */
    protected function handleExpansion(ExpansionToken $token)
    {

        if (!$this->_current)
            $this->throwException(
                "Expansion needs an element to work on",
                $token
            );

        if ($this->_current->type === 'element' && !$token->hasSpace()) {

            if (!$this->expectNext([TagToken::class])) {
                $this->throwException(
                    sprintf(
                        "Expected tag name or expansion after double colon, "
                        ."%s received",
                        get_class($this->getToken())
                    ),
                    $token
                );
            }

            /** @var TagToken $token */
            $token = $this->getToken();
            $this->_current->tag .= ':'.$token->getName();

            return;
        }

        if ($this->_expansion)
            $this->_current->expands = $this->_expansion;

        $this->_expansion = $this->_current;
        $this->_current = null;
    }


    /**
     * Handles a <text>-token and parses it into a text-node.
     *
     * If there's a $_current element, we append it to that element,
     * if not, it becomes the $_current element
     *
     * @param TextToken $token the <text>-token
     */
    protected function handleText(TextToken $token)
    {

        $node = $this->createNode('text', $token);
        $node->value = $token->getValue();
        $node->level = $token->getLevel();
        $node->escaped = $token->isEscaped();

        if ($this->_current) {

            $this->_current->append($node);
        } else
            $this->_current = $node;
    }

    /**
     * Handles a <when>-token and parses it into a when-node.
     *
     * @param WhenToken $token the <when>-token
     */
    protected function handleWhen(WhenToken $token)
    {

        $node = $this->createNode('when', $token);
        $node->subject = $token->getSubject();
        $node->default = $token->getName() === 'default';
        $this->_current = $node;
    }

    /**
     * Handles a <while>-token and parses it into a while-node.
     *
     * @param WhileToken $token the <while>-token
     */
    protected function handleWhile(WhileToken $token)
    {

        $node = $this->createNode('while', $token);
        $node->subject = $token->getSubject();
        $this->_current = $node;
    }


    /**
     * Handles a <for>-token and parses it into a for-node.
     *
     * @param ForToken $token the <while>-token
     */
    protected function handleFor(ForToken $token)
    {

        $node = $this->createNode('for', $token);
        $node->subject = $token->getSubject();
        $this->_current = $node;
    }


    /**
     * Throws a Parser-Exception.
     *
     * If a related token is passed, it will also append
     * the location in the input of that token
     *
     * @param string     $message      a meaningful error-message
     * @param array|null $relatedToken the token related to this error
     *
     * @throws Exception
     */
    protected function throwException($message, array $relatedToken = null)
    {

        if ($relatedToken)
            $message .= "\n(".$relatedToken['type']
                .' at '.$relatedToken['line']
                .':'.$relatedToken['offset'].')';

        throw new Exception(
            "Failed to parse Jade: $message"
        );
    }
}