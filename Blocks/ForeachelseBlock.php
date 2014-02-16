<?php
/*
 * This file is a part of Solve framework.
 *
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 * @copyright 2009-2014, Alexandr Viniychuk
 * created: 05.01.14 10:15
 */

namespace Solve\Slot\Blocks;

/**
 * Class ForeachelseBlock
 * @package Solve\Slot\Blocks
 *
 * @version 1.0
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 */
class ForeachelseBlock extends BaseBlock {

    public function processBlockStart() {
        ForeachBlock::setForeachElse();
        $res =  '<?php endforeach; ?><?php else: ?>';
        return $res;
    }

} 