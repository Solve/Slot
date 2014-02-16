<?php
/*
 * This file is a part of Solve framework.
 *
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 * @copyright 2009-2014, Alexandr Viniychuk
 * created: 15.02.14 18:40
 */

namespace Solve\Slot\Tests;

use Solve\Slot\Compiler;
use Solve\Slot\Slot;

require_once '../Compiler.php';
require_once '../functions.php';

class CompilerTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Compiler
     */
    public $_compiler;

    /**
     * @var Slot
     */
    public $_slot;

    protected function setUp() {
        $this->_compiler    = new Compiler();
        $this->_slot        = new Slot();
    }

    public function testVarsExpression() {
        $this->_testFileExpressions('templates/expressions/01.vars');
    }

    public function testOperandsExpression() {
        $this->_testFileExpressions('templates/expressions/02.operands');
    }

    public function testMethodsExpression() {
        $this->_testFileExpressions('templates/expressions/03.methods');
    }

    public function testModifiersExpression() {
        $this->_testFileExpressions('templates/expressions/04.modifiers');
    }

    public function testComments() {
        $this->_testFileTemplates('templates/00.comments.slot');
    }

    public function testVarsTemplate() {
        $this->_slot->getCompiler()->setEscapingStrategy('none');
        $this->_testFileTemplates('templates/01.vars.slot');
    }

    public function testControlTemplate() {
        $this->_slot->getCompiler()->setEscapingStrategy('none');
        $this->_testFileTemplates('templates/control_structure/01.include.slot');
        $this->_testFileTemplates('templates/control_structure/02.if.slot');
        $this->_testFileTemplates('templates/control_structure/03.foreach.slot');
    }

    public function testSlot() {
        $this->_testTemplatesExecution('02.complex.slot', array(
            'name'      => 'Sasha',
            'float'     => '9.2',
            'products'  => array(
                array(
                    'id'    => 1,
                    'title' => 'Wisky' . "\n",
                ),
                array(
                    'id'    => 2,
                    'title' => 'Apple' . "\n",
                )
            )
        ));
    }

    protected function _testTemplatesExecution($path, $vars) {
        $files = GLOB('templates/' . $path);
        if (empty($files)) {
            $this->fail('files not found in '.$path);
        }
        $this->_slot->setTemplateDir(__DIR__ . '/templates/');
        $this->_slot->setCompileDir(__DIR__ . '/compiled/');

        foreach($files as $file) {
            $expectation    = trim(file_get_contents('expectations/' . substr($file, 10, -4) . 'php'));
            $compiled       = trim($this->_slot->fetchTemplate('02.complex.slot', $vars));
            $this->assertEquals($expectation, $compiled, 'working with: '.$file);
        }
    }


    protected function _testFileTemplates($path) {
        $files = GLOB($path);
        if (empty($files)) {
            $this->fail('files not found in '.$path);
        }
        foreach($files as $file) {
            $source         = file_get_contents($file);
            $expectation    = trim(file_get_contents('expectations/' . substr($file, 10, -4) . 'php'));
            $compiled       = trim($this->_slot->getCompiler()->compileSource($source));
            $this->assertEquals($expectation, $compiled, 'working with: '.$file);
        }
    }


    protected function _testFileExpressions($path) {
        $files = GLOB($path);
        if (empty($files)) {
            $this->fail('files not found in '.$path);
        }
        foreach($files as $file) {
            $source         = file($file);
            $expectation    = file('expectations/' . substr($file, 10));
            foreach($source as $index => $expression) {
                $expected = trim($expectation[$index]);
                $compiled = $this->_compiler->compileExpression($expression);
                $this->assertEquals($expected, $compiled, 'working with: '.$expression);
            }
        }
    }

}
