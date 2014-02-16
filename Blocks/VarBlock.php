<?php
/*
 * This file is a part of Solve framework.
 *
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 * @copyright 2009-2014, Alexandr Viniychuk
 * created: 05.01.14 10:16
 */

namespace Solve\Slot\Blocks;

/**
 * Class VarBlock
 * @package Solve\Slot
 *
 * @version 1.0
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 */
class VarBlock extends BaseBlock {

    public function processBlockStart() {
        $res = '<?php ' . $this->_compiler->compileExpression($this->_token) . '; ?>';

        return $res;
    }

} 