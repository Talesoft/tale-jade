<?php

use Tale\Jade\Node;

include '../vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('error_reporting', E_ALL | E_NOTICE);

$testCount = $passes = $fails = 0;
function check($condition, $message)
{
    global $testCount, $passes, $fails;

    $testCount++;
    if (!$condition) {

        $fails++;
        return '<strong style="color: red">'.$message.'</strong>';
    }

    $passes++;
    return '<strong style="color: green">Passed!</strong>';
}

$tests = [
    'indexOf' => function() {

        $node = new Node('parent');
        $node->append(new Node('child-1'));
        $node->append($secondChild = new Node('child-2'));
        $node->append($thirdChild = new Node('child-3'));

        $notAChild = new Node('not-a-child');

        yield check($node->indexOf($secondChild) === 1, 'Second child is not 1');
        yield check($node->indexOf($thirdChild) === 2, 'Third child is not 2');
        yield check($node->indexOf($notAChild) === false, 'notAChild is not false');
    },
    'append' => function() {

        $node = new Node('parent');
        $node->append(new Node('child-1'));
        $node->append(new Node('child-2'));
        $node->append(new Node('child-3'));

        $notAChild = new Node('not-a-child');

        check(count($node->children) === 3, 'Child count is not 3');

        $validParent = true;
        foreach ($node->children as $childNode)
            if ($childNode->parent !== $node)
                $validParent = false;
        yield check($validParent, 'Parent not correctly set');
    },
    'prepend' => function() {

        $node = new Node('parent');
        $node->append(new Node('child-1'));
        $node->prepend($secondChild = new Node('child-2'));
        $node->prepend($thirdChild = new Node('child-3'));

        $notAChild = new Node('not-a-child');

        yield check(count($node->children) === 3, 'Child count is not 3');

        $validParent = true;
        foreach ($node->children as $childNode)
            if ($childNode->parent !== $node)
                $validParent = false;
        yield check($node->indexOf($secondChild) === 1, 'Second child not 1');
        yield check($node->indexOf($thirdChild) === 0, 'Third child not 0');
        yield check($validParent, 'Parent not correctly set');
    },
    'remove' => function() {

        $node = new Node('parent');
        $node->append(new Node('child-1'));
        $node->append($secondChild = new Node('child-2'));
        $node->append($thirdChild = new Node('child-3'));

        $notAChild = new Node('not-a-child');

        $node->remove($secondChild);

        yield check($node->indexOf($secondChild) === false, 'Child still in parent node');
        yield check($secondChild->parent === null, 'Childs parent not null');
        yield check($node->indexOf($thirdChild) === 1, 'Third child not moved to 1');
    },
    'insertAfter' => function() {

        $node = new Node('parent');
        $node->append($firstChild = new Node('child-1'));
        $node->append($secondChild = new Node('child-2'));
        $node->insertAfter($firstChild, $thirdChild = new Node('child-3'));

        $notAChild = new Node('not-a-child');

        yield check(count($node->children) === 3, 'Child count not 3');
        yield check($node->indexOf($thirdChild) === 1, 'Third child not 1');
        yield check($node->indexOf($secondChild) === 2, 'Second child not 2');
    },
    'insertBefore' => function() {

        $node = new Node('parent');
        $node->append($firstChild = new Node('child-1'));
        $node->append($secondChild = new Node('child-2'));
        $node->insertBefore($secondChild, $thirdChild = new Node('child-3'));

        $notAChild = new Node('not-a-child');

        yield check(count($node->children) === 3, 'Child count not 3');
        yield check($node->indexOf($thirdChild) === 1, 'Third child not 1');
        yield check($node->indexOf($secondChild) === 2, 'Second child not 2');
    }
];



echo '<h1>Testing node functionality</h1>';

ob_start();
foreach ($tests as $name => $test) {

    echo '<h2>Testing '.$name.':</h2><br>';

    foreach ($test() as $result) {

        echo "$result<br>";
    }
}
$results = ob_get_clean();

echo "<h2>Tests: $testCount, Passes: $passes, Fails: $fails</h2><br>";
echo $results;