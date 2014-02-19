<?php
/*
 * This file is a part of Solve framework.
 *
 * @author Pavel Vodnyakoff <pavel.vodnyakoff@gmail.com>
 * @copyright 2009-2014, Pavel Vodnyakoff
 * created: 19.02.14 23:43
 */

namespace Solve\Slot\Blocks;


/**
 * Class ForBlock
 * @package Solve\Slot\Blocks
 *
 * @paired
 *
 * @version 1.0
 * @author Pavel Vodnyakoff <pavel.vodnyakoff@gmail.com>
 */
class ForBlock extends BaseBlock {

    public function processBlockStart() {
        return '<?php for($i = 0; $i < 5; $i++): ?>';
    }

    public function processBlockEnd() {
        return '<?php endfor; ?>';
    }
}