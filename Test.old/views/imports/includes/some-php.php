<?php

if (!function_exists('somePassedVar')):
    function somePassedVar($var)
    {
        echo "Hello from $var";
    }
endif;

somePassedVar('PHP!');