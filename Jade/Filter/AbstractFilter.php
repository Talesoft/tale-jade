<?php

namespace Tale\Jade\Filter;

use MtHaml\NodeVisitor\RendererAbstract as Renderer;
use MtHaml\Node\Insert;
use Tale\Jade\Parser\Node;
use Tale\Jade\Parser\Node\FilterNode;

abstract class AbstractFilter implements FilterInterface
{
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
    public function wrapCode(Node $node, $indent, $newLine)
    {
        $text = $this->filterPlain($node, $indent, $newLine);
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
    public function filterPlain(Node $node, $indent, $newLine)
    {

        $text = trim($node->getText());

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

    public function isOptimizable(Renderer $renderer, FilterNode $node, $options)
    {
        foreach ($node->getChilds() as $line) {
            foreach ($line->getContent()->getChilds() as $child) {
                if ($child instanceof Insert) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function renderFilter(Renderer $renderer, FilterNode $node)
    {
        foreach ($node->getChilds() as $child) {
            $child->accept($renderer);
        }
    }

    protected function getContent(FilterNode $node)
    {
        $content = '';
        foreach ($node->getChildren() as $line) {
            foreach ($line->getContent()->getChilds() as $child) {
                $content .= $child->getContent();
            }
            $content .= "\n";
        }

        return $content;
    }
}
