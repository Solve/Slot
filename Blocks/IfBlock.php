<?php
/*
 * This file is a part of Solve framework.
 *
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 * @copyright 2009-2014, Alexandr Viniychuk
 * created: 05.01.14 11:26
 */

namespace Solve\Slot\Blocks;

/**
 * Class IfBlock
 * @package Solve\Slot\Blocks
 *
 * @paired
 *
 * @version 1.0
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 */
class IfBlock extends BaseBlock {

    public function processBlockStart() {
        $res = '<?php ';
        $ifExpression = $this->_compiler->compileExpression($this->_token);
        $res .= 'if ('.$ifExpression.'):';

        $res .= ' ?>';
        return $res;
    }

    public function processBlockEnd() {
        return '<?php endif; ?>';
    }

} 