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
 * @copyright  Copyright (c) 2015 Torben Köhn (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.3
 * @link       http://jade.talesoft.io/docs/files/Parser.html
 * @since      File available since Release 1.0
 */

namespace Tale\Jade;

use Tale\ConfigurableTrait;
use Tale\Jade\Parser\Node;
use Tale\Jade\Parser\Exception;

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
 * @copyright  Copyright (c) 2015 Torben Köhn (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.3
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
    private $lexer;

    /**
     * The level we're currently on.
     *
     * This does not equal the Lexer-level or Compiler-level,
     * it's an internal level to get the child/parent-relation between
     * nodes right
     *
     * @var int
     */
    private $level;

    /**
     * The Generator returned by the ->lex() method of the lexer.
     *
     * @var \Generator
     */
    private $tokens;

    /**
     * The root node of the currently parsed document.
     *
     * @var Node
     */
    private $document;

    /**
     * The parent that currently found childs are appended to.
     *
     * When an <outdent>-token is encountered, it moves one parent up
     * ($_currentParent->parent becomes the new $_currentParent)
     *
     * @var Node
     */
    private $currentParent;

    /**
     * The current element in the queue.
     *
     * Will be appended to $_currentParent when a <newLine>-token is encountered
     * It will become the current parent, if an <indent>-token is encountered
     *
     * @var Node
     */
    private $current;

    /**
     * The last element that was completely put together.
     *
     * Will be set on a <newLine>-token ($_current will become last)
     *
     * @var Node
     */
    private $last;

    /**
     * States if we're in a mixin or not.
     *
     * Used to check for the mixin-block and nested mixins
     *
     * @var bool
     */
    private $inMixin;

    /**
     * The level we're on inside a mixin.
     *
     * Used to check for the mixin-block and nested mixins
     *
     * @var int
     */
    private $mixinLevel;

    /**
     * Stores an expanded node to attach it to the expanding node later.
     *
     * @var Node
     */
    private $expansion;


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

        $this->defineOptions(['lexer_options' => []], $options);

        $this->lexer = $lexer ? $lexer : new Lexer($this->options['lexer_options']);
    }

    /**
     * Returns the currently used Lexer instance.
     *
     * @return Lexer
     */
    public function getLexer()
    {

        return $this->lexer;
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

        $this->level = 0;
        $this->tokens = $this->lexer->lex($input);
        $this->document = $this->createNode('document', ['line' => 0, 'offset' => 0]);
        $this->currentParent = $this->document;
        $this->current = null;
        $this->last = null;
        $this->inMixin = false;
        $this->mixinLevel = null;
        $this->expansion = null;

        //Fix HHVM generators needing ->next() before ->current()
        //This will actually work as expected, no node will be skipped
        //HHVM always needs a first ->next() (I don't know if this is a bug or
        //expected behaviour)
        if (defined('HHVM_VERSION')) {

            $this->tokens->next();
        }

        //While we have tokens, handle current token, then go to next token
        //rinse and repeat
        while ($this->hasTokens()) {

            $this->handleToken();
            $this->nextToken();
        }

        //Return the final document node with all its awesome child nodes
        return $this->document;
    }

    /**
     * Handles any kind of token returned by the lexer dynamically.
     *
     * The token-type will be translated into a method name
     * e.g.
     *
     * newLine      => handleNewLine
     * attribute    => handleAttribute
     * tag          => handleTag
     *
     * First argument of that method will always be the token array
     *
     * If no token is passed, it will take the current token
     * in the lexer's token generator
     *
     * @param array|null $token a token or the current lexer's generator token
     *
     * @throws Exception when no token handler has been found
     */
    protected function handleToken(array $token = null)
    {

        $token = $token ? $token : $this->getToken();

        //Put together the method name
        $method = 'handle'.ucfirst($token['type']);

        //If the token has no handler, we throw an error
        if (!method_exists($this, $method)) {
            $this->throwException(
                "Unexpected token `{$token['type']}`, no handler $method found",
                $token
            );
        } else {

            //Call the handler method and pass the token array as the first argument
            call_user_func([$this, $method], $token);
        }
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
            if (in_array($token['type'], $types, true))
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
     * Throws an exception if the next token is not a newLine token.
     *
     * This states that "a line of instructions should end here"
     *
     * Notice that if the next token is _not_ a newLine, it gets
     * handled through handleToken automatically
     *
     * @param array|null $relatedToken the token to relate the exception to
     *
     * @throws Exception when the next token is not a newLine token
     */
    protected function expectEnd(array $relatedToken = null)
    {

        foreach ($this->lookUpNext(['newLine']) as $token) {

            $this->handleToken($token);

            return;
        }

        if (!$this->expectNext(['newLine'])) {

            $this->throwException(
                "The statement should end here.",
                $relatedToken
            );
        } else
            $this->handleToken();
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

        return $this->tokens->valid();
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

        $this->tokens->next();

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

        return $this->tokens->current();
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
     * @param array|null $token the token to relate this node to
     *
     * @return Node The newly created node
     */
    protected function createNode($type, array $token = null)
    {

        $token = $token ? $token : ['line' => $this->lexer->getLine(), 'offset' => $this->lexer->getOffset()];
        $node = new Node($type, $token['line'], $token['offset']);

        return $node;
    }

    /**
     * Creates an element-node with the properties it should have consistently.
     *
     * This will create the following properties on the Node instance:
     *
     * @todo Do this for a bunch of other elements as well, maybe all, maybe a centralized way?
     *
     * @param array|null $token the token to relate this element to
     *
     * @return Node the newly created element-node
     */
    protected function createElement(array $token = null)
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
     * @param array $token the <assignment>-token
     *
     * @throws Exception
     */
    protected function handleAssignment(array $token)
    {

        if (!$this->current)
            $this->current = $this->createElement();

        if (!in_array($this->current->type, ['element', 'mixinCall']))
            $this->throwException(
                "Assignments can only happen on elements and mixinCalls"
            );

        $node = $this->createNode('assignment', $token);
        $node->name = $token['name'];
        $this->current->assignments[] = $node;

        if ($this->expectNext(['attributeStart'])) {

            $element = $this->current;
            $this->current = $node;
            $this->handleToken();
            $this->current = $element;
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
     * @param array $token the <attribute>-token
     *
     * @throws Exception
     */
    protected function handleAttribute(array $token)
    {

        if (!$this->current)
            $this->current = $this->createElement();

        $node = $this->createNode('attribute', $token);
        $node->name = $token['name'];
        $node->value = $token['value'];
        $node->escaped = $token['escaped'];
        $node->unchecked = $token['unchecked'];

        if (!$node->name && in_array($this->current->type, ['element', 'mixin']))
            $this->throwException('Attributes in elements and mixins need a name', $token);

        if ($this->current->type === 'mixinCall' && !$node->value) {

            $node->value = $node->name;
            $node->name = null;
        }

        $this->current->attributes[] = $node;
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
     * @param array $token the <attributeStart>-token
     *
     * @throws Exception
     */
    protected function handleAttributeStart(array $token)
    {

        if (!$this->current)
            $this->current = $this->createElement();

        if (!in_array($this->current->type, ['element', 'assignment', 'import', 'variable', 'mixin', 'mixinCall']))
            $this->throwException(
                "Attributes can only be placed on element, assignment, import, variable, mixin and mixinCall"
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

    /**
     * Handles an <attributeEnd>-token.
     *
     * It does nothing (right now?)
     *
     * @param array $token the <attributeEnd>-token
     */
    protected function handleAttributeEnd(array $token)
    {

    }

    /**
     * Handles a <block>-token and parses it into a block-node.
     *
     * Blocks outside a mixin always need a name! (That's what $_inMixin is for)
     *
     * @param array $token the <block>-token
     *
     * @throws Exception
     */
    protected function handleBlock(array $token)
    {

        $node = $this->createNode('block', $token);
        $node->name = isset($token['name']) ? $token['name'] : null;
        $node->mode = isset($token['mode']) ? $token['mode'] : null;

        if (!$node->name && !$this->inMixin)
            $this->throwException(
                "Blocks outside a mixin always need a name"
            );

        $this->current = $node;

        $this->expectEnd($token);
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
     * @param array $token the <class>-token
     *
     * @throws Exception
     */
    protected function handleClass(array $token)
    {

        if (!$this->current)
            $this->current = $this->createElement();

        if (!in_array($this->current->type, ['element', 'mixinCall']))
            $this->throwException("Classes can only be used on elements and mixin calls", $token);

        $attr = $this->createNode('attribute', $token);
        $attr->name = 'class';
        $attr->value = $token['name'];
        $attr->escaped = false;
        $this->current->attributes[] = $attr;
    }

    /**
     * Handles a <comment>-token and parses it into a comment-node.
     *
     * The comment node is set as the $_current element
     *
     * @param array $token the <comment>-token
     */
    protected function handleComment(array $token)
    {

        $node = $this->createNode('comment', $token);
        $node->rendered = $token['rendered'];

        $this->current = $node;
    }

    /**
     * Handles a <case>-token and parses it into a case-node.
     *
     * @param array $token the <case>-token
     */
    protected function handleCase(array $token)
    {

        $node = $this->createNode('case', $token);
        $node->subject = $token['subject'];
        $this->current = $node;
    }

    /**
     * Handles a <conditional>-token and parses it into a conditional-node.
     *
     * @param array $token the <conditional>-token
     */
    protected function handleConditional(array $token)
    {

        $node = $this->createNode('conditional', $token);
        $node->subject = $token['subject'];
        $node->conditionType = $token['conditionType'];

        $this->current = $node;
    }

    /**
     * Handles a <do>-token and parses it into a do-node.
     *
     * @param array $token the <do>-token
     */
    protected function handleDo(array $token)
    {

        $node = $this->createNode('do', $token);
        $this->current = $node;
    }

    /**
     * Handles a <doctype>-token and parses it into a doctype-node.
     *
     * @param array $token the <doctype>-token
     */
    protected function handleDoctype(array $token)
    {

        $node = $this->createNode('doctype', $token);
        $node->name = $token['name'];

        $this->current = $node;
    }

    /**
     * Handles an <each>-token and parses it into an each-node.
     *
     * @param array $token the <each>-token
     */
    protected function handleEach(array $token)
    {

        $node = $this->createNode('each', $token);
        $node->subject = $token['subject'];
        $node->itemName = $token['itemName'];
        $node->keyName = isset($token['keyName']) ? $token['keyName'] : null;

        $this->current = $node;
    }

    /**
     * Handles an <expression>-token into an expression-node.
     *
     * If there's a $_current-element, the expression gets appended
     * to the $_current-element. If not, the expression itself
     * becomes the $_current element
     *
     * @param array $token the <expression>-token
     *
     * @throws Exception
     */
    protected function handleExpression(array $token)
    {

        $node = $this->createNode('expression', $token);
        $node->escaped = $token['escaped'];
        $node->unchecked = $token['unchecked'];
        $node->value = $token['value'];

        if ($this->current)
            $this->current->append($node);
        else
            $this->current = $node;
    }

    /**
     * Handles an <code>-token into an code-node.
     *
     * @param array $token the <code>-token
     *
     * @throws Exception
     */
    protected function handleCode(array $token)
    {

        $node = $this->createNode('code', $token);
        $node->value = $token['value'];
        $node->block = $token['block'];

        $this->current = $node;
    }

    /**
     * Handles a <filter>-token and parses it into a filter-node.
     *
     * @param array $token the <filter>-token
     */
    protected function handleFilter(array $token)
    {

        $node = $this->createNode('filter', $token);
        $node->name = $token['name'];
        $this->current = $node;
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
     * @param array $token the <id>-token
     *
     * @throws Exception
     */
    protected function handleId(array $token)
    {

        if (!$this->current)
            $this->current = $this->createElement();

        if (!in_array($this->current->type, ['element', 'mixinCall']))
            $this->throwException("IDs can only be used on elements and mixin calls", $token);

        $attr = $this->createNode('attribute', $token);
        $attr->name = 'id';
        $attr->value = $token['name'];
        $attr->escaped = false;
        $this->current->attributes[] = $attr;
    }

    /**
     * Handles a <variable>-token and parses it into a variable assignment.
     *
     * @param array $token the <variable>-token
     *
     * @throws Exception
     */
    protected function handleVariable(array $token)
    {

        $node = $this->createNode('variable');
        $node->name = $token['name'];
        $node->attributes = [];
        $this->current = $node;
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
     * @param array $token the <import>-token
     *
     * @throws Exception
     */
    protected function handleImport(array $token)
    {

        if ($token['importType'] === 'extends' && count($this->document->children) > 0)
            $this->throwException(
                "extends should be the very first statement in a document",
                $token
            );

        $node = $this->createNode('import', $token);
        $node->importType = $token['importType'];
        $node->path = $token['path'];
        $node->filter = $token['filter'];
        $node->attributes = [];
        $node->assignments = [];

        $this->current = $node;
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
     * @param array|null $token the <indent>-token
     *
     * @throws Exception
     */
    protected function handleIndent(array $token = null)
    {

        $this->level++;

        if (!$this->last)
            return;

        if (in_array($this->last->type, ['import', 'expression', 'doctype']))
            $this->throwException(
                'The '.$this->last->type.' instruction can\'t have children',
                $token
            );

        $this->currentParent = $this->last;
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
     * @param array $token the <tag>-token
     *
     * @throws Exception
     */
    protected function handleTag(array $token)
    {

        if (!$this->current)
            $this->current = $this->createElement();

        if ($this->current->type !== 'element')
            $this->throwException("Tags can only be used on elements", $token);

        if ($this->current->tag)
            $this->throwException('This element already has a tag name', $token);

        $this->current->tag = $token['name'];
    }

    /**
     * Handles a <mixin>-token and parses it into a mixin-node.
     *
     * Mixins can't be inside other mixins.
     * We use $_inMixin and $_mixinLevel for that
     * $_mixinLevel gets reset in handleOutdent
     *
     * @param array $token the <mixin>-token
     *
     * @throws Exception
     */
    protected function handleMixin(array $token)
    {

        if ($this->inMixin)
            $this->throwException(
                "Failed to define mixin: Mixins cant be nested"
            );

        $node = $this->createNode('mixin', $token);
        $node->name = $token['name'];
        $node->attributes = [];
        $node->assignments = [];

        $this->inMixin = true;
        $this->mixinLevel = $this->level;

        $this->current = $node;
    }

    /**
     * Handles a <mixinCall>-token and parses it into a mixinCall-node.
     *
     * @param array $token the <mixinCall>-token
     */
    protected function handleMixinCall(array $token)
    {

        $node = $this->createNode('mixinCall', $token);
        $node->name = $token['name'];
        $node->attributes = [];
        $node->assignments = [];

        $this->current = $node;
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
     * @param array|null $token the <newLine>-token or null
     */
    protected function handleNewLine()
    {

        if ($this->current) {

            //Is there any expansion?
            if ($this->expansion) {

                //Tell the current element who expands it
                $this->current->expands = $this->expansion;
                $this->expansion = null;
            }

            //Append to current parent
            $this->currentParent->append($this->current);
            $this->last = $this->current;
            $this->current = null;
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
     * @param array|null $token the <outdent>-token
     */
    protected function handleOutdent()
    {

        $this->level--;

        $this->currentParent = $this->currentParent->parent;

        if ($this->inMixin && $this->level <= $this->mixinLevel) {

            $this->inMixin = false;
            $this->mixinLevel = null;
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
     * @param array $token the <expansion>-token
     *
     * @throws Exception
     */
    protected function handleExpansion(array $token)
    {

        if (!$this->current)
            $this->throwException(
                "Expansion needs an element to work on",
                $token
            );

        if ($this->current->type === 'element' && !$token['withSpace']) {

            if (!$this->expectNext(['tag'])) {
                $this->throwException(
                    sprintf(
                        "Expected tag name or expansion after double colon, "
                        ."%s received",
                        $this->getToken()['type']
                    ),
                    $token
                );
            }

            $token = $this->getToken();
            $this->current->tag .= ':'.$token['name'];

            return;
        }

        if ($this->expansion)
            $this->current->expands = $this->expansion;

        $this->expansion = $this->current;
        $this->current = null;
    }


    /**
     * Handles a <text>-token and parses it into a text-node.
     *
     * If there's a $_current element, we append it to that element,
     * if not, it becomes the $_current element
     *
     * @param array $token the <text>-token
     */
    protected function handleText(array $token)
    {

        $node = $this->createNode('text', $token);
        $node->value = $token['value'];
        $node->level = $token['level'];
        $node->escaped = $token['escaped'];

        if ($this->current) {

            $this->current->append($node);
        } else
            $this->current = $node;
    }

    /**
     * Handles a <when>-token and parses it into a when-node.
     *
     * @param array $token the <when>-token
     */
    protected function handleWhen(array $token)
    {

        $node = $this->createNode('when', $token);
        $node->subject = $token['subject'];
        $node->default = $token['default'];
        $this->current = $node;
    }

    /**
     * Handles a <while>-token and parses it into a while-node.
     *
     * @param array $token the <while>-token
     */
    protected function handleWhile(array $token)
    {

        $node = $this->createNode('while', $token);
        $node->subject = $token['subject'];
        $this->current = $node;
    }


    /**
     * Handles a <for>-token and parses it into a for-node.
     *
     * @param array $token the <while>-token
     */
    protected function handleFor(array $token)
    {

        $node = $this->createNode('for', $token);
        $node->subject = $token['subject'];
        $this->current = $node;
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