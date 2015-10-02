<?php

namespace Tale\Jade;

use Tale\Util\PathUtil;

class Compiler
{

    const MODE_HTML = 0;
    const MODE_XML = 1;

    private $_options;
    private $_lexer;
    private $_parser;

    private $_files;
    private $_mixins;
    private $_blocks;
    private $_hasAssignments;
    private $_level;

    public function __construct(array $options = null, Parser $parser = null, Lexer $lexer = null)
    {

        $this->_options = array_replace([
            'pretty' => true,
            'indentStyle' => Lexer::INDENT_SPACE,
            'indentWidth' => 4,
            'mode' => self::MODE_HTML,
            'selfClosingTags' => [
                'input', 'br', 'img'
            ],
            'selfRepeatingAttributes' => [
                'selected', 'checked', 'disabled'
            ],
            'doctypes' => [
                '5'             => '<!DOCTYPE html>',
                'html'          => '<!DOCTYPE html>',
                'xml'           => '<?xml version="1.0" encoding="utf-8"?>',
                'default'       => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
                'transitional'  => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
                'strict'        => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
                'frameset'      => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
                '1.1'           => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
                'basic'         => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">',
                'mobile'        => '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.2//EN" "http://www.openmobilealliance.org/tech/DTD/xhtml-mobile12.dtd">'
            ],
            'defaultTag' => 'div',
            'quoteStyle' => '"',
            'paths' => [],
            'extension' => '.jade',
            'parser' => [],
            'lexer' => []
        ], $options ? $options : []);

        $this->_lexer = $lexer ? $lexer : new Lexer($this->_options['lexer']);
        $this->_parser = $parser ? $parser : new Parser($this->_options['parser'], $lexer);
    }

    /**
     * @return array
     */
    public function getOptions()
    {

        return $this->_options;
    }

    /**
     * @return \Tale\Jade\Lexer
     */
    public function getLexer()
    {

        return $this->_lexer;
    }

    /**
     * @return \Tale\Jade\Parser
     */
    public function getParser()
    {

        return $this->_parser;
    }

    public function compile($input, $path = null)
    {

        $this->_files = $path ? [$path] : [];
        $this->_mixins = [];
        $this->_blocks = [];
        $this->_level = 0;

        $node = $this->_parser->parse($input);
        $this->handleImports($node);
        $this->handleBlocks($node);
        $phtml = $this->compileNode($node);


        $this->_level = 0;
        //Now we append/prepend specific stuff (like mixin functions and helpers)
        if ($this->_hasAssignments) {

            $phtml = $this->createCode('
$__assign = function($name, $values) {

    $values = [];

    foreach ($values as $value) {

        if (empty($value))
            continue;

        if ($name === \'class\')
            $values[] = (is_array($value) ? implode(\' \', $value) : $value);
        else
            $values[] = $value;
    }

    $value = \'\';

    if ($name === \'class\')
        $value = \' \'.implode(\' \', $values);
    else if (is_array($string) || is_object($string))
        $value = json_encode($values);
    else
        $value = implode(\'\', $values);

    return $value;
}').$this->newLine().$phtml;
        }

        return $phtml;
    }

    public function compileFile($path)
    {

        return $this->compile(file_get_contents($path), $path);
    }

    protected function isScalar($value)
    {

        return preg_match('/^([a-z0-9\_]+$|"[^"]*"|\'[^\']*\')$/i', $value);
    }

    protected function isVariable($value)
    {

        return preg_match('/^\$[a-z][a-z0-9\_\[\]\->\'" ]*$/i', $value);
    }

    protected function interpolate($string)
    {

        return preg_replace_callback('/\#\{([^\}]+)\}/', function($matches) {

            $subject = $matches[1];

            return $this->createShortCode("isset($subject) ? $subject : ''");
        }, $string);
    }

    protected function newLine()
    {

        return $this->_options['pretty']
               ? "\n"
               : '';
    }
    protected function indent($offset = 0)
    {

        return $this->_options['pretty']
               ? str_repeat($this->_options['indentStyle'], ($this->_level + $offset) * $this->_options['indentWidth'])
               : '';
    }

    protected function createCode($code, $prefix = '<?php ', $suffix = '?>')
    {

        if (strpos($code, "\n") !== false) {

            $this->_level++;
            $code = implode($this->newLine().$this->indent(), preg_split("/\n[\t ]*/", $code))
                  .$this->newLine().$this->indent(-1);
            $this->_level--;
        }

        return $prefix.$code.$suffix;
    }

    protected function createShortCode($code)
    {

        return $this->createCode($code, '<?=');
    }

    protected function createPhpComment($code)
    {

        return $this->createCode($code, '<?php /* ', ' */ ?>');
    }

    protected function createMarkupComment($code)
    {

        return $this->createCode($code, '<!-- ', ' -->');
    }

    protected function compileNode(Node $node)
    {

        $method = 'compile'.ucfirst($node->type);

        if (!method_exists($this, $method)) {
            /*$this->throwException(
                "No handler found",
                $node
            );*/

            var_dump('Unhandled '.$node->type."($method)");
            return $this->compileChildren($node->children);
        }

        return call_user_func([$this, $method], $node);
    }

    protected function compileDocument(Node $node)
    {

        return $this->compileChildren($node->children);
    }
    protected function compileDoctype(Node $node)
    {

        $name = $node->name;
        $value = isset($this->_options['doctypes'][$name]) ? $this->_options['doctypes'][$name] : $name;
        return $value;
    }

    private function _resolvePath($path)
    {

        $paths = $this->_options['paths'];

        if (count($this->_files) > 0)
            $paths[] = dirname(end($this->_files));

        if (empty($paths)) {

            //We got no paths to search in. We use the include-path in that case
            $paths = explode(\PATH_SEPARATOR, get_include_path());
        }

        foreach ($paths as $directory) {

            $fullPath = realpath(PathUtil::join($directory, $path));

            if ($fullPath)
                return $fullPath;
        }

        return false;
    }

    protected function handleImports(Node $node)
    {

        foreach ($node->find('import') as $importNode) {

            $this->handleImport($importNode);
        }

        return $this;
    }

    protected function handleImport(Node $node)
    {

        $path = $node->path;
        $ext = $this->_options['extension'];

        if (strncmp($path, $ext, strlen($ext) !== 0))
            $path .= $ext;

        $fullPath = $this->_resolvePath($path);

        if (!$fullPath)
            $this->throwException(
                "File $path wasnt found in ".implode(', ', $this->_options['paths'])
            );

        $importedNode = $this->_parser->parse(file_get_contents($fullPath));
        $this->_files[] = $path;
        $this->handleImports($importedNode);
        array_pop($this->_files);

        $node->parent->insertBefore($node, $importedNode);
        $node->parent->remove($node);

        return $this;
    }

    protected function handleBlocks(Node $node)
    {

        $this->_blocks = $node->findArray('block');
        foreach ($this->_blocks as $blockNode)
            $this->handleBlock($blockNode);

        return $this;
    }

    protected function handleBlock(Node $node)
    {

        if (!$node->name || $node->mode === 'ignore') //Will be handled through compileBlock when the loop encounters it
            return $this;

        //Find all other blocks with that name
        foreach ($this->_blocks as $block) {

            if ($block === $node || $block->name !== $node->name)
                continue;

            $mode = $block->mode;
            //detach from parent
            $block->parent->remove($block);

            switch ($mode) {
                default:
                case 'replace':

                    $node->children = [];
                //WANTED FALLTHROUGH!
                case 'append':

                    //Append to master block
                    foreach ($block->children as $child) {

                        $block->remove($child);
                        $node->append($child);
                    }
                    break;
                case 'prepend':

                    $last = null;
                    foreach ($block->children as $child) {

                        $block->remove($child);
                        if (!$last) {

                            $node->prepend($child);
                            $last = $child;
                            continue;
                        }

                        $node->insertAfter($last, $child);
                        $last = $child;
                    }
                    break;
            }

            $block->mode = 'ignore';
        }
    }

    protected function compileBlock(Node $node)
    {

        $name = $node->name;

        if (!$name)
            return $this->createShortCode('!empty($__block) ? $__block : \'\'');

        //At this point the code knows this block only, since handleBlock took care of the blocks previously
        return $this->compileChildren($node->children);
    }

    protected function compileConditional(Node $node)
    {

        $type = $node->conditionType;
        $subject = $node->subject;

        if ($this->isVariable($subject))
            $subject = "isset($subject) ? $subject : false";

        if ($type === 'unless') {

            $type = 'if';
            $subject = "!($subject)";
        }

        $phtml = $type === 'else' ? $this->createCode(' else {') : $this->createCode("$type ($subject) {");
        $phtml .= $this->compileChildren($node->children);
        $phtml .= $this->newLine().$this->indent().$this->createCode("}");

        return $phtml;
    }

    protected function compileChildren(array $nodes, $allowInline = false)
    {

        $phtml = '';
        $this->_level++;

        if (count($nodes) === 1 && $allowInline) {

            $compiled = $this->compileNode($nodes[0]);
            $this->_level--;
            return trim($compiled);
        }

        foreach ($nodes as $node) {

            if ($node->type === 'text' && !$this->_options['pretty'])
                $phtml .= ' ';

            $phtml .= $this->newLine().$this->indent().$this->compileNode($node);
        }
        $this->_level--;

        return $phtml;
    }

    protected function compileElement(Node $node)
    {
        $phtml = '';
        $assignedAttributes = [];

        if (!$node->tag)
            $node->tag = $this->_options['defaultTag'];

        if (count($node->assignments) > 0) {

            $this->_hasAssignments = true;
            $phtml .= $this->createCode('$__assignments = [];');
            foreach ($node->assignments as $assignment) {

                $phtml .= $this->newLine()
                        .$this->indent()
                        .$this->createCode('
$__assignments[\''.$assignment->name.'\'] = $__assign('
    .'\''.$assignment->name.'\','
    .'['.implode(',', array_map(function($attr) {

    return "isset({$attr->value}) ? {$attr->value} : null";
}, $assignment->attributes)).']);'
                        )
                        .$this->newLine();

                $assignedAttributes[] = $assignment->name;
            }
        }


        $phtml .= (count($node->assignments) > 0 ? $this->indent() : '')."<{$node->tag}";

        if (count($node->attributes) > 0 || count($assignedAttributes) > 0) {

            $attributes = [];
            foreach ($node->attributes as $attr) {

                if (isset($attributes[$attr->name]))
                    $attributes[$attr->name][] = $attr;
                else
                    $attributes[$attr->name] = [$attr];
            }

            foreach ($assignedAttributes as $name) {

                if (!isset($attributes[$name]))
                    $attributes[$name] = [];
            }

            $pairs = [];
            foreach ($attributes as $name => $attrs) {

                $values = [];
                foreach ($attrs as $attr) {

                    $value = $attr->value;
                    $pair = null;

                    if ($value) {

                        if ($this->isScalar($value)) {

                            $value = $this->interpolate($value);
                            $values[] = trim(trim($value), '"\'');
                            continue;
                        }

                        if ($this->isVariable($value)) {

                            $value = $this->createShortCode(
                                'empty('.$value.') ? '.$value.' : \'\''
                            );
                        }
                    } else if ($this->_options['mode'] == self::MODE_HTML && in_array($name, $this->_options['selfRepeatingAttributes'])) {

                        $values[] = $name;
                        continue;
                    }

                    $values[] = $value;
                }

                if (in_array($name, $assignedAttributes)) {

                    $values[] = $this->createShortCode(
                        'isset($__assignments[\''.$name.'\']) ? $__assignments[\''.$name.'\'] : \'\''
                    );
                }

                $pair = "$name";
                if (!empty($values)) {

                    $pair .= '='.$this->_options['quoteStyle'];
                    if ($name === 'class')
                        $pair .= implode(' ', $values);
                    else if (strncmp('data-', $name, 5) === 0)
                        $pair .= count($values) > 1 ? json_encode($values) : $values[0];
                    else
                        $pair .= implode('', $values);

                    $pair .= $this->_options['quoteStyle'];
                }

                $pairs[] = $pair;
            }


            if (!empty($pairs))
                $phtml .= ' '.implode(' ', $pairs);
        }

        $hasChildren = count($node->children) > 0;
        $isSelfClosing = in_array($node->tag, $this->_options['selfClosingTags']);

        if (!$hasChildren && !$isSelfClosing) {

            if ($this->_options['mode'] === self::MODE_HTML) {

                //Force closed tag in HTML
                $phtml .= "></{$node->tag}>";
                return $phtml;
            }

            //Allow /> closing in all other modes
            $phtml .= ' />';
            return $phtml;
        } else
            $phtml .= '>';

        if (!$hasChildren)
            return $phtml;

        $phtml .= $this->compileChildren($node->children);
        $phtml .= $this->newLine().$this->indent()."</{$node->tag}>";

        if (count($node->assignments) > 0) {

            $phtml .= $this->newLine().$this->indent().$this->createCode('unset($__assignments);');
        }

        return $phtml;
    }

    protected function compileText(Node $node)
    {

        return $this->interpolate($node->value).$this->compileChildren($node->children, true);
    }

    protected function compileExpression(Node $node)
    {

        $code = $node->escaped ? 'htmlentities(%s, \\ENT_QUOTES)' : '%s';

        if (count($node->children) === 1 && $node->children[0]->type === 'text' && $this->isVariable($node->children[0]->value)) {

            //We can have a single variable expression that uses isset automatically
            $value = $node->children[0]->value;
            return $this->createShortCode(sprintf($code, "isset({$value}) ? {$value} : ''"));
        }

        $method = $node->return ? 'createShortCode' : 'createCode';
        return $this->$method(sprintf($code, trim($this->compileChildren($node->children, true))));
    }

    protected function compileComment(Node $node)
    {

        $content = $this->compileChildren($node->children, true);
        return $node->rendered ? $this->createMarkupComment($content) : $this->createPhpComment($content);
    }

    protected function throwException($message, Node $relatedNode = null)
    {

        if ($relatedNode)
            $message .= "\n(".$relatedNode->type
                    .' at '.$relatedNode->line
                    .':'.$relatedNode->offset.')';

        throw new CompileException(
            "Failed to compile Jade: $message"
        );
    }
}