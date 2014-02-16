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
 * Class ElseBlock
 * @package Solve\Slot\Blocks
 *
 * @version 1.0
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 */
class ElseBlock extends BaseBlock {

    public function processBlockStart() {
        return '<?php else: ?>';
    }
}