<?php

namespace Tale\Jade\Compiler;

use Tale\ConfigurableTrait;
use Tale\Jade\Lexer;
use Tale\Jade\Parser\Node\AttributeListNode;
use Tale\Jade\Parser\Node\ElementNode;
use Tale\Jade\Util\LevelTrait;
use Tale\Reader;

class Formatter
{
    use ConfigurableTrait;
    use LevelTrait;

    /**
     * The Mode for HTML.
     *
     * Will     keep elements in selfClosingElements open
     * Will     repeat attributes if they're in selfRepeatingAttributes
     * Won't    /> close any elements, will </close> elements
     */
    const MODE_HTML = 0;

    /**
     * The Mode for XML.
     *
     * Will     /> close all elements, will </close> elements
     * Won't    repeat attributes if they're in selfRepeatingAttributes
     * Won't    keep elements in selfClosingElements open
     */
    const MODE_XML = 1;

    /**
     * The Mode for XHTML.
     *
     * Will     /> close all elements, will </close> elements
     * Will     repeat attributes if they're in selfRepeatingAttributes
     * Won't    keep elements in selfClosingElements open
     */
    const MODE_XHTML = 2;

    const CODE_START = '<?php';
    const CODE_END = '?>';
    const SHORT_CODE_START = '<?=';
    const SHORT_CODE_END = '?>';
    const VISIBLE_COMMENT_START = '<!--';
    const VISIBLE_COMMENT_END = '-->';
    const HIDDEN_COMMENT_START = '<?php /*';
    const HIDDEN_COMMENT_END = '/* ?>';

    public function __construct(array $options = null)
    {

        $this->defineOptions([
            'mode' => self::MODE_HTML,
            'newLine' => "\n",
            'lineLimit' => 60,
            'selfClosingElements' => [],
            'selfClosingStyle' => ' /',
            'indentStyle' => Lexer::INDENT_SPACE,
            'indentWidth' => 2,
            'quoteStyle' => '"',
            'codeQuoteStyle' => '\'',
            'pretty' => false,
            'level' => 0
        ], $options);

        $this->level = $this->options['level'];
    }

    public function isPretty()
    {

        return $this->options['pretty'];
    }

    /**
     * Checks if the current document mode equals the mode passed.
     *
     * Take a look at the Compiler::MODE_* constants to see the possible
     * modes
     *
     * @param int $mode the mode to check against
     *
     * @return bool
     */
    public function isMode($mode)
    {

        return $this->options['mode'] === $mode;
    }

    /**
     * Checks if we're in XML document mode.
     *
     * @return bool
     */
    public function isXmlMode()
    {

        return $this->isMode(self::MODE_XML);
    }

    /**
     * Checks if we're in HTML document mode.
     *
     * @return bool
     */
    public function isHtmlMode()
    {

        return $this->isMode(self::MODE_HTML);
    }

    /**
     * Checks if we're in XHTML document mode.
     *
     * @return bool
     */
    public function isXhtmlMode()
    {

        return $this->isMode(self::MODE_XHTML);
    }

    public function setMode($mode)
    {

        $this->options['mode'] = $mode;

        return $this;
    }

    public function setXmlMode()
    {

        return $this->setMode(self::MODE_XML);
    }

    public function setHtmlMode()
    {

        return $this->setMode(self::MODE_HTML);
    }

    public function setXhtmlMode()
    {

        return $this->setMode(self::MODE_XHTML);
    }

    public function isSelfClosingAllowed()
    {

        return $this->isXhtmlMode() || $this->isXmlMode();
    }

    public function getIndentation($level = null)
    {

        return $this->isPretty() ? str_repeat(
            $this->options['indentStyle'],
            $this->options['indentWidth'] * ($this->level + $level)
        ) : '';
    }

    public function getNewLine()
    {

        return $this->isPretty() ? $this->options['newLine'] : '';
    }

    public function isShortText($string)
    {

        return $this->strlen($string) < $this->options['lineLimit'];
    }

    public function formatAttributes(AttributeListNode $attributeList)
    {

        if (!count($attributeList))
            return '';

        $quoteStyle = $this->options['quoteStyle'];
        return implode(' ', array_map(function($key, $value) use ($quoteStyle) {

            return implode('', [
                $key, '=', $quoteStyle, $value, $quoteStyle
            ]);
        }, array_keys($attributeList), $attributeList));
    }

    public function isSelfClosing(ElementNode $node)
    {

        if (count($node) > 0)
            return false;

        return $this->isSelfClosingAllowed() || in_array(
            $node->getName(),
            $this->options['selfClosingElements'],
            true
        );
    }

    public function formatElementStart(ElementNode $element)
    {

        $name = $element->getName();
        $str = "<$name";
        $attrs = $this->formatAttributes($element->getAttributes());

        if (!empty($attrs))
            $str .= " $attrs";

        if ($this->isSelfClosing($element))
            $str .= $this->options['selfClosingStyle'];

        return "$str>";
    }

    public function formatElementEnd(ElementNode $element)
    {

        return '</'.$element->getName().'>';
    }

    public function wrapText($text, $prefix, $suffix, $block = false)
    {

        $break = $block
            ? $this->getNewLine()
            : ' ';

        $indent = $block
            ? $this->getIndentation(1)
            : '';

        return $prefix.$break
        . $indent.$this->formatText($text).$break
        . $indent.$suffix;
    }

    public function wrapCode($code, $block = false)
    {

        return $this->wrap(
            $code,
            self::SHORT_CODE_START,
            self::SHORT_CODE_END,
            $block
        );
    }

    public function wrapShortCode($code, $block = false)
    {

        return $this->wrap(
            $code,
            self::SHORT_CODE_START,
            self::SHORT_CODE_END,
            $block
        );
    }

    public function wrapVisibleComment($code, $block = false)
    {

        return $this->wrap(
            $code,
            self::VISIBLE_COMMENT_START,
            self::VISIBLE_COMMENT_END,
            $block
        );
    }

    public function wrapHiddenComment($code, $block = false)
    {

        return $this->wrap(
            $code,
            self::HIDDEN_COMMENT_START,
            self::HIDDEN_COMMENT_END,
            $block
        );
    }

    private function strlen($string)
    {

        return function_exists('mb_strlen') ? mb_strlen($string) : strlen($string);
    }
}