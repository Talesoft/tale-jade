<?php

namespace Tale\Jade\Parser\Dumper;

use Tale\Jade\Parser\DumperBase;
use Tale\Jade\Parser\NodeInterface;

class Text extends DumperBase
{

    protected function dumpNode(NodeInterface $node, $level = null)
    {

        $level = $level ?: 0;
        $text = '';
        switch (get_class($node)) {
            default:

                $text = $this->getNodeName($node);
                break;
        }

        $text = str_repeat('  ', $level)."[$text]";

        if (count($node) > 0) {

            foreach ($node as $child)
                $text .= "\n".$this->dumpNode($child, $level + 1);
        }

        return $text;
    }
}