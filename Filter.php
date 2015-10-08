<?php
/**
 * The Tale Jade Project
 *
 * The Filter Class
 *
 * This file is part of the Tale Jade Template Engine for PHP
 *
 * @author Torben Köhn <tk@talesoft.io>
 * @author Talesoft <info@talesoft.io>
 * @projectGroup Tale
 * @project Jade
 * @component Filter
 *
 * The code of this file is distributed under the MIT license.
 * If you didn't receive a copy of the license text, you can
 * read it here http://licenses.talesoft.io/2015/MIT.txt
 *
 * Please do not remove this comment block.
 * Thank you and have fun with Tale Jade!
 */

namespace Tale\Jade;

use Tale\Jade\Parser\Node;

/**
 * Static filter class
 *
 * This class contains the default filters of the template engine.
 *
 * @todo: Need moar filters!
 * @package Tale\Jade
 */
class Filter
{

    /**
     * Wraps a node $node in tags using $tag and respecting
     * indentation and new-lines based on $indent and $newLine
     *
     * @param string                 $tag     The tag to wrap the node in
     * @param \Tale\Jade\Parser\Node $node    The node to be wrapped
     * @param string                 $indent  The indentation to use on each child
     * @param string                 $newLine The new-line to append after each line
     *
     * @return string The wrapped PTHML-string
     */
    public static function wrapTag($tag, Node $node, $indent, $newLine)
    {

        return "<$tag>".$newLine.self::filterPlain($node, $indent, $newLine).$indent."</$tag>".$newLine;
    }

    /**
     * Similar to wrapTag, but rather puts PHP-instruction-tags around the text
     * inside the node.
     * This will create working PHP expressions.
     *
     * If <?php or ?> are already found, they will be trimmed and re-appended correctly.
     *
     * @param \Tale\Jade\Parser\Node $node    The node to be wrapped
     * @param string                 $indent  The indentation to use on each child
     * @param string                 $newLine The new-line to append after each line
     *
     * @return string The wrapped PHP-string
     */
    public static function wrapCode(Node $node, $indent, $newLine)
    {

        $strlen = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
        $text = self::filterPlain($node, $indent, $newLine);

        $text = preg_replace(['/^\s*<\?php ?/i', '/\?>\s*$/'], '', $text);

        return $indent.'<?php '.$newLine.$text.$newLine.$indent.'?>'.$newLine;
    }


    /**
     * A plain-text filter that just corrects indentation and new-lines
     *
     * @param \Tale\Jade\Parser\Node $node    The node to be wrapped
     * @param string                 $indent  The indentation to use on each child
     * @param string                 $newLine The new-line to append after each line
     *
     * @return string The wrapped PTHML-string
     */
    public static function filterPlain(Node $node, $indent, $newLine)
    {

        $text = trim($node->text());

        //Normalize newlines to $newLine and append our indent
        $i = 0;

        return implode($newLine, array_map(function ($value) use ($indent, $newLine, &$i) {

            if (strlen($indent) < 1 && $i++ !== 0 && strlen($value) > 0) {

                //Make sure we separate with at least one white-space
                $indent = ' ';
            }

            return $indent.trim($value);
        }, explode("\n", $text)));
    }

    /**
     * Wraps the content in <style></style> tags and corrects indentation
     * and new-lines
     *
     * @param \Tale\Jade\Parser\Node $node    The node to be wrapped
     * @param string                 $indent  The indentation to use on each child
     * @param string                 $newLine The new-line to append after each line
     *
     * @return string The wrapped PTHML-string
     */
    public static function filterStyle(Node $node, $indent, $newLine)
    {

        return self::wrapTag('style', $node, $indent, $newLine);
    }

    /**
     * Wraps the content in <script></script> tags and corrects indentation
     * and new-lines
     *
     * @param \Tale\Jade\Parser\Node $node    The node to be wrapped
     * @param string                 $indent  The indentation to use on each child
     * @param string                 $newLine The new-line to append after each line
     *
     * @return string The wrapped PTHML-string
     */
    public static function filterScript(Node $node, $indent, $newLine)
    {

        return self::wrapTag('script', $node, $indent, $newLine);
    }

    /**
     * Wraps the content in PHP-compiler tags and corrects indentation
     * and new-lines
     *
     * @param \Tale\Jade\Parser\Node $node    The node to be wrapped
     * @param string                 $indent  The indentation to use on each child
     * @param string                 $newLine The new-line to append after each line
     *
     * @return string The wrapped PHP-string
     */
    public static function filterCode(Node $node, $indent, $newLine)
    {

        return self::wrapCode($node, $indent, $newLine);
    }
}