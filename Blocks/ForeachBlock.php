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

    static private $_callStack  = array();

    public function processBlockStart() {
        $res            = '<?php ';
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

        $countName  = substr(md5(time()), 0, 10);
        $varFrom    = $this->_compiler->compileExpression($params['from']);
        $varKey     = $params['key'];
        $varItem    = '$__lv[\'' . $params['index'] . '\']';

        self::$_callStack[] = array(
            'modifiers' => $this->_modifiers,
            'countName' => $countName
        );

        if (!$this->hasModifier('noif')) {
            $ifFrom = strpos($varFrom, '()') === false ? $varFrom : '$__lv';
            $res .= 'if (!empty('.$ifFrom.') && count( '.$ifFrom.')) :' . "\n";
        }
        if (!$this->hasModifier('nocount')) {
            $foreachItemsVar = '$__lv[\'foreach_items_' . $countName . '\']';
            $res .= $foreachItemsVar .' = '.$varFrom.';' . "\n";
            $res .= '$__lv[\'foreach_count_'.$countName.'\'] = count('.$foreachItemsVar.');' . "\n";
        } else {
            $foreachItemsVar = $varFrom;
        }


        $res .= 'foreach(' . $foreachItemsVar;
        $res .= ' as ' . ($varKey ? '$__lv[\''.$varKey.'\'] => ' : '') . $varItem . ') : ?>';

        return $res;
    }

    public function processBlockEnd() {
        $call               = array_pop(self::$_callStack);
        $endIfBlock         = in_array('noif', $call['modifiers']) ? '' : ' endif;';
        $endForeachBlock    = array_key_exists('foreachElse', $call) ? '' : 'endforeach;';
        return '<?php '. $endForeachBlock . $endIfBlock . ' ?>';
    }


    public static function setForeachElse() {
        self::$_callStack[count(self::$_callStack) - 1]['foreachElse'] = true;
    }
} 