<?php

namespace Tale\Jade\Parser;

abstract class DumperBase implements DumperInterface
{

    protected function getNodeName(NodeInterface $node)
    {

        return basename(get_class($node), 'Node');
    }

    protected function getPrefix()
    {

        return '';
    }

    protected function getSuffix()
    {

        return '';
    }

    abstract protected function dumpNode(NodeInterface $node);

    public function dump(NodeInterface $node)
    {

        $string = $this->getPrefix();
        $string .= $this->dumpNode($node);
        $string .= $this->getSuffix();

        return $string;
    }
}