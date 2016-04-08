<?php

namespace Tale\Jade\Filter\Markdown;

use Tale\Jade\Filter\Markdown;

class MichelfMarkdown extends Markdown
{
    private $markdown;

    public function __construct(\Michelf\Markdown $markdown = null, $forceOptimization = false)
    {
        parent::__construct($forceOptimization);
        $this->markdown = $markdown ?: ;
    }

    public function filter($content, array $context, $options)
    {
        return $this->markdown->transform($content);
    }
}
