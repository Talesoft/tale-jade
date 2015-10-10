<?php
/**
 * The Tale Jade Default Filters.
 *
 * Contains a static filter class that provides some basic
 * filters for use inside Jade-files. You can define own filters
 * by passing the 'filter'-option to the Compiler you compile
 * your Jade files with
 *
 * This file is part of the Tale Jade Template Engine for PHP
 *
 * LICENSE:
 * The code of this file is distributed under the MIT license.
 * If you didn't receive a copy of the license text, you can
 * read it here http://licenses.talesoft.io/2015/MIT.txt
 *
 * @category   Presentation
 * @package    Tale\Jade
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Talesoft (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.1
 * @link       http://jade.talesoft.io/docs/files/Filter.html
 * @since      File available since Release 1.0
 */

namespace Tale\Jade;

use Tale\Jade\Parser\Node;

/**
 * Provides basic, static filters for the compiler.
 *
 * The only reason this class exists is so that you don't have do write
 * this basic stuff yourself.
 *
 * This class provides the following filters for the Compiler:
 *
 * :plain   => Converts to plain text
 * :js      => Converts to <script></script>
 * :css     => Converts to <style></style>
 * :php     => converts to <?php ? >
 *
 * @category   Presentation
 * @package    Tale\Jade
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Talesoft (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.1
 * @link       http://jade.talesoft.io/docs/classes/Tale.Jade.Filter.html
 * @since      File available since Release 1.0
 */
class Filter
{

    /**
     * Wraps a node $node in tags using $tag.
     *
     * Respects indentation and new-lines based on $indent and $newLine
     *
     * @param string $tag     the tag to wrap the node in
     * @param Node   $node    the node to be wrapped
     * @param string $indent  the indentation to use on each child
     * @param string $newLine the new-line to append after each line
     *
     * @return string the wrapped PTHML-string
     */
    public static function wrapTag($tag, Node $node, $indent, $newLine)
    {

        return "<$tag>".$newLine.self::filterPlain($node, $indent, $newLine)
               .$indent."</$tag>".$newLine;
    }

    /**
     * Similar to wrapTag, but rather puts PHP-instruction-tags around the text.
     *
     * This will create working PHP expressions.
     *
     * If <?php or ? > are already found, they will be trimmed and re-appended
     * correctly to avoid failing nested expressions (<?php cant be used
     * _inside_ <?php)
     *
     * @param Node   $node    the node to be wrapped
     * @param string $indent  the indentation to use on each child
     * @param string $newLine the new-line to append after each line
     *
     * @return string the wrapped PHP-string
     */
    public static function wrapCode(Node $node, $indent, $newLine)
    {

        $text = self::filterPlain($node, $indent, $newLine);
        $text = preg_replace(['/^\s*<\?php ?/i', '/\?>\s*$/'], '', $text);

        return $indent.'<?php '.$newLine.$text.$newLine.$indent.'?>'.$newLine;
    }


    /**
     * A plain-text filter that just corrects indentation and new-lines.
     *
     * @param Node   $node    the node to be wrapped
     * @param string $indent  the indentation to use on each child
     * @param string $newLine the new-line to append after each line
     *
     * @return string the wrapped PTHML-string
     */
    public static function filterPlain(Node $node, $indent, $newLine)
    {

        $text = trim($node->text());

        //Normalize newlines to $newLine and append our indent
        $i = 0;

        return implode($newLine, array_map(function ($value) use (
            $indent, $newLine, &$i
        ) {

            if (strlen($indent) < 1 && $i++ !== 0 && strlen($value) > 0) {

                //Make sure we separate with at least one white-space
                $indent = ' ';
            }

            return $indent.trim($value);
        }, explode("\n", $text)));
    }

    /**
     * Wraps the content in <style></style> tags and corrects indentation
     * and new-lines.
     *
     * @param Node   $node    the node to be wrapped
     * @param string $indent  the indentation to use on each child
     * @param string $newLine the new-line to append after each line
     *
     * @return string The wrapped PTHML-string
     */
    public static function filterStyle(Node $node, $indent, $newLine)
    {

        return self::wrapTag('style', $node, $indent, $newLine);
    }

    /**
     * Wraps the content in <script></script> tags and corrects indentation
     * and new-lines.
     *
     * @param Node   $node    the node to be wrapped
     * @param string $indent  the indentation to use on each child
     * @param string $newLine the new-line to append after each line
     *
     * @return string the wrapped PTHML-string
     */
    public static function filterScript(Node $node, $indent, $newLine)
    {

        return self::wrapTag('script', $node, $indent, $newLine);
    }

    /**
     * Wraps the content in PHP-compiler tags and corrects indentation
     * and new-lines.
     *
     * @param Node   $node    the node to be wrapped
     * @param string $indent  the indentation to use on each child
     * @param string $newLine the new-line to append after each line
     *
     * @return string the wrapped PHP-string
     */
    public static function filterCode(Node $node, $indent, $newLine)
    {

        return self::wrapCode($node, $indent, $newLine);
    }
}