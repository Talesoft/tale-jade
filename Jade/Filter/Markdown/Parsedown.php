<?php

namespace Tale\Jade\Filter\Markdown;

use Parsedown;
use Tale\Jade\Filter\Markdown;

class Parsedown extends Markdown
{
    private $markdown;

    public function __construct(Parsedown $markdown = null, $forceOptimization = false)
    {
        parent::__construct($forceOptimization);
        $this->markdown = $markdown ?: new Parsedown();
    }

    public function filter($content, array $context, $options)
    {
        return $this->markdown->text($content);
    }
}
