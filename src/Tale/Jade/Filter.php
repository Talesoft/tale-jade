<?php

namespace Tale\Jade;

class Filter
{

    public static function wrapTag($tag, Node $node, $indent, $newLine)
    {

        return "<$tag>".$newLine.self::filterPlain($node, $indent, $newLine).$indent."</$tag>";
    }

    public static function wrapCode(Node $node, $indent, $newLine)
    {

        return "<?php ".$newLine.self::filterPlain($node, $indent, $newLine).$indent."?>";
    }


    public static function filterPlain(Node $node, $indent, $newLine)
    {

        return implode($newLine, array_map(function($line) use($indent, $newLine) {

            return $indent.trim($line);
        }, preg_split("/\r?\n/", $node->text())));
    }

    public static function filterStyle(Node $node, $indent, $newLine)
    {

        return self::wrapTag('style', $node, $indent, $newLine);
    }

    public static function filterScript(Node $node, $indent, $newLine)
    {

        return self::wrapTag('script', $node, $indent, $newLine);
    }

    public static function filterCode(Node $node, $indent, $newLine)
    {

        return self::wrapCode($node, $indent, $newLine);
    }

    public static function filterMarkdown(Node $node, $indent, $newLine)
    {

        return self::wrapTag('markdown', $node, $indent, $newLine);
    }
}