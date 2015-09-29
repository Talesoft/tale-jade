<?php

include '../vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('error_reporting', E_ALL | E_NOTICE);

$lexer = new Tale\Jade\Lexer();
?>
<table style="width: 100%;">
    <tr>
        <td style="width: 50%; border-right: 1px solid black; vertical-align: top;">
            <pre>
                <?php
                    echo "\n<b>views/index.jade</b>\n";
                    echo file_get_contents('views/index.jade');

                    echo "\n\n<b>views/layout-basic.jade</b>\n";
                    echo file_get_contents('views/layout-basic.jade');
                ?>
            </pre>
        </td>
        <td style="width: 50%; vertical-align: top;">
            <pre>
                <?php
                    echo "\n<b>views/index.jade</b>\n";
                    foreach ($lexer->lex(file_get_contents('views/index.jade')) as $token)
                        echo $token;

                    echo "\n\n<b>views/layout-basic.jade</b>\n";
                    foreach ($lexer->lex(file_get_contents('views/layout-basic.jade')) as $token)
                        echo $token;
                ?>
            </pre>
        </td>
    </tr>
</table>