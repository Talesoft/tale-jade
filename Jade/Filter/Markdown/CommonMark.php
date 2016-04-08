<?php

namespace Tale\Jade\Filter\Markdown;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Converter;
use Tale\Jade\Filter\Markdown;

class CommonMark extends Markdown
{
    private $converter;

    public function __construct(Converter $converter = null, $forceOptimization = false)
    {
        parent::__construct($forceOptimization);
        $this->converter = $converter ?: new CommonMarkConverter();
    }

    public function filter($content, array $context, $options)
    {
        return $this->converter->convertToHtml($content);
    }
}
