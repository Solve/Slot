<?php
/*
 * This file is a part of Solve framework.
 *
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 * @copyright 2009-2014, Alexandr Viniychuk
 * created: 05.01.14 10:15
 */

namespace Solve\Slot\Blocks;
use Solve\Storage\ParametersStorage;


/**
 * Class ForeachBlock
 * @package Solve\Slot\Blocks
 *
 * @paired
 *
 * @version 1.0
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 */
class ForeachBlock extends BaseBlock {

    public function processBlockStart() {
        $res = '<?php ';
        $params         = new ParametersStorage($this->_params, 'index', null, 'from');

        $extendedParams = array('index', null, 'from', 'key', 'name');
        if (count($this->_params) > 3) {
            for($i=3; $i < count($this->_params); ++$i) {
                $paramIndex = $extendedParams[$i - 3];
                if (($ePos = strpos($this->_params[$i], '=')) !== false) {
                    $paramIndex = substr($this->_params[$i], 0, $ePos);
                    $paramValue = substr($this->_params[$i], $ePos+1);
                } else {
                    $paramValue = $this->_params[$i];
                }
                $params->setDeepValue($paramIndex, $paramValue);
            }
        }

        $varFrom    = $this->_compiler->compileExpression($params['from']);
        $varKey     = $params['key'];
        $varItem    = '$__lv[\'' . $params['index'] . '\']';

//        if (!$this->hasModifier('noif')) {
//        }
        $ifFrom = strpos($varFrom, '()') === false ? $varFrom : '$__lv';
        $res .= 'if (!empty('.$ifFrom.') && count( '.$ifFrom.')) :' . "\n";

        $count_name = substr(md5(time()), 0, 10);
        $foreachItemsVar = '$__lv[\'foreach_items_' . $count_name . '\']';

        $res .= $foreachItemsVar .' = '.$varFrom.';' . "\n";
        $res .= '$__lv[\'foreach_count_'.$count_name.'\'] = count('.$foreachItemsVar.');' . "\n";
        $res .= 'foreach(' . $foreachItemsVar;
        $res .= ' as ' . ($varKey ? '$__lv[\''.$varKey.'\'] => ' : '') . $varItem . ') :?>';

        return $res;
    }

    public function processBlockEnd() {
        $endforeachblock = 'endforeach; ';
        return '<?php '. $endforeachblock .'endif; ?>';
    }


} 