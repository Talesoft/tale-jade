<?php

include 'vendor/autoload.php';

$lexer = new Tale\Jade\Lexer();

echo '<pre>';
$jade = <<<JADE
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
    :some-filter
        some filtered
        content
    include some-file
    extends some-file
    includeextends

    block some-block
    append append-block
    prepend prepend-block
    replace replace-block
    block

    block.
        some
        block
        include

    block! some text

    block!.
        some text

    append a: prepend b

    case abc: def
    case (a ? b : c):
        def
    case "ab:c":
        def
    case

JADE;

echo htmlentities($jade, ENT_QUOTES, 'UTF-8');

echo "\n\n================\n\n";

foreach ($lexer->lex($jade) as $token) {

    echo '<span';
    if ($token instanceof \Tale\Jade\Lexer\Token\IndentToken)
        echo ' style="color: blue;"';

    if ($token instanceof \Tale\Jade\Lexer\Token\OutdentToken)
        echo ' style="color: red;"';

    if ($token instanceof \Tale\Jade\Lexer\Token\NewLineToken)
        echo ' style="color: lightcyan;"';

    if ($token instanceof \Tale\Jade\Lexer\Token\CommentToken)
        echo ' style="color: lightgray;"';

    if ($token instanceof \Tale\Jade\Lexer\Token\TextToken)
        echo ' style="color: lime;"';

    echo '>';
    echo $token;
    echo '</span>';
}