<?php

namespace Tale\Test\Jade;

use Tale\Jade\Lexer;
use Tale\Jade\Lexer\Token\AssignmentToken;
use Tale\Jade\Lexer\Token\AttributeEndToken;
use Tale\Jade\Lexer\Token\AttributeStartToken;
use Tale\Jade\Lexer\Token\AttributeToken;
use Tale\Jade\Lexer\Token\DoctypeToken;
use Tale\Jade\LexerException;

class LexerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Lexer
     */
    protected $lexer;

    public function setUp()
    {

        $this->lexer = new Lexer([]);
    }

    public function testAssignmentToken()
    {

        $this->assertTokens(
            $this->lexer->lex('&test'),
            AssignmentToken::class
        );
    }

    public function testAttributes()
    {

        $this->assertTokens(
            $this->lexer->lex('(a=b c=d e=f)'),
            AttributeStartToken::class,
            AttributeToken::class,
            AttributeToken::class,
            AttributeToken::class,
            AttributeEndToken::class
        );

        $this->assertTokens(
            $this->lexer->lex('(a=b,c=d, e=f)'),
            AttributeStartToken::class,
            AttributeToken::class,
            AttributeToken::class,
            AttributeToken::class,
            AttributeEndToken::class
        );

        $this->setExpectedException(LexerException::class);
        $this->lexer->lex('(a=b');
    }

    public function testDoctypeScan()
    {

        $this->assertTokens(
            $this->lexer->lex('doctype 5'),
            DoctypeToken::class
        );

        $this->assertTokens(
            $this->lexer->lex('!!! 5'),
            DoctypeToken::class
        );
    }

    public function assertTokens(\Generator $tokens)
    {

        $args = func_get_args();
        array_shift($args);

        $tokens = iterator_to_array($tokens);

        $this->assertEquals(count($args), count($tokens), 'same amount of tokens');

        foreach ($tokens as $i => $token) {

            $isset = isset($args[$i]);
            $this->assertTrue($isset, "tokens has index $i");

            if ($isset)
                $this->assertInstanceOf($args[$i], $token, "token is {$args[$i]}");
        }
    }
}