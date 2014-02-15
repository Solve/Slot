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

require_once '../Compiler.php';
require_once '../functions.php';

class CompilerTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Compiler
     */
    public $_compiler;

    public $_source;

    protected function setUp() {
        $this->_source['comments'] = file_get_contents('templates/comments.slot');
        $this->_compiler = new Compiler();
    }


    public function testComments() {
        $compiled = $this->_compiler->stripComments($this->_source['comments']);
        $this->assertEquals('just a text', trim($compiled));
    }

    public function testBraced() {
        $compiled = $this->_compiler->compileExpression('user[\'field\'.nameField[\'name\']]');
        vd($compiled);
    }


}
