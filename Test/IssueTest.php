<?php

namespace Tale\Jade\Test;

use Tale\Jade\Compiler;
use Tale\Jade\Renderer;
use Tale\Jade\Parser;

class IssueTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Renderer */
    private $_renderer;

    public function setUp()
    {

        $this->_renderer = new Renderer([
            'adapter' => 'file',
            'adapterOptions' => [
                'path' => __DIR__.'/cache',
                'lifeTime' => 0
            ],
            'compilerOptions' => [
                'pretty' => false,
                'handleErrors' => false,
                'paths' => [__DIR__.'/views/issues']
            ]
        ]);
    }

    public function testIssue42()
    {

        $this->assertEquals('<a><b><c><d>Some TextSome further text</d></c><e></e></b><f></f></a><g></g><a><b><c><d><e><f><style>Some Text Some further text</style></f></e><e></e></d></c><f></f></b></a><g></g><a><b><c><d><e><f>Some TextSome further text</f></e><e></e></d></c></b><f></f></a><g></g>', $this->_renderer->render(
            'issue-42'
        ));
    }

}