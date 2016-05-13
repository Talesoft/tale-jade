<?php

namespace Tale\Test\Jade;

use Tale\Jade\Compiler;
use Tale\Jade\Renderer;

class ConditionalTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Renderer */
    private $renderer;

    public function setUp()
    {

        $this->renderer = new Renderer([
            'adapter_options' => [
                'path' => __DIR__.'/cache/conditionals'
            ],
            'pretty' => false,
            'paths' => [__DIR__.'/views/conditionals']
        ]);
    }

    public function testIfCompilation()
    {

        $this->assertEquals('<?php if (isset($something) ? $something : false) {?><p>Do something</p><?php }?>', $this->renderer->compile('if $something
    p Do something'));
    }

    public function testIfRendering()
    {

        $this->assertEquals(
            '<p>1 This should be printed</p><p>4 This should be printed</p><p>5 This should be printed</p><p>6 This should be printed</p><p>9 This should be printed</p>',
            $this->renderer->render('if', ['condition' => true, 'negativeCondition' => false])
        );
    }

    public function testUnlessCompilation()
    {

        $this->assertEquals('<?php if (!(isset($something) ? $something : false)) {?><p>Do something</p><?php }?>', $this->renderer->compile('unless $something
    p Do something'));
    }

    public function testUnlessRendering()
    {

        $this->assertEquals(
            '<p>2 This should be printed</p><p>3 This should be printed</p>',
            $this->renderer->render('unless', ['condition' => true, 'negativeCondition' => false])
        );
    }

    public function testIfElseCompilation()
    {

        $this->assertEquals(
            '<?php if (isset($something) ? $something : false) {?><p>Do something</p><?php } else {?><p>Do some other thing</p><?php }?>',
            $this->renderer->compile('if $something
    p Do something
else
    p Do some other thing'));
    }

    public function testIfElseRendering()
    {

        $this->assertEquals(
            '<p>1 This should be printed</p><p>4 This should be printed</p>',
            $this->renderer->render('if-else', ['condition' => true, 'negativeCondition' => false])
        );
    }



    public function testIssue19()
    {

        $jade = <<<JADE
div
  -
    \$menuItems[] = ['label' => 'Issues',
    'url' => ['/issue/index']]
  if (Yii::\$app->user->isGuest)
    -
      \$menuItems[] = ['label' => 'Login',
      'url' => ['/site/login']]
    -\$menuItems[] = ['label' => 'Users', 'url' => ['/user/index']]
  else
    -\$menuItems[] = ['label' => 'Gii', 'url' => ['/gii']]
JADE;


        $this->assertEquals(
            '<div><?php $menuItems[] = [\'label\' => \'Issues\',\'url\' => [\'/issue/index\']]?><?php if (Yii::$app->user->isGuest) {?><?php $menuItems[] = [\'label\' => \'Login\',\'url\' => [\'/site/login\']]?><?php $menuItems[] = [\'label\' => \'Users\', \'url\' => [\'/user/index\']]?><?php } else {?><?php $menuItems[] = [\'label\' => \'Gii\', \'url\' => [\'/gii\']]?><?php }?></div>',
            $this->renderer->compile($jade),
            '2-spaces'
        );


        //Also testing again with 4-space indentation
        $jade = <<<JADE
div
    -
        \$menuItems[] = ['label' => 'Issues',
        'url' => ['/issue/index']]
    if (Yii::\$app->user->isGuest)
        -
            \$menuItems[] = ['label' => 'Login',
            'url' => ['/site/login']]
        -\$menuItems[] = ['label' => 'Users', 'url' => ['/user/index']]
    else
        -\$menuItems[] = ['label' => 'Gii', 'url' => ['/gii']]
JADE;

        $this->assertEquals(
            '<div><?php $menuItems[] = [\'label\' => \'Issues\',\'url\' => [\'/issue/index\']]?><?php if (Yii::$app->user->isGuest) {?><?php $menuItems[] = [\'label\' => \'Login\',\'url\' => [\'/site/login\']]?><?php $menuItems[] = [\'label\' => \'Users\', \'url\' => [\'/user/index\']]?><?php } else {?><?php $menuItems[] = [\'label\' => \'Gii\', \'url\' => [\'/gii\']]?><?php }?></div>',
            $this->renderer->compile($jade),
            '4-spaces'
        );



        //Also testing again with tab indentation
        $jade = "
div
\t-
\t\t\$menuItems[] = ['label' => 'Issues',
\t\t'url' => ['/issue/index']]
\tif (Yii::\$app->user->isGuest)
\t\t-
\t\t\t\$menuItems[] = ['label' => 'Login',
\t\t\t'url' => ['/site/login']]
\t\t-\$menuItems[] = ['label' => 'Users', 'url' => ['/user/index']]
\telse
\t\t-\$menuItems[] = ['label' => 'Gii', 'url' => ['/gii']]
";

        $this->assertEquals(
            '<div><?php $menuItems[] = [\'label\' => \'Issues\',\'url\' => [\'/issue/index\']]?><?php if (Yii::$app->user->isGuest) {?><?php $menuItems[] = [\'label\' => \'Login\',\'url\' => [\'/site/login\']]?><?php $menuItems[] = [\'label\' => \'Users\', \'url\' => [\'/user/index\']]?><?php } else {?><?php $menuItems[] = [\'label\' => \'Gii\', \'url\' => [\'/gii\']]?><?php }?></div>',
            $this->renderer->compile($jade),
            'tabs'
        );
    }


    public function testIssue18()
    {


        $jade = <<<JADE
- if (\$something):
    p Do something
- endif;



- if (\$something && \$somethingElse) {
    p Do some random stuff
- }



-
    if (\$something && \$somethingElse) {
        echo "No jade handling here";
    }

    \$array = ["a","b"
        "c", "d",
            "e", "f",
        "g",
    "h"];

p and it goes on normally...
JADE;


        $this->assertEquals(
            '<?php if ($something):?><p>Do something</p><?php endif;?><?php if ($something && $somethingElse) {?><p>Do some random stuff</p><?php }?><?php if ($something && $somethingElse) {echo "No jade handling here";}$array = ["a","b""c", "d","e", "f","g","h"];?><p>and it goes on normally...</p>',
            $this->renderer->compile($jade)
        );
    }
}