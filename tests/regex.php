<?php

$patterns = [
    '^(?<name>\w[\-:\w]*)' => 'tag',
    '^(?J:block(?: (?<mode>append|prepend|replace))?(?: (?<name>\w))?|(?<mode>append|prepend|replace) (?<name>\w))$' => 'block'
];



$jade = '

extends ../layout

h1 Some Headling
p Some Text

block content

    input(type=\'text\') Some Input Value


block scripts
    script(src="")

block append scripts
    script(src="")

prepend scripts
    script(src="")

';



foreach ($patterns as $pattern => $tokenType) {

    var_dump('Match '.$pattern);
    if (preg_match('/'.$pattern.'/ms', $jade, $matches)) {

        var_dump($matches);
    }
}

