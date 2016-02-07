<?php

namespace Tale\Jade\Compiler;

use Tale\ConfigurableTrait;
use Tale\Jade\Lexer;
use Tale\Jade\Util\LevelTrait;

class Formatter
{
    use ConfigurableTrait;
    use LevelTrait;

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
            'indentStyle' => Lexer::INDENT_SPACE,
            'indentWidth' => 2,
            'lineBreak' => "\n",
            'quoteStyle' => '"',
            'codeQuoteStyle' => '\'',
            'pretty' => false,
            'level' => 0
        ]);

        $this->setLevel($this->getOption('level'));
    }

    public function formatsPretty()
    {

        return $this->getOption('pretty');
    }

    public function getIndentation($offset = null)
    {

        $offset = $offset ?: 0;

        if (!$this->formatsPretty())
            return '';

        return str_repeat(
            $this->getOption('indentStyle'),
            $this->getOption('indentWidth') * ($this->getLevel() + $offset)
        );
    }

    public function getLineBreak()
    {

        if (!$this->formatsPretty())
            return '';

        return $this->getOption('lineBreak');
    }

    public function wrap($string, $prefix, $suffix, $block = false)
    {

        $break = $block
             ? $this->getLineBreak()
             : ' ';
        $indent = $block
                ? $this->getIndentation(1)
                : '';

        return $prefix.$break
             . $indent.$string.$break
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
}