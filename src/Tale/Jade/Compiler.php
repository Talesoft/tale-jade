<?php

namespace Tale\Jade;

class Compiler
{

    private $_options;
    private $_lexer;
    private $_parser;

    private $_paths;
    private $_mixins;
    private $_blocks;
    private $_level;

    public function __construct(array $options = null, Parser $parser = null, Lexer $lexer = null)
    {

        $this->_options = array_replace([
            'pretty' => false,
            'indentStyle' => Lexer::INDENT_SPACE,
            'indentWidth' => 4,
            'paths' => [],
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

    public function compile($input)
    {

        $this->_mixins = [];
        $this->_blocks = [];
        $this->_level = 0;

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
    protected function indent()
    {

        return $this->_options['pretty']
               ? str_repeat($this->_options['indentStyle'], $this->_level * $this->_options['indentWidth'])
               : '';
    }

    protected function createCode($code)
    {

        if (strpos($code, "\n") !== false) {

            $this->_level++;
            $code = $this->newLine()
                  .implode($this->newLine().$this->indent(), explode("\n", $code))
                  .$this->newLine().$this->indent();
            $this->_level--;
        }

        return '<?php '.$code.' ?>';
    }

    protected function createShortCode($code)
    {

        return '<?='.implode($this->newLine(), explode("\n", $code)).'?>';
    }

    protected function compileNode(Node $node)
    {

        $method = 'compile'.ucfirst($node->type);

        if (!method_exists($this, $method))
            /*$this->throwException(
                "No handler found",
                $node
            );*/
            return (string)$node;

        call_user_func([$this, $method], $node);
    }

    protected function compileElement(Node $node)
    {
        $phtml = '';
        $assignedAttributes = [];

        if (!empty($node->assignments)) {

            $this->createCode('$__assignments = []');
            foreach ($node->assignments as $assignment) {

                $phtml .= $this->newLine()
                        .$this->indent()
                        .$this->createCode(
                            '$__assignments[\''.$assignment->name.'\'] = \Tale\Jade\Compiler::assign('
                                .'\''.$assignment->name.'\','
                                .'['.implode(',', array_map(function($attr) {

                                return "isset({$attr->value}) ? {$attr->value} : null";
                            }, $assignment->attributes)).']);'
                        )
                        .$this->newLine();

                $assignedAttributes[] = $assignment->name;
            }
        }


        $phtml = $this->newLine().$this->indent()."<{$node->tag}";
        if (!empty($node->attributes)) {

            $phtml .= ' ';
            $attributes = [];
            foreach ($node->attributes as $attr) {

                $name = $attr->name;
                $isData = strncmp('data-', $name, 5) === 0;
                $value = $attr->value;

                if ($value) {

                    if ($this->isScalar($value)) {

                        $value = $this->interpolate($this->value);
                    } else if ($this->isVariable($value)) {

                        $value = $this->createShortCode("isset({$value}) ? {$value} : ")
                    }
                }

                if (in_array($name, $assignedAttributes)) {

                    $value = $value ? $value : '';
                    $value .= $this->createShortCode(
                        'isset($__assignments[\''.$name.'\']) ? $__assignments[\''.$name.'\'] : \'\''
                    );
                }
            }
        }
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