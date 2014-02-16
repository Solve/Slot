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

    public function testBraced() {

//        $a = 'asd *xxxxx* asd*zzzzz*';
//        preg_match_all('#\*\w+\*|\w+#ism', $a, $matches);
//        vd($matches);

//        $compiled = $this->_compiler->compileExpression('var1 var2 var3');
//        $compiled = $this->_compiler->compileExpression('user[name]');
//        $compiled = $this->_compiler->compileExpression('user.name');
//        $compiled = $this->_compiler->compileExpression('user[name] user.name');
//        $compiled = $this->_compiler->compileExpression('user.name user.age');
//        $compiled = $this->_compiler->compileExpression('user.nameField[\'name.first\']');
//        $compiled = $this->_compiler->compileExpression('user["field".nameField[\'name.first\']] city[id_city]');
//        $compiled = $this->_compiler->compileExpression('user."field"');
//        $expression = '((a.a == b.b) || (c.a == (d.b == d.a)))';
//        $expression = '(a.a == b.b) || (c.a == (d.b == d.a))';
//        $simpleBracePattern = '#\((([^()]*|(?R))*)\)#';
//        $braced = array();
//        preg_match_all($simpleBracePattern, $expression, $braced);
//vd($braced);
//        $compiled = $this->_compiler->compileExpression('user->test()');
//        $compiled = $this->_compiler->compileExpression('a|var_dump');


//        $compiled = $this->_compiler->compileExpression('user["name"]|strtolower');
//        vd($compiled);
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
        $this->_testFileTemplates('templates/control_structure/01.if.slot');
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
