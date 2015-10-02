<?php

include '../vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('error_reporting', E_ALL | E_NOTICE);

$compiler = new Tale\Jade\Compiler();
?>
<table style="width: 100%;">
    <tr>
        <td style="width: 50%; border-right: 1px solid black; vertical-align: top;">
            <pre>
                <?php
                echo "\n<b>views/index.jade</b>\n";
                echo file_get_contents('views/index.jade');
                ?>
            </pre>
        </td>
        <td style="width: 50%; vertical-align: top;">
            <pre>
                <?php
                echo "\n<b>views/index.jade</b>\n";
                echo htmlentities($compiler->compileFile('views/index.jade'), ENT_QUOTES);
                ?>
            </pre>
        </td>
    </tr>
</table>