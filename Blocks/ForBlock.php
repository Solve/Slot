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
class ForBlock extends BaseBlock {

    public function processBlockStart() {
        return '<?php for($i = 0; $i < 5; $i++): ?>';
    }

    public function processBlockEnd() {
        return '<?php endfor; ?>';
    }
}