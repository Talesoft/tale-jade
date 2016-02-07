<?php
/**
 * The Tale Jade Lexer.
 *
 * Contains the a lexer that analyzes the input-jade and generates
 * tokens out of it via a PHP Generator
 *
 * This file is part of the Tale Jade Template Engine for PHP
 *
 * LICENSE:
 * The code of this file is distributed under the MIT license.
 * If you didn't receive a copy of the license text, you can
 * read it here http://licenses.talesoft.io/2015/MIT.txt
 *
 * @category   Presentation
 * @package    Tale\Jade
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Talesoft (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.0
 * @link       http://jade.talesoft.io/docs/files/Lexer.html
 * @since      File available since Release 1.0
 */

namespace Tale\Jade;

use Tale\ConfigurableTrait;
use Tale\Jade\Lexer\Reader;
use Tale\Jade\Lexer\Scanner\AssignmentScanner;
use Tale\Jade\Lexer\Scanner\AttributeScanner;
use Tale\Jade\Lexer\Scanner\BlockScanner;
use Tale\Jade\Lexer\Scanner\CaseScanner;
use Tale\Jade\Lexer\Scanner\ClassScanner;
use Tale\Jade\Lexer\Scanner\CodeScanner;
use Tale\Jade\Lexer\Scanner\CommentScanner;
use Tale\Jade\Lexer\Scanner\ConditionalScanner;
use Tale\Jade\Lexer\Scanner\DoctypeScanner;
use Tale\Jade\Lexer\Scanner\DoScanner;
use Tale\Jade\Lexer\Scanner\EachScanner;
use Tale\Jade\Lexer\Scanner\ExpressionScanner;
use Tale\Jade\Lexer\Scanner\FilterScanner;
use Tale\Jade\Lexer\Scanner\ForScanner;
use Tale\Jade\Lexer\Scanner\IdScanner;
use Tale\Jade\Lexer\Scanner\ImportScanner;
use Tale\Jade\Lexer\Scanner\IndentationScanner;
use Tale\Jade\Lexer\Scanner\MarkupScanner;
use Tale\Jade\Lexer\Scanner\MixinCallScanner;
use Tale\Jade\Lexer\Scanner\MixinScanner;
use Tale\Jade\Lexer\Scanner\NewLineScanner;
use Tale\Jade\Lexer\Scanner\TagScanner;
use Tale\Jade\Lexer\Scanner\TextLineScanner;
use Tale\Jade\Lexer\Scanner\TextScanner;
use Tale\Jade\Lexer\Scanner\VariableScanner;
use Tale\Jade\Lexer\Scanner\WhenScanner;
use Tale\Jade\Lexer\Scanner\WhileScanner;
use Tale\Jade\Lexer\ScannerInterface;
use Tale\Jade\Lexer\State;

/**
 * Performs lexical analysis and provides a token generator.
 *
 * Tokens are defined as single units of code
 * (e.g. tag, class, id, attributeStart, attribute, attributeEnd)
 *
 * These will run through the parser and be converted to an AST
 *
 * The lexer works sequentially, ->lex will return a generator and
 * you can read that generator in any manner you like.
 * The generator will produce valid tokens until the end of the passed
 * input.
 *
 * Usage example:
 * <code>
 *
 *     use Tale\Jade\Lexer;
 *
 *     $lexer = new Lexer();
 *
 *     foreach ($lexer->lex($jadeInput) as $token)
 *          echo $token;
 *
 *     //Prints a human-readable dump of the generated tokens
 *
 * </code>
 *
 * @category   Presentation
 * @package    Tale\Jade
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Talesoft (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.4.0
 * @link       http://jade.talesoft.io/docs/classes/Tale.Jade.Lexer.html
 * @since      File available since Release 1.0
 */
class Lexer
{
    use ConfigurableTrait;

    const INDENT_SPACE = ' ';
    const INDENT_TAB = "\t";

    /**
     * @var State
     */
    private $_state;

    private $_scanners;

