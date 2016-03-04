<?php

namespace Tale\Jade\Parser;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\TokenInterface;
use Tale\Jade\Parser;
use Tale\Jade\Parser\Node\DocumentNode;
use Tale\Jade\ParserException;
use Tale\Jade\Util\LevelTrait;

class State
{
    use LevelTrait;

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
    private $documentNode;

    /**
     * The parent that currently found childs are appended to.
     *
     * When an <outdent>-token is encountered, it moves one parent up
     * ($_currentParent->parent becomes the new $_currentParent)
     *
     * @var Node
     */
    private $parentNode;

    /**
     * The current element in the queue.
     *
     * Will be appended to $_currentParent when a <newLine>-token is encountered
     * It will become the current parent, if an <indent>-token is encountered
     *
     * @var Node
     */
    private $currentNode;

    /**
     * The last element that was completely put together.
     *
     * Will be set on a <newLine>-token ($_current will become last)
     *
     * @var Node
     */
    private $lastNode;

    /**
     * Stores an expanded node to attach it to the expanding node later.
     *
     * @var Node
     */
    private $outerNode;

    public function __construct(\Generator $tokens)
    {

        $this->setLevel(0);
        $this->tokens = $tokens;
        $this->documentNode = $this->createNode(DocumentNode::class);
        $this->parentNode = $this->documentNode;
        $this->currentNode = null;
        $this->lastNode = null;
        $this->outerNode = null;

        //Fix HHVM generators needing ->next() before ->current()
        //This will actually work as expected, no node will be skipped
        //HHVM always needs a first ->next() (I don't know if this is a bug or
        //expected behaviour)
        if (defined('HHVM_VERSION'))
            $this->tokens->next();
    }

    /**
     * @return \Generator
     */
    public function getTokens()
    {

        return $this->tokens;
    }

    /**
     * @param \Generator $tokens
     * @return $this
     */
    public function setTokens($tokens)
    {

        $this->tokens = $tokens;

        return $this;
    }

    /**
     * @return DocumentNode
     */
    public function getDocumentNode()
    {

        return $this->documentNode;
    }

    /**
     * @return Node
     */
    public function getParentNode()
    {

        return $this->parentNode;
    }

    /**
     * @param Node $currentParent
     *
     * @return $this
     */
    public function setParentNode($currentParent)
    {

        $this->parentNode = $currentParent;

        return $this;
    }

    /**
     * @return Node
     */
    public function getCurrentNode()
    {

        return $this->currentNode;
    }

    /**
     * @param Node $current
     *
*@return $this
     */
    public function setCurrentNode($current)
    {

        $this->currentNode = $current;

        return $this;
    }

    /**
     * @return Node
     */
    public function getLastNode()
    {

        return $this->lastNode;
    }

    /**
     * @param Node $last
     *
*@return $this
     */
    public function setLastNode($last)
    {

        $this->lastNode = $last;

        return $this;
    }

    /**
     * @return Node
     */
    public function getOuterNode()
    {

        return $this->outerNode;
    }

    /**
     * @param Node $node
     *
*@return $this
     */
    public function setOuterNode(Node $node)
    {

        $this->outerNode = $node;

        return $this;
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
    public function lookUp(array $types)
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
    public function lookUpNext(array $types)
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
    public function expect(array $types)
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
    public function expectNext(array $types)
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
    public function hasTokens()
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
    public function nextToken()
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
    public function getToken()
    {

        return $this->tokens->current();
    }

    public function is(Node $node, array $classNames)
    {

        foreach ($classNames as $className)
            if (is_a($node, $className))
                return true;

        return false;
    }

    public function currentNodeIs(array $classNames)
    {

        if (!$this->currentNode)
            return false;

        return $this->is($this->currentNode, $classNames);
    }

    public function lastNodeIs(array $classNames)
    {

        if (!$this->lastNode)
            return false;

        return $this->is($this->lastNode, $classNames);
    }

    public function parentNodeIs(array $classNames)
    {

        if (!$this->parentNode)
            return false;

        return $this->is($this->parentNode, $classNames);
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
     * @param string $className  the type the node should have
     * @param TokenInterface $token the token to relate this node to
     *
     * @return Node The newly created node
     */
    public function createNode($className, TokenInterface $token = null)
    {

        if (!is_subclass_of($className, Node::class))
            throw new \InvalidArgumentException(
                "$className is not a valid token class"
            );

        return new $className(
            $token ? $token->getLine() : null,
            $token ? $token->getOffset() : null,
            $token ? $token->getLevel() : null
        );
    }

    public function enter()
    {

        $this->increaseLevel();

        if (!$this->lastNode)
            return $this;

        $this->parentNode = $this->lastNode;

        return $this;
    }

    public function leave()
    {

        $this->decreaseLevel();

        if (!$this->parentNode->getParent())
            $this->throwException(
                "Failed to outdent: No parent to outdent to. "
                ."Seems the parser moved out too many levels."
            );

        $this->parentNode = $this->parentNode->getParent();
    }

    public function store()
    {

        if (!$this->currentNode)
            return $this;


        //Is there any expansion?
        if ($this->outerNode) {

            //Store outer node on current node for expansion
            $this->currentNode->setOuterNode($this->outerNode);
            $this->outerNode = null;
        }

        //Append to current parent
        $this->parentNode->appendChild($this->currentNode);
        $this->lastNode = $this->getCurrentNode();
        $this->currentNode = null;

        return $this;
    }

    /**
     * Throws a parser-exception.
     *
     * The current line and offset of the exception
     * get automatically appended to the message
     *
     * @param string $message A meaningful error message
     * @param TokenInterface $relatedToken
     *
     * @throws ParserException
     */
    public function throwException($message, TokenInterface $relatedToken = null)
    {

        $pattern = "Failed to parse: %s \nToken: %s \nLine: %s \nOffset: %s";

        throw new ParserException(vsprintf($pattern, [
            $message,
            $relatedToken ?: null,
            $relatedToken ? $relatedToken->getLine() : '',
            $relatedToken ? $relatedToken->getOffset() : '',
        ]));
    }
}