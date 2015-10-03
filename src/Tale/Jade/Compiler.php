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
    private $_calledMixins;
    private $_blocks;
    private $_level;

    public function __construct(array $options = null, Parser $parser = null, Lexer $lexer = null)
    {

        $this->_options = array_replace_recursive([
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
            'filters' => [
                'css' => 'Tale\\Jade\\Filter::filterStyle',
                'js' => 'Tale\\Jade\\Filter::filterScript',
                'php' => 'Tale\\Jade\\Filter::filterCode',
                'markdown' => 'Tale\\Jade\\Filter::filterMarkdown'
                //What else?
            ],
            'handleErrors' => true,
            'compileUncalledMixins' => false,
            'allowImports' => true,
            'defaultTag' => 'div',
            'quoteStyle' => '"',
            'replaceMixins' => false,
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

    public function addPath($path)
    {

        $this->_options['paths'][] = $path;

        return $this;
    }

    public function addFilter($name, $callback)
    {

        if (!is_callable($callback))
            throw new \InvalidArgumentException(
                "Argument 2 of addFilter must be valid callback"
            );

        $this->_options['filters'][$name] = $callback;

        return $this;
    }

    public function compile($input, $path = null)
    {

        //Compiler reset
        $this->_files = $path ? [$path] : [];
        $this->_mixins = [];
        $this->_calledMixins = [];
        $this->_blocks = [];
        $this->_level = 0;

        //Parse the input into an AST
        $node = $this->_parser->parse($input);

        //There are some things we need to take care of before compilation
        $this->handleImports($node);
        $this->handleBlocks($node);
        $this->handleMixins($node);

        //The actual compilation process ($node is the very root node of everything)
        $phtml = $this->compileNode($node);


        //Reset the level again for our next operations
        $this->_level = 0;
        //Now we append/prepend specific stuff (like mixin functions and helpers)
        $errorHandler = $this->compileErrorHandlerHelper();
        $mixins = $this->compileMixins();


        //Put everything together
        $phtml = implode('', [$errorHandler, $mixins, $phtml]);

        if ($this->_options['handleErrors'])
            $phtml .= $this->createCode('restore_error_handler(); unset($__errorHandler);');

        //Reset the files after compilation so that compileFile may resolve correctly
        //Happens when you call compileFile twice on different files
        //Note that Compiler only uses the include-path, when there is no file in the
        //file name storage $_files
        $this->_files = [];

        //Return the compiled PHTML
        return $phtml;
    }

    public function compileFile($path)
    {

        $fullPath = $this->resolvePath($path);

        if (!$fullPath)
            throw new \Exception(
                "File $path wasnt found in ".implode(', ', $this->_options['paths'])
            );

        return $this->compile(file_get_contents($fullPath), $fullPath);
    }

    protected function isScalar($value)
    {

        return preg_match('/^([a-z0-9\_\-]+|"[^"]*"|\'[^\']*\')$/i', $value);
    }

    protected function isVariable($value)
    {

        return preg_match('/^\$[a-z_][a-z0-9\_\[\]\->\'"]*$/i', $value);
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
            $this->throwException(
                "No handler found",
                $node
            );

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

    public function resolvePath($path)
    {

        $paths = $this->_options['paths'];
        $ext = $this->_options['extension'];

        if (substr($path, -strlen($ext)) !== $ext)
            $path .= $ext;

        if (count($this->_files) > 0)
            $paths[] = dirname(end($this->_files));

        if (count($paths) < 1) {

            //We got no paths to search in. We use the include-path in that case
            $paths = explode(\PATH_SEPARATOR, get_include_path());
        }

        foreach ($paths as $directory) {

            //TODO: This is the only reference to tale-util right now
            //We might as well write a little join-wrapper here
            //and remove the dependency of tale-util in composer
            $fullPath = realpath(PathUtil::join($directory, $path));

            if ($fullPath)
                return $fullPath;
        }

        return false;
    }

    protected function handleImports(Node $node)
    {

        foreach ($node->find('import') as $importNode) {

            if (!$this->_options['allowImports'])
                $this->throwException(
                    'Imports are not allowed in this compiler instance',
                    $node
                );

            $this->handleImport($importNode);
        }

        return $this;
    }

    protected function handleImport(Node $node)
    {

        $path = $node->path;
        $fullPath = $this->resolvePath($path);

        if (!$fullPath)
            $this->throwException(
                "File $path wasnt found in ".implode(', ', $this->_options['paths']),
                $node
            );

        $importedNode = $this->_parser->parse(file_get_contents($fullPath));
        $this->_files[] = $fullPath;
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

    protected function handleMixins(Node $node)
    {

        $mixins = $node->findArray('mixin');

        //Save all mixins in $this->_mixins for our mixinCalls to reference them
        foreach ($mixins as $mixinNode) {

            if (isset($this->_mixins[$mixinNode->name]) && !$this->_options['replaceMixins'])
                $this->throwException(
                    "Duplicate mixin name $mixinNode->name",
                    $mixinNode
                );

            $this->_mixins[$mixinNode->name] = $mixinNode;
        }

        //Handle the mixins
        foreach ($this->_mixins as $mixinNode) {
            $this->handleMixin($mixinNode);
        }

        return $this;
    }

    protected function handleMixin(Node $node)
    {

        //Find the absolute document root
        $root = $node;
        while($root->parent)
            $root = $root->parent;

        //Detach
        $node->parent->remove($node);

        $this->_mixins[$node->name] = ['node' => $node, 'phtml' => $this->compileChildren($node->children)];

        return $this;
    }

    protected function compileMixins()
    {

        if (count($this->_mixins) < 1)
            return '';

        $phtml = '';
        $phtml .= $this->createCode('$__args = isset($__args) ? $__args : [];').$this->newLine();
        $phtml .= $this->createCode('$__mixins = [];').$this->newLine();

        foreach ($this->_mixins as $name => $mixin) {

            //Don't compile the mixin if we dont use it (opt-out)
            if (!$this->_options['compileUncalledMixins'] && !in_array($name, $this->_calledMixins, true))
                continue; //Skip compilation

            //Put the arguments together
            $args = [];
            foreach ($mixin['node']->attributes as $attr) {

                $args[$attr->name] = $attr->value;
            }

            $phtml .= $this->createCode(
                '$__mixins[\''.$name.'\'] = function(array $__callArgs) use($__args, $__mixins) {
                    static $__mixinArgs = '.var_export($args, true).';
                    extract($__args);
                    extract($__mixinArgs);
                    extract($__callArgs);
                '
            ).$this->newLine();

            $phtml .= $mixin['phtml'].$this->newLine();
            $phtml .= $this->createCode('};').$this->newLine();
        }

        return $phtml;
    }

    protected function compileMixinCall(Node $node)
    {

        $name = $node->name;

        if (!isset($this->_mixins[$name]))
            $this->throwException(
                "Mixin $name is not defined",
                $node
            );

        if (!in_array($name, $this->_calledMixins, true))
            $this->_calledMixins[] = $name;

        $mixin = $this->_mixins[$name];
        $phtml = '';

        if (count($node->children) > 0) {

            $phtml = $this->createCode(
                    '$__block = function(array $__callArgs) use($__args, $__mixins) {
                extract($__args);
                extract($__callArgs);
            '
                ).$this->newLine();
            $phtml .= $this->compileChildren($node->children).$this->newLine();
            $phtml .= $this->indent().$this->createCode('};').$this->newLine();
        }

        $args = [];
        foreach ($node->attributes as $attr) {

            $value = $attr->value;

            if ($this->isScalar($value)) {

                $value = trim($this->interpolate($value), '\'"');
            }

            if ($attr->name) {

                if (isset($args[$attr->name])) {

                    if (is_array($args[$attr->name]))
                        $args[$attr->name][] = $value;
                    else
                        $args[$attr->name] = [$args[$attr->name], $value];
                } else {

                    $args[$attr->name] = $value;
                }
                continue;
            }

            foreach ($mixin['node']->attributes as $mixinAttr) {

                if (isset($args[$mixinAttr->name]))
                    continue;

                $args[$mixinAttr->name] = $value;
            }
        }

        $phtml .= (count($node->children) > 0 ? $this->indent() : '').$this->createCode(
            '$__mixinCallArgs = '.var_export($args, true).';
            $__mixinCallArgs[\'__block\'] = isset($__block) ? $__block : null;
            call_user_func($__mixins[\''.$name.'\'], $__mixinCallArgs);
            unset($__mixinCallArgs);
            unset($__block);'
        ).$this->newLine();

        return $phtml;
    }

    protected function compileBlock(Node $node)
    {

        $name = $node->name;

        if (!$name)
            return $this->createShortCode('isset($__block) && $__block instanceof \Closure ? $__block(array_replace($__args, $__callArgs)) : \'\'');

        //At this point the code knows this block only, since handleBlock took care of the blocks previously
        return $this->compileChildren($node->children);
    }

    protected function compileConditional(Node $node)
    {

        $type = $node->conditionType;
        $subject = $node->subject;

        if ($subject === 'block')
            $subject = '$__block';

        if ($this->isVariable($subject))
            $subject = "isset($subject) ? $subject : false";

        if ($type === 'unless') {

            $type = 'if';
            $subject = "!($subject)";
        }

        $isPrevConditional = $node->prev() && $node->prev()->type === 'conditional';
        $isNextConditional = $node->next()
                          && $node->next()->type === 'conditional'
                          && $node->next()->conditionType !== 'if';
        $prefix = $isPrevConditional ? '' : '<?php ';
        $suffix = $isNextConditional ? '' : '?>';
        $phtml = $type === 'else'
               ? $this->createCode(' else {', $prefix)
               : $this->createCode("$type ($subject) {", $prefix);
        $phtml .= $this->compileChildren($node->children);
        $phtml .= $this->newLine().$this->indent().$this->createCode("}", '<?php ', $suffix);

        return $phtml;
    }

    protected function compileCase(Node $node)
    {

        $subject = $node->subject;

        if ($this->isVariable($subject))
            $subject = "isset({$subject}) ? {$subject} : null";

        $phtml = $this->createCode("switch ({$subject}) { case ':JADE_BREAK:': break;").$this->newLine();
        $phtml .= $this->compileChildren($node->children).$this->newLine();
        $phtml .= $this->indent().$this->createCode('}');

        return $phtml;
    }

    protected function compileWhen(Node $node)
    {

        $subject = $node->subject;

        if ($subject && $this->isVariable($subject))
            $subject = "isset({$subject}) ? {$subject} : null";

        $phtml = $this->createCode($node->default ? 'default:' : "case $subject:").$this->newLine();
        $phtml .= $this->compileChildren($node->children).$this->newLine();

        if (count($node->children) > 0)
            $phtml .= $this->indent().$this->createCode('break;');

        return $phtml;
    }

    protected function compileEach(Node $node)
    {

        static $id = 0;

        $subject = $node->subject;

        if ($this->isVariable($subject))
            $subject = "isset({$subject}) ? {$subject} : null";

        $as = "\${$node->itemName}";
        if ($node->keyName)
            $as .= " => \${$node->keyName}";

        $var = '$__iterator'.($id++);
        $phtml = $this->createCode("$var = {$subject};").$this->newLine();
        $phtml .= $this->indent().$this->createCode("foreach ($var as $as) {").$this->newLine();
        $phtml .= $this->compileChildren($node->children).$this->newLine();
        $phtml .= $this->indent().$this->createCode('}').$this->newLine();
        $phtml .= $this->indent().$this->createCode("unset($var);");

        return $phtml;
    }

    protected function compileWhile(Node $node)
    {

        $subject = $node->subject;

        if ($this->isVariable($subject))
            $subject = "isset({$subject}) ? {$subject} : null";

        $phtml = $this->createCode("while ({$subject}) {").$this->newLine();
        $phtml .= $this->compileChildren($node->children).$this->newLine();
        $phtml .= $this->indent().$this->createCode('}').$this->newLine();

        return $phtml;
    }

    protected function compileDo(Node $node)
    {

        $subject = $node->subject;

        if (!empty($subject))
            $this->throwException(
                "Do can't have a subject",
                $node
            );

        $phtml = $this->createCode("do {").$this->newLine();
        $phtml .= $this->compileChildren($node->children).$this->newLine();
        $phtml .= $this->indent().$this->createCode('}').$this->newLine();

        return $phtml;
    }

    protected function compileFilter(Node $node)
    {

        $name = $node->name;

        if (!isset($this->_options['filters'][$name]))
            $this->throwException(
                "Filter $name doesnt exist",
                $node
            );

        $result = call_user_func($this->_options['filters'][$name], $node, $this);

        return $result instanceof Node ? $this->compileNode($result) : (string)$result;
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

            if ($node->type === 'text' && !$this->_options['pretty']) {

                $phtml .= ' ';
            }

            $phtml .= $this->newLine().$this->indent().$this->compileNode($node);
        }
        $this->_level--;

        return $phtml;
    }

    protected function compileElement(Node $node)
    {

        $phtml = '';

        if (!$node->tag)
            $node->tag = $this->_options['defaultTag'];

        $phtml .= "<{$node->tag}";

        $nodeAttributes = $node->attributes;
        foreach ($node->assignments as $assignment) {

            $name = $assignment->name;

            //This line provides compatibility to the offical jade method
            if ($this->_options['mode'] === self::MODE_HTML && $name === 'classes')
                $name = 'class';

            foreach ($assignment->attributes as $attr) {

                if (!$attr->value)
                    $attr->value = $attr->name;

                $attr->name = $name;
                $nodeAttributes[] = $attr;
            }
        }

        if (count($nodeAttributes) > 0) {

            $attributes = [];
            foreach ($nodeAttributes as $attr) {

                if (isset($attributes[$attr->name]))
                    $attributes[$attr->name][] = $attr;
                else
                    $attributes[$attr->name] = [$attr];
            }

            foreach ($attributes as $name => $attrs) {

                $values = [];
                foreach ($attrs as $attr) {

                    $value = trim($attr->value);

                    if ($value) {

                        if ($this->isScalar($value)) {

                            $value = $this->interpolate($value);
                            $values[] = $value;
                            continue;
                        } else if ($this->isVariable($value)) {

                            $values[] = 'isset('.$value.') ? '.$value.' : false';
                        } else {

                            $values[] = $value;
                        }
                    }
                }

                if ($this->_options['mode'] === self::MODE_HTML && count($values) < 1 && in_array($name, $this->_options['selfRepeatingAttributes'])) {

                    $values[] = $name;
                }

                $quot = $this->_options['quoteStyle'];
                $builder = '\Tale\Jade\Compiler::buildValue';

                //Handle specific attribute styles for HTML
                if ($this->_options['mode'] === self::MODE_HTML) {

                    switch ($name) {
                        case 'class': $builder = '\Tale\Jade\Compiler::buildClassValue'; break;
                        case 'style': $builder = '\Tale\Jade\Compiler::buildStyleValue'; break;
                    }
                }

                //If all values are scalar, we don't do any kind of resolution for
                //the attribute name. It's always there.

                $escaped = $attr->escaped ? 'true' : 'false';

                $pair = '';
                if (count(array_filter($values, [$this, 'isScalar'])) === count($values)) {

                    //Print the normal pair
                    //We got all scalar values, we can evaluate them directly, so no code needed in the PHTML output
                    $pair .= " $name=";
                    $values = array_map(function($val) { return trim($val, '\'"'); }, $values);
                    $pair .= call_user_func($builder, count($values) === 1 ? $values[0] : $values , $quot, $escaped === 'true');
                } else {

                    //If there's any kind of expression in the attribute, we
                    //also check, if something of the expression is false or null
                    //and if it is, we don't print the attribute

                    $values = array_map(function($val) use($quot) {

                        return $this->isScalar($val) ? $quot.trim($val, '\'"').$quot : $val;
                    }, $values);

                    $quot = $quot === '\'' ? '\\\'' : $quot;
                    //We don't need to run big array stuff if there's only one value
                    if (count($values) === 1) {

                        $pair = $this->createCode(
                            '$__value = '.$values[0].'; '
                            .'if (!\\Tale\\Jade\\Compiler::isNullOrFalse($__value)) '
                                ."echo ' $name='.$builder(\$__value, '$quot', $escaped); "
                            .'unset($__value);'
                        );
                    } else {

                        $pair = $this->createCode(
                            '$__values = ['.implode(', ', $values).']; '
                            .'if (!\\Tale\\Jade\\Compiler::isArrayNullOrFalse($__values)) '
                                ."echo ' $name='.$builder(\$__values, '$quot', $escaped); "
                            .'unset($__values);'
                        );
                    }
                }

                $phtml .= $pair;
            }
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

    protected function compileErrorHandlerHelper()
    {

        $phtml = '';
        if ($this->_options['handleErrors']) {

            $phtml = $this->createCode(
                    '$__errorHandler = function($code, $message, $file, $line) {

                        if (!(error_reporting() & $code))
                            return;

                        throw new \ErrorException($message, 0, $code, $file, $line);
                    };
                    set_error_handler($__errorHandler);'
                ).$this->newLine();
        }

        return $phtml;
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


    public static function buildValue($value, $quoteStyle, $escaped)
    {

        if (self::isObjectOrArray($value))
            return '\''.json_encode($value).'\'';

        return $quoteStyle.($escaped ? htmlentities($value, \ENT_QUOTES) : ((string)$value)).$quoteStyle;
    }

    public static function buildStyleValue($value, $quoteStyle)
    {

        if (is_object($value))
            $value = (array)$value;

        if (is_array($value))
            $value = self::flatten($value, '; ');

        return $quoteStyle.((string)$value).$quoteStyle;
    }

    public static function buildClassValue($value, $quoteStyle)
    {

        if (is_object($value))
            $value = (array)$value;

        if (is_array($value))
            $value = self::flatten($value);

        return $quoteStyle.((string)$value).$quoteStyle;
    }

    public static function isNullOrFalse($value)
    {

        return $value === null || $value === false;
    }

    public static function isArrayNullOrFalse(array $value)
    {

        return count(array_filter($value, [self::class, 'isNullOrFalse'])) === count($value);
    }

    public static function isObjectOrArray($value)
    {

        return is_object($value) || is_array($value);
    }

    public static function flatten(array $array, $separator = ' ', $argSeparator = '=')
    {

        $items = [];
        foreach ($array as $key => $value) {

            if (is_object($value))
                $value = (array)$value;

            if (is_array($value))
                $value = self::flatten($value, $separator, $argSeparator);

            if (is_string($key))
                $items[] = "$key$argSeparator$value";
            else
                $items[] = $value;
        }

        return implode($separator, $items);
    }
}