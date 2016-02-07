<?php

namespace Tale\Jade\Parser;

use Tale\Jade\Compiler\Exception;
use Tale\Jade\Lexer;
use Tale\Jade\Lexer\TokenInterface;
use Tale\Jade\Parser\Node\DocumentNode;
use Tale\Jade\Util\LevelTrait;

class State
{
    use LevelTrait;

    /**
     * The Generator returned by the ->lex() method of the lexer.
     *
     * @var \Generator
     */
    private $_tokens;

    /**
     * The root node of the currently parsed document.
     *
     * @var NodeInterface
     */
    private $_documentNode;

    /**
     * The parent that currently found childs are appended to.
     *
     * When an <outdent>-token is encountered, it moves one parent up
     * ($_currentParent->parent becomes the new $_currentParent)
     *
     * @var NodeInterface
     */
    private $_parentNode;

    /**
     * The current element in the queue.
     *
     * Will be appended to $_currentParent when a <newLine>-token is encountered
     * It will become the current parent, if an <indent>-token is encountered
     *
     * @var NodeInterface
     */
    private $_currentNode;

    /**
     * The last element that was completely put together.
     *
     * Will be set on a <newLine>-token ($_current will become last)
     *
     * @var NodeInterface
     */
    private $_lastNode;

    /**
     * Stores an expanded node to attach it to the expanding node later.
     *
     * @var NodeInterface
     */
    private $_outerNode;

    public function __construct(\Generator $tokens)
    {

        $this->setLevel(0);
        $this->_tokens = $tokens;
        $this->_documentNode = $this->createNode(DocumentNode::class);
        $this->_parentNode = $this->_documentNode;
        $this->_currentNode = null;
        $this->_lastNode = null;
        $this->_inMixin = false;
        $this->_mixinLevel = null;
        $this->_outerNode = null;

        //Fix HHVM generators needing ->next() before ->current()
        //This will actually work as expected, no node will be skipped
        //HHVM always needs a first ->next() (I don't know if this is a bug or
        //expected behaviour)
        if (defined('HHVM_VERSION'))
            $this->_tokens->next();
    }

    /**
     * @return \Generator
     */
    public function getTokens()
    {

        return $this->_tokens;
    }

    /**
     * @param \Generator $tokens
     * @return $this
     */
    public function setTokens($tokens)
    {

        $this->_tokens = $tokens;

        return $this;
    }

    /**
     * @return NodeInterface
     */
    public function getDocumentNode()
    {

        return $this->_documentNode;
    }

    /**
     * @param NodeInterface $document
     * @return $this
     */
    public function setDocumentNode($document)
    {

        $this->_documentNode = $document;

        return $this;
    }

    /**
     * @return NodeInterface
     */
    public function getParentNode()
    {

        return $this->_parentNode;
    }

    /**
     * @param NodeInterface $currentParent
     * @return $this
     */
    public function setParentNode($currentParent)
    {

        $this->_parentNode = $currentParent;

        return $this;
    }

    /**
     * @return NodeInterface
     */
    public function getCurrentNode()
    {

        return $this->_currentNode;
    }

    /**
     * @param NodeInterface $current
     * @return $this
     */
    public function setCurrentNode($current)
    {

        $this->_currentNode = $current;

        return $this;
    }

    /**
     * @return NodeInterface
     */
    public function getLastNode()
    {

        return $this->_lastNode;
    }

    /**
     * @param NodeInterface $last
     * @return $this
     */
    public function setLastNode($last)
    {

        $this->_lastNode = $last;

        return $this;
    }

    /**
     * @return NodeInterface
     */
    public function getOuterNode()
    {

        return $this->_outerNode;
    }

    /**
     * @param NodeInterface $node
     * @return $this
     */
    public function setOuterNode(NodeInterface $node)
    {

        $this->_outerNode = $node;

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
     * @return NodeInterface|null
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
     * @return NodeInterface|null
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
    public function nextToken()
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
    public function getToken()
    {

        return $this->_tokens->current();
    }

    public function is(NodeInterface $node, array $classNames)
    {

        foreach ($classNames as $className)
            if ($node->is($className))
                return true;

        return false;
    }

    public function currentNodeIs(array $classNames)
    {

        if (!$this->_currentNode)
            return false;

        return $this->is($this->_currentNode, $classNames);
    }

    public function lastNodeIs(array $classNames)
    {

        if (!$this->_lastNode)
            return false;

        return $this->is($this->_lastNode, $classNames);
    }

    public function parentNodeIs(array $classNames)
    {

        if (!$this->_parentNode)
            return false;

        return $this->is($this->_parentNode, $classNames);
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
     * @return NodeInterface The newly created node
     */
    public function createNode($className, TokenInterface $token = null)
    {

        if (!is_subclass_of($className, NodeInterface::class))
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

        if (!$this->_lastNode)
            return $this;

        $this->_parentNode = $this->_lastNode;

        return $this;
    }

    public function leave()
    {

        $this->decreaseLevel();

        if (!$this->_parentNode->getParent())
            $this->throwException(
                "Failed to outdent: No parent to outdent to. "
                ."Seems the parser moved out too many levels."
            );

        $this->_parentNode = $this->_parentNode->getParent();
    }

    public function store()
    {

        if (!$this->_currentNode)
            return $this;


        //Is there any expansion?
        if ($this->_outerNode) {

            //Store outer node on current node for expansion
            $this->_currentNode->setOuterNode($this->_outerNode);
            $this->_outerNode = null;
        }

        //Append to current parent
        $this->_parentNode->appendChild($this->_currentNode);
        $this->_lastNode = $this->getCurrentNode();
        $this->_currentNode = null;

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
     * @throws Exception
     */
    public function throwException($message, TokenInterface $relatedToken = null)
    {

        $pattern = "Failed to parse: %s \nToken: %s \nLine: %s \nOffset: %s";

        throw new Exception(vsprintf($pattern, [
            $message,
            $relatedToken ? $relatedToken : null,
            $relatedToken ? $relatedToken->getLine() : '',
            $relatedToken ? $relatedToken->getOffset() : '',
        ]));
    }
}