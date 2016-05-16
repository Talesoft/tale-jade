<?php

namespace Tale\Test\Jade;

use Tale\Jade\Compiler;
use Tale\Jade\Renderer;
use Tale\Jade\Parser;

class PrettyTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Renderer */
    private $renderer;

    public function setUp()
    {

        $this->renderer = new Renderer([
            'adapter_options' => [
                'path' => __DIR__.'/cache/pretty',
            ],
            'pretty' => true,
            'paths' => [__DIR__.'/views/pretty']
        ]);
    }

    public function testBasic()
    {

        $phtml = <<<'PHTML'
<!DOCTYPE html>
<html<?php $__value = isset($lang) ? $lang : false; if (!\Tale\Jade\Compiler\is_null_or_false($__value)) echo ' lang='.\Tale\Jade\Compiler\build_value($__value, '"', true); unset($__value);?>>
  <head>
    <title>
      <?=htmlentities(isset($title) ? $title : '', \ENT_QUOTES, 'UTF-8')?>
    </title>
    <link rel="stylesheet" href="/some-style.css">
  </head>
  <body>
    <h1>
      Some Header
    </h1>
    <p>
      Some multiline
      text that will just span
      over as many lines as it fucking likes!
    </p>
    <h2>
      A node with a single zero
    </h2>
    <p>
      0
    </p>
    <script src="/some-script.css"></script>
  </body>
</html>
PHTML;


        $this->assertEquals(str_replace("\r", '', $phtml), $this->renderer->compileFile(
            'basic'
        ));
    }

    public function testSingle()
    {

        $phtml = <<<'PHTML'
<div class="container">
  <div class="row">
    <div class="col-md-6 col-sm-3">
      <p>
        Some content
      </p>
    </div>
    <div class="col-md-6 col-sm-3">
      <p>
        Some content
      </p>
    </div>
    <div class="col-md-6 col-sm-3">
      <p>
        Some content
      </p>
    </div>
  </div>
</div>
PHTML;

        $this->assertEquals(str_replace("\r", '', $phtml), $this->renderer->compileFile(
            'single'
        ));
    }

}