<?php
/*
 * This file is a part of Solve framework.
 *
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 * @copyright 2009-2014, Alexandr Viniychuk
 * created: 07.01.14 00:57
 */

namespace Solve\Slot\Blocks;


/**
 * Class EscapeStrategyBlock
 * @package Solve\Slot\Blocks
 *
 * @version 1.0
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 */
class EscapeStrategyBlock extends BaseBlock {

    public function processBlockStart() {
        if (empty($this->_params[0])) {
            $this->_params = array('html');
        }
        $this->_compiler->setEscapingStrategy($this->_params[0]);
    }

} 