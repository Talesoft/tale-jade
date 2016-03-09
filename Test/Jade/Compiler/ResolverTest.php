<?php

namespace Tale\Test\Jade\Compiler;

use Tale\Jade\Compiler\Resolver;

class ResolverTest extends \PHPUnit_Framework_TestCase
{

    public function testResolving()
    {

        $resolver = new Resolver([__DIR__.'/test']);

        $this->assertEquals(realpath(__DIR__.'/test/test-1.css'), $resolver->resolve('test-1.css', ['css']));
        $this->assertEquals(realpath(__DIR__.'/test/test-1.jade'), $resolver->resolve('test-1.jade', ['jade']));
        $this->assertEquals(realpath(__DIR__.'/test/test-1.css'), $resolver->resolve('test-1', ['css']));
        $this->assertEquals(realpath(__DIR__.'/test/test-1.jade'), $resolver->resolve('test-1', ['jade']));

        $this->assertEquals(realpath(__DIR__.'/test/test-sub/test-2.php'), $resolver->resolve('test-sub/test-2.php', ['php']));
        $this->assertEquals(realpath(__DIR__.'/test/test-sub/test-1.js'), $resolver->resolve('test-sub/test-1.js', ['js']));
        $this->assertEquals(realpath(__DIR__.'/test/test-sub/test-2.php'), $resolver->resolve('test-sub/test-2', ['php']));
        $this->assertEquals(realpath(__DIR__.'/test/test-sub/test-1.js'), $resolver->resolve('test-sub/test-1', ['js']));

        $this->assertEquals(null, $resolver->resolve('test-2', ['jade']));
        $this->assertEquals(null, $resolver->resolve('test-1', ['xyz']));
    }
}