<?php

namespace Tale\Jade\Parser\Dumper;


use Tale\Jade\Parser\NodeInterface;

class Html extends Text
{
    protected function getPrefix()
    {

        return '<pre>';
    }

    protected function getSuffix()
    {

        return '</pre>';
    }

    protected function dumpNode(NodeInterface $node, $level = null)
    {

        $name = $this->getNodeName($node);
        $html = '';
        switch (get_class($node)) {
            default:

                $html = parent::dumpNode($node, $level);
        }

        return $html;
    }
}