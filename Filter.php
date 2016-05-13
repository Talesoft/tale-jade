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
 * @copyright  Copyright (c) 2015 Torben Köhn (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.3
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
 * :markdown => converts to markdown-HTML
 * :coffee  => converts to CoffeeScript-JavaScript
 * :less    => converts to LESS CSS
 * :stylus  => converts to Stylus CSS
 * :sass    => converts to SASS CSS
 *
 * @category   Presentation
 * @package    Tale\Jade
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Torben Köhn (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.3
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

    /**
     * Compiles the markdown content to HTML.
     *
     * @param Node   $node    the node to be compiled
     * @param string $indent  the indentation to use on each child
     * @param string $newLine the new-line to append after each line
     *
     * @return string the wrapped HTML-string
     * @throws Compiler\Exception when the Parsedown package is not installed
     */
    public static function filterMarkdown(Node $node, $indent, $newLine)
    {

        if (!class_exists('Parsedown'))
            throw new Compiler\Exception(
                "Failed to compile Markdown: "
                ."Please install the erusev/parsedown composer package"
            );

        $parsedown = new \Parsedown();

        return $parsedown->text($node->text());
    }


    /**
     * Compiles the CoffeeScript content to JavaScript
     *
     * @param Node   $node    the node to be compiled
     * @param string $indent  the indentation to use on each child
     * @param string $newLine the new-line to append after each line
     *
     * @return string the wrapped JavaScript-HTML-string
     * @throws Compiler\Exception when the CoffeeScript package is not installed
     */
    public static function filterCoffeeScript(Node $node, $indent, $newLine)
    {

        if (!class_exists('CoffeeScript\\Compiler'))
            throw new Compiler\Exception(
                "Failed to compile CoffeeScript: "
                ."Please install the coffeescript/coffeescript composer package"
            );

        $js = \CoffeeScript\Compiler::compile($node->text(), [
            'header' => '',
            'filename' => 'Imported-at-('.$node->line.':'.$node->offset.').coffee'
        ]);

        return '<script>'.$newLine.$indent.$js.$newLine.$indent.'</script>';
    }

    /**
     * Compiles the LESS content to CSS
     *
     * @param Node   $node    the node to be compiled
     * @param string $indent  the indentation to use on each child
     * @param string $newLine the new-line to append after each line
     *
     * @return string the wrapped Less-CSS-string
     * @throws Compiler\Exception when the LESS package is not installed
     */
    public static function filterLess(Node $node, $indent, $newLine)
    {

        if (!class_exists('lessc'))
            throw new Compiler\Exception(
                "Failed to compile LESS: "
                ."Please install the leafo/lessphp composer package"
            );

        $less = new \lessc;
        $css = $less->compile($node->text());

        return '<style>'.$newLine.$indent.$css.$newLine.$indent.'</style>';
    }

    /**
     * Compiles the Stylus content to CSS
     *
     * @param Node   $node    the node to be compiled
     * @param string $indent  the indentation to use on each child
     * @param string $newLine the new-line to append after each line
     *
     * @return string the wrapped Stylus-CSS-string
     * @throws Compiler\Exception when the Stylus package is not installed
     */
    public static function filterStylus(Node $node, $indent, $newLine)
    {

        if (!class_exists('Stylus\\Stylus'))
            throw new Compiler\Exception(
                "Failed to compile Stylus: "
                ."Please install the neemzy/stylus composer package"
            );

        $stylus = new \Stylus\Stylus;
        $css = $stylus->fromString($node->text())->toString();

        return '<style>'.$newLine.$indent.$css.$newLine.$indent.'</style>';
    }

    /**
     * Compiles the SASS content to CSS
     *
     * @param Node   $node    the node to be compiled
     * @param string $indent  the indentation to use on each child
     * @param string $newLine the new-line to append after each line
     *
     * @return string the wrapped SASS-CSS-string
     * @throws Compiler\Exception when the Stylus package is not installed
     */
    public static function filterSass(Node $node, $indent, $newLine)
    {

        if (!class_exists('Leafo\\ScssPhp\\Compiler'))
            throw new Compiler\Exception(
                "Failed to compile SASS: "
                ."Please install the leafo/scssphp composer package"
            );

        $sass = new \Leafo\ScssPhp\Compiler;
        $css = $sass->compile($node->text());

        return '<style>'.$newLine.$indent.$css.$newLine.$indent.'</style>';
    }
}