    /**
     * Creates a new lexer instance.
     *
     * The options should be an associative array
     *
     * Valid options are:
     *
     * indentStyle:     The indentation character (auto-detected)
     * indentWidth:     How often to repeat indentStyle (auto-detected)
     * encoding:        The encoding when working with mb_*-functions (auto-detected)
     * scans:           An array of scans that will be performed
     *
     * Passing an indentation-style forces you to stick to that style.
     * If not, the lexer will assume the first indentation type it finds as the indentation.
     * Mixed indentation is not possible, since it would be a bitch to calculate without
     * taking away configuration freedom
     *
     * Add a new scan to 'scans' to extend the lexer.
     * Notice that you need the fitting 'handle*'-method in the parser
     * or you will get unhandled-token-exceptions.
     *
     * @param array|null $options the options passed to the lexer instance
     *
     * @TODO: Maybe the scanners could be respresented as a tree,
     *        that may remove many ->scan calls inside the scanners
     *        and would make scan modifications easier
     *
     * @throws \Exception
     */
    public function __construct(array $options = null)
    {

        $this->defineOptions([
            'stateClassName' => State::class,
            'level' => 0,
            'indentStyle' => null,
            'indentWidth' => null,
            'encoding'    => Lexer\get_internal_encoding(),
            'scanners' => [
                'newLine' => new NewLineScanner(),
                'indent' => new IndentationScanner(),
                'import' => new ImportScanner(),
                'block' => new BlockScanner(),
                'conditional' => new ConditionalScanner(),
                'each' => new EachScanner(),
                'case' => new CaseScanner(),
                'when' => new WhenScanner(),
                'do' => new DoScanner(),
                'while' => new WhileScanner(),
                'for' => new ForScanner(),
                'mixin' => new MixinScanner(),
                'mixinCall' => new MixinCallScanner(),
                'doctype' => new DoctypeScanner(),
                'tag' => new TagScanner(),
                'class' => new ClassScanner(),
                'id' => new IdScanner(),
                'attribute' => new AttributeScanner(),
                'assignment' => new AssignmentScanner(),
                'variable' => new VariableScanner(),
                'comment' => new CommentScanner(),
                'filter' => new FilterScanner(),
                'expression' => new ExpressionScanner(),
                'code' => new CodeScanner(),
                'markup' => new MarkupScanner(),
                'textLine' => new TextLineScanner(),
                'text' => new TextScanner()
            ]
        ], $options);

        $this->_state = null;
        $this->_scanners = [];

        foreach ($this->getOption('scanners') as $name => $scanner)
            $this->setScanner($name, $scanner);
    }

    /**
     * @return State
     */
    public function getState()
    {

        if (!$this->_state)
            throw new \RuntimeException(
                "Failed to get state: No lexing process active. "
                ."Use the lex method"
            );

        return $this->_state;
    }

    public function setScanner($name, $scanner)
    {

        if (!is_subclass_of($scanner, ScannerInterface::class))
            throw new \InvalidArgumentException(
                "Scanner $scanner is not a valid ".ScannerInterface::class
            );

        $this->_scanners[$name] = $scanner;

        return $this;
    }

    /**
     * Returns a generator that will lex the passed $input sequentially.
     *
     * If you don't move the generator, the lexer does nothing.
     * Only as soon as you iterate the generator or call next()/current() on it
     * the lexer will start it's work and spit out tokens sequentially.
     * This approach takes less memory during the lexing process.
     *
     * Tokens are always an array and always provide the following keys:
     * <samp>
     * [
     *      'type' => The token type,
     *      'line' => The line this token is on,
     *      'offset' => The offset this token is at
     * ]
     * </samp>
     *
     * @param string $input the Jade-string to lex into tokens
     *
     * @return \Generator a generator that can be iterated sequentially
     */
    public function lex($input)
    {

        $stateClassName = $this->getOption('stateClassName');

        if (!is_a($stateClassName, State::class, true))
            throw new \InvalidArgumentException(
                'stateClassName needs to be a valid '.State::class.' sub class'
            );

        $this->_state = new State([
            'input' => $input,
            'encoding' => $this->getOption('encoding'),
            'indentStyle' => $this->getOption('indentStyle'),
            'indentWidth' => $this->getOption('indentWidth'),
            'level' => $this->getOption('level')
        ]);

        foreach ($this->_state->loopScan($this->_scanners) as $token)
            yield $token;

        $this->_state = null;
    }
}