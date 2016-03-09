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

    public function testAssignmentScan()
    {

        $this->assertTokens(
            $this->lexer->lex('&test'),
            AssignmentToken::class
        );
    }

    public function testAttributeScan()
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

        $this->assertTokens(
            $this->lexer->lex('(a=b
        c=d     e=f
        //ignored line
    ,g=h        )'),
            AttributeStartToken::class,
            AttributeToken::class,
            AttributeToken::class,
            AttributeToken::class,
            AttributeToken::class,
            AttributeEndToken::class
        );


        $this->assertTokens(
            $this->lexer->lex('(
                a//ignore
                b //ignore
                c//ignore
                =d
                e=//ignore
                f//ignore
                g=h//ignore
            )'),
            AttributeStartToken::class,
            AttributeToken::class,
            AttributeToken::class,
            AttributeToken::class,
            AttributeToken::class,
            AttributeToken::class,
            AttributeEndToken::class
        );

        $this->expectException(LexerException::class);
        iterator_to_array($this->lexer->lex('(a=b'));
    }

    public function testAttributeDetailScan()
    {

        /** @var AttributeToken $attr */
        $attr = iterator_to_array($this->lexer->lex('(a=b)'))[1];
        $this->assertEquals('a', $attr->getName());
        $this->assertEquals('b', $attr->getValue());
        $this->assertEquals(true, $attr->isEscaped());
        $this->assertEquals(true, $attr->isChecked());

        /** @var AttributeToken $attr */
        $attr = iterator_to_array($this->lexer->lex('(a!=b)'))[1];
        $this->assertEquals('a', $attr->getName());
        $this->assertEquals('b', $attr->getValue());
        $this->assertEquals(false, $attr->isEscaped());
        $this->assertEquals(true, $attr->isChecked());

        /** @var AttributeToken $attr */
        $attr = iterator_to_array($this->lexer->lex('(a?=b)'))[1];
        $this->assertEquals('a', $attr->getName());
        $this->assertEquals('b', $attr->getValue());
        $this->assertEquals(true, $attr->isEscaped());
        $this->assertEquals(false, $attr->isChecked());

        /** @var AttributeToken $attr */
        $attr = iterator_to_array($this->lexer->lex('(a?!=b)'))[1];
        $this->assertEquals('a', $attr->getName());
        $this->assertEquals('b', $attr->getValue());
        $this->assertEquals(false, $attr->isEscaped());
        $this->assertEquals(false, $attr->isChecked());
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