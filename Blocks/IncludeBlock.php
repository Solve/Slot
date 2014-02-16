<?php
/*
 * This file is a part of Solve framework.
 *
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 * @copyright 2009-2014, Alexandr Viniychuk
 * created: 05.01.14 23:48
 */

namespace Solve\Slot\Blocks;


/**
 * Class IncludeBlock
 * @package Solve\Slot\Blocks
 *
 * @runtime
 *
 * @version 1.0
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 */
class IncludeBlock extends BaseBlock {

    public function processBlockStart() {

        $args = $this->_compiler->parseSpacedArguments($this->_token);
        $path = $args[0];

        if ($path[0] == '"' && $path[strlen($path) - 1] == '"') {
            $templatePath = substr($path, 1, -1);
        } else {
            $templatePath = $path;
        }
        $res = $this->_compiler->getSlot()->fetchTemplate($templatePath);
        return $res;
    }

} 