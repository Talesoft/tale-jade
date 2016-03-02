<?php

include 'vendor/autoload.php';

$lexer = new Tale\Jade\Lexer();
$parser = new Tale\Jade\Parser();
$compiler = new Tale\Jade\Compiler(['pretty' => true]);

ini_set('xdebug.max_nesting_level', 10000);

header('Content-Type: text/plain; encoding=utf-8');

$jade = <<<'JADE'

//extends some-file
| Some text
!| Some text
<some markup>

here shall be tag

//single line comment
//- single line unrendered comment
//
    multiline
    comment
!| here be normal stuff again
p.a.b.c#d(e=f, g, h='i')
    :css
        some filtered
        content
    //include some-file
    //includeextends

    block some-block
    append append-block
    prepend prepend-block
    replace replace-block

    a.
        some
        block
        include

    a! some text

    a!.
        some text

    append a: prepend b

    case (a ? b : c)
        when (d ? e : f): ghi.jkl
        when ("d:e:f" ? "g" : 'h:')
            i.jk
        default: default1
        default
            default2


    if somexpr: some if stuff
    else if some other expr
        some elseif stuff
    elseif: some other elseif stuff
    else      if
        some other else   if stuff
    else: some else stuff

    $someVariable

    each $item in $items

    do: something
    while $anything

    while $something
        anything

    for $x; $x < 0; $x++: something

    - $someCode
    -
        $some(
            $multiLine,
            $code
         )

    - if ($x):
        p something
    - else:
        p something else
    - endif;

    some-tag.a-class.b-class.c-class#someId.d-class&some-assignment()

    ($someKey="some value" some-key?=($a ? $b : ', d'), $some['value'])

    doctype html
    !!! 1.1

    mixin some-mixin

    +some-mixin

    = $someExpression
    != $someUnescapedExpression
    ?= $someUncheckedExpression
    ?!= $someUnescapedUncheckedExpression

    mixin article(title)

        h2= $title
        p
            if block
                block

    +article('Article 1')
        strong Block Content 1
        | Awesome, isn't it?

JADE;

echo htmlentities($jade, ENT_QUOTES, 'UTF-8');

echo "\n\n================\n\n";

try {

    echo htmlentities($compiler->compile($jade), ENT_QUOTES, 'UTF-8');
} catch(Exception $e) {
    echo $e->getMessage();
}

echo "\n\n================\n\n";

try {
    echo $parser->dump($jade);
} catch(Exception $e) {
    echo $e->getMessage();
}

echo "\n\n================\n\n";

try {

    echo $lexer->dump($jade);
} catch(Exception $e) {
    echo $e->getMessage();
}