<?php

namespace Tale\Test\Jade;

use Tale\Jade\Compiler;
use Tale\Jade\Renderer;
use Tale\Jade\Parser;

class DoctypeTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Renderer */
    private $renderer;

    public function setUp()
    {

        $this->renderer = new Renderer([
            'adapter_options' => [
                'path' => __DIR__.'/cache/doctypes'
            ],
            'pretty' => false,
            'paths' => [__DIR__.'/views/doctypes']
        ]);
    }

    public function testXmlDoctype()
    {

        $this->assertEquals('<?xml version="1.0" encoding="utf-8"?><!-- these should be (self)-closed --><hr /><img /><link /><area /><!-- These shouldn\'t repeat --><a disabled="" selected="" checked="">Some link</a><!-- this should self-close --><some-element /><!-- this shouldn\'t self-close --><some-element>Some Content</some-element>', $this->renderer->render(
            'xml'
        ));
    }

    public function testHtmlDoctype()
    {

        $this->assertEquals('<!DOCTYPE html><!-- these should be left open --><hr><img><link><area><!-- These shouldn\'t repeat --><a disabled selected checked>Some link</a><!-- this should self-close --><some-element></some-element><!-- this shouldn\'t self-close --><some-element>Some Content</some-element>', $this->renderer->render(
            'html'
        ));
    }

    public function testXhtmlDoctype()
    {

        $this->assertEquals('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"><!-- these should (self)-close --><hr /><img /><link /><area /><!-- These should repeat --><a disabled="disabled" selected="selected" checked="checked">Some link</a><!-- this should self-close --><some-element></some-element><!-- this shouldn\'t self-close --><some-element>Some Content</some-element>', $this->renderer->render(
            'xhtml'
        ));
    }
}