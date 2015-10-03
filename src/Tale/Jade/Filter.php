<?php

namespace Tale\Jade;

class Filter
{

    public static function filterStyle(Node $node)
    {

        return '<style>'.$node->text().'</style>';
    }

    public static function filterScript(Node $node)
    {

        return '<script>'.$node->text().'</script>';
    }

    public static function filterCode(Node $node)
    {

        return '<?php '.$node->text().'?>';
    }

    public static function filterMarkdown(Node $node)
    {

        return '<markdown>'.$node->text().'</markdown>';
    }
}