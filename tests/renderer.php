<?php

include '../vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('error_reporting', E_ALL | E_NOTICE);

$renderer = new Tale\Jade\Renderer([
    'compiler' => ['pretty' => false]
]);
?>


<div style="padding: 20px; border: 1px dashed red;">
    <?php
    echo '<pre>';
    echo htmlentities($renderer->compileFile('views/all-features'), ENT_QUOTES);
    echo '</pre>';
    ?>
</div>



<?php
echo $renderer->render('views/all-features');
?>
