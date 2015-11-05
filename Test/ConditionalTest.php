<?php

namespace Tale\Jade\Test;

use Tale\Jade\Compiler;

class ConditionalTest extends \PHPUnit_Framework_TestCase
{

    /** @var \Tale\Jade\Compiler */
    private $_compiler;

    public function setUp()
    {

        $this->_compiler = new Compiler([
            'pretty' => false,
            'handleErrors' => false
        ]);
    }

    public function testSimpleIf()
    {

        $this->assertEquals('<?php if (isset($something) ? $something : false) {?><p>Do something</p><?php }?>', $this->_compiler->compile('if $something
    p Do something'));
    }

    public function testSimpleUnless()
    {

        $this->assertEquals('<?php if (!(isset($something) ? $something : false)) {?><p>Do something</p><?php }?>', $this->_compiler->compile('unless $something
    p Do something'));
    }

    public function testElseIf()
    {

        $this->assertEquals(
            '<?php if (isset($something) ? $something : false) {?><p>Do something</p><?php } else {?><p>Do some other thing</p><?php }?>',
            $this->_compiler->compile('if $something
    p Do something
else
    p Do some other thing'));
    }



    public function testIssue19()
    {

        //Also testing this in pretty-mode
        $prettyCompiler = new Compiler([
            'pretty' => true,
            'handleErrors' => false
        ]);

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
            '<div><?php $menuItems[] = [\'label\' => \'Issues\', \'url\' => [\'/issue/index\']]?><?php if (Yii::$app->user->isGuest) {?><?php $menuItems[] = [\'label\' => \'Login\', \'url\' => [\'/site/login\']]?><?php $menuItems[] = [\'label\' => \'Users\', \'url\' => [\'/user/index\']]?><?php } else {?><?php $menuItems[] = [\'label\' => \'Gii\', \'url\' => [\'/gii\']]?><?php }?></div>',
            $this->_compiler->compile($jade)
        );
        $this->assertEquals('
<div>
  <?php $menuItems[] = [\'label\' => \'Issues\',
    \'url\' => [\'/issue/index\']]
  ?>
  <?php if (Yii::$app->user->isGuest) {?>
    <?php $menuItems[] = [\'label\' => \'Login\',
      \'url\' => [\'/site/login\']]
    ?>
    <?php $menuItems[] = [\'label\' => \'Users\', \'url\' => [\'/user/index\']]?>

  <?php }
   else {?>
    <?php $menuItems[] = [\'label\' => \'Gii\', \'url\' => [\'/gii\']]?>

  <?php }?>
</div>', $prettyCompiler->compile($jade));





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
            '<div><?php $menuItems[] = [\'label\' => \'Issues\', \'url\' => [\'/issue/index\']]?><?php if (Yii::$app->user->isGuest) {?><?php $menuItems[] = [\'label\' => \'Login\', \'url\' => [\'/site/login\']]?><?php $menuItems[] = [\'label\' => \'Users\', \'url\' => [\'/user/index\']]?><?php } else {?><?php $menuItems[] = [\'label\' => \'Gii\', \'url\' => [\'/gii\']]?><?php }?></div>',
            $this->_compiler->compile($jade)
        );
        $this->assertEquals('
<div>
  <?php $menuItems[] = [\'label\' => \'Issues\',
    \'url\' => [\'/issue/index\']]
  ?>
  <?php if (Yii::$app->user->isGuest) {?>
    <?php $menuItems[] = [\'label\' => \'Login\',
      \'url\' => [\'/site/login\']]
    ?>
    <?php $menuItems[] = [\'label\' => \'Users\', \'url\' => [\'/user/index\']]?>

  <?php }
   else {?>
    <?php $menuItems[] = [\'label\' => \'Gii\', \'url\' => [\'/gii\']]?>

  <?php }?>
</div>', $prettyCompiler->compile($jade));







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
            '<div><?php $menuItems[] = [\'label\' => \'Issues\', \'url\' => [\'/issue/index\']]?><?php if (Yii::$app->user->isGuest) {?><?php $menuItems[] = [\'label\' => \'Login\', \'url\' => [\'/site/login\']]?><?php $menuItems[] = [\'label\' => \'Users\', \'url\' => [\'/user/index\']]?><?php } else {?><?php $menuItems[] = [\'label\' => \'Gii\', \'url\' => [\'/gii\']]?><?php }?></div>',
            $this->_compiler->compile($jade)
        );
        $this->assertEquals('
<div>
  <?php $menuItems[] = [\'label\' => \'Issues\',
    \'url\' => [\'/issue/index\']]
  ?>
  <?php if (Yii::$app->user->isGuest) {?>
    <?php $menuItems[] = [\'label\' => \'Login\',
      \'url\' => [\'/site/login\']]
    ?>
    <?php $menuItems[] = [\'label\' => \'Users\', \'url\' => [\'/user/index\']]?>

  <?php }
   else {?>
    <?php $menuItems[] = [\'label\' => \'Gii\', \'url\' => [\'/gii\']]?>

  <?php }?>
</div>', $prettyCompiler->compile($jade));
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
            '<?php if ($something):?><p>Do something</p><?php endif;?><?php if ($something && $somethingElse) {?><p>Do some random stuff</p><?php }?><?php if ($something && $somethingElse) {echo "No jade handling here"; } $array = ["a","b""c", "d","e", "f", "g", "h"];?><p>and it goes on normally...</p>',
            $this->_compiler->compile($jade)
        );

    }
}