<?php
/*
 * This file is a part of Solve framework.
 *
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 * @copyright 2009-2014, Alexandr Viniychuk
 * created: 05.01.14 10:19
 */

namespace Solve\Slot;
use Solve\Slot\Blocks\BaseBlock;
use Solve\Storage\ArrayStorage;
use Solve\Utils\Inflector;
require_once 'vendor/autoload.php';

/**
 * Class Compiler
 * @package Solve\Slot
 *
 * Class Compiler is used to ...
 *
 * @version 1.0
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 */
class Compiler {

    /**
     * @var ArrayStorage tokens found stack
     */
    private $_tokenStack;

    /**
     * @var ArrayStorage registered blocks
     */
    private $_blocks;

    /**
     * @var array used for syntax check
     */
    private $_expectations = array();

    /**
     * @var ArrayStorage
     */
    private $_config            = array(
        'commentStart'  => '{#',
        'commentEnd'    => '#}',

        'tokenStart'    => '{{',
        'tokenEnd'      => '}}',

        'blockClose'    => 'end',
    );

    private $_escapingStrategy  = 'html';
    /**
     * @var Slot instance of SLOT class
     */
    private $_slotInstance;

    private $_hashesQuoted      = array();

    private $_hashScopeChar     = '@';

    /**
     * @var array methods that available as system modifiers
     */
    private $_predefinedMethods = array(
        'empty', 'is_null', 'isset', 'var_dump'
    );

    /**
     * @param Slot $slot
     */
    public function __construct(Slot $slot = null) {
        $this->_config          = new ArrayStorage((array)$this->_config);

        $this->_tokenStack      = new ArrayStorage();
        $this->_blocks          = new ArrayStorage();
        $this->_slotInstance    = $slot;
    }

    /**
     * Set config value partially of completely
     * @param $name
     * @param $value
     * @return $this
     */
    public function setConfig($name, $value) {
        if (is_null($name)) {
            $this->_config = new ArrayStorage($value);
        } else {
            $this->_config->setDeepValue($name, $value);
        }
        return $this;
    }

    /**
     * Register functional block for compiler. Each block should be represented with Class
     * @param $blockTag
     * @param $info
     */
    public function registerBlock($blockTag, $info) {
        $this->_blocks[$blockTag] = $info;
    }

    /**
     * @param $source
     * @return mixed
     */
    public function stripComments($source) {
        $source = preg_replace('/' . $this->_config['commentStart'] . '.*' . $this->_config['commentEnd'] . '/sU', '', $source);
        return $source;
    }

    /**
     * @param string $expression
     * @return string result
     */
    public function compileExpression($expression) {
        $expression = trim($expression);
        $quotedPattern = "#(?:(?:\"(?:\\\\\"|[^\"])+\")|(?:'(?:\\'|[^'])+'))#isU";
//        $matches = array();
//        preg_match_all($quotedPattern, $expression, $matches);
        $expression = preg_replace_callback($quotedPattern, array($this, '_quotesRegexpCallback'), $expression);

        $expression = $this->_processUnquotedExpression($expression);
        $expression = str_replace(array_keys($this->_hashesQuoted), $this->_hashesQuoted, $expression);
        $expression = str_replace('~', '.' , $expression);
        return $expression;
    }

    private function _processUnquotedExpression($expression) {
        $isSimpleExpression = true;
        $highLevelHash      = array();

        $squareBracePattern = '#\[[^\s]+\]#ism';
        $braced = array();
        preg_match_all($squareBracePattern, $expression, $braced);
        if (!empty($braced[0])) {
            foreach($braced[0] as $item) {
                $hash                 = $this->getHash($item);
                $highLevelHash[$hash] = '[' . $this->_processUnquotedExpression(substr($item, 1, -1)) . ']';
                $expression           = str_replace($item, $hash, $expression);
            }
        }

        $simpleBracePattern = '#\((([^()]+|(?R))*)\)#ism';
        $braced = array();
        preg_match_all($simpleBracePattern, $expression, $braced);
        if (!empty($braced[0])) {
            foreach($braced[0] as $item) {
                if ($item == '()') continue;

                $hash                 = $this->getHash($item);
                $highLevelHash[$hash] = '(' . $this->_processUnquotedExpression(substr($item, 1, -1)) . ')';
                $expression           = str_replace($item, $hash, $expression);
            }
        }

        if (strpos($expression, ' ') !== false) {
            $spacedParts = array();
            $spacePattern = '#"(?:\\\\.|[^\\\\"])*"|\S+#';
            preg_match_all($spacePattern, $expression, $spacedParts);
            if (!empty($spacedParts[0])) {
                $expression = '';
                foreach($spacedParts[0] as $item) {
                    $expression .= ' ' . $this->_processUnquotedExpression($item);
                }
                $expression = substr($expression, 1);
                $isSimpleExpression = false;
            }
        }

        if ($isSimpleExpression) {
            $modifiersInfo = $this->_processModifiers($expression);
            if (!empty($modifiersInfo[1])) {
                $expression = $modifiersInfo[0];
            }
            if (strpos($expression, '.') !== false) {
                $pointPattern = '#\.#';
                $pointedParts = preg_split($pointPattern, $expression, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
                $expression = array_shift($pointedParts);
                foreach($pointedParts as $part) {
                    if ($this->checkForSimpleString($part)) {
                        $value  = '[\'' . $part . '\']';
                    } else {
                        $value  = '[' . $this->_processUnquotedExpression($part) . ']';
                    }
                    $hash                 = $this->getHash($value);
                    $highLevelHash[$hash] = $value;
                    $expression          .= $hash;
                }
            }
            $varPattern = '#'.$this->_hashScopeChar.'\w+'.$this->_hashScopeChar.'|(->)?\w+(\|[_\w\d:]+)?#is';
            $expression = preg_replace_callback($varPattern, array($this, '_varRegexpCallback'), $expression);
            if (!empty($modifiersInfo[1])) {
                $expression = $modifiersInfo[1] . $expression . $modifiersInfo[2];
            }
        }

        $highLevelHash = array_reverse($highLevelHash);
        $expression = str_replace(array_keys($highLevelHash), $highLevelHash, $expression);
        return $expression;
    }

    public function checkForSimpleString($expression) {
        $matches = array();
        preg_match('#[_\w]{1}[_\w\d]*#', $expression, $matches);
        return $matches[0] === $expression;
    }

    /**
     * Preg match replace callback
     * @param $pregMatchResult
     * @return string
     */
    private function _varRegexpCallback($pregMatchResult) {
        $var = $pregMatchResult[0];
        if (($var[0] == $this->_hashScopeChar) && ($var[strlen($var) - 1] == $this->_hashScopeChar)) {
            return $var;
        }
        if (is_numeric($var)) {
            return $var;
        }
        if (!empty($pregMatchResult[1]) && ($pregMatchResult[1] == '->')) {
            return $var;
        }
        $info = $this->_processModifiers($var);

        return $info[1] . '$__lv[\'' . $info[0] . '\']' . $info[2];
    }

    /**
     * Preg match replace callback
     * @param $pregMatchResult
     * @return string
     */
    private function _quotesRegexpCallback($pregMatchResult) {
        $hash = $this->getHash($pregMatchResult[0]);
        $this->_hashesQuoted[$hash] = $pregMatchResult[0];
        return $hash;
    }

    private function getHash($string) {
        return $this->_hashScopeChar . md5($string) . $this->_hashScopeChar;
    }

    /**
     * Main compilartor function
     * @param $source
     * @return mixed
     */
    public function compileSource($source) {
        $source = $this->stripComments($source);

        $result = preg_replace_callback('#'.$this->_config['tokenStart']. '(.*)' . $this->_config['tokenEnd']. '#smU', array($this, 'onTokenFound'), $source);
        return $result;
    }

    /**
     * Since it called step by step we can create context for each tag
     * @param $token
     * @return string
     */
    private function onTokenFound($token) {
        if (is_array($token)) $token = array_pop($token);
        $token = trim($token);

        $tokenParts = explode(' ', $token);
        $tag        = array_shift($tokenParts);
        $params     = implode(' ', $tokenParts);

        if ($this->_blocks->has($tag)) {
            if ($this->_blocks->getDeepValue($tag . '/runtime')) {
                $res = '<?php $this->_compiler->invokeBlock(\''.$tag.'\', '. $this->compileExpression($params) .'); ?>';
            } else {
                $res = $this->onBlockTagOpen($tag, $params);
            }
        } elseif (substr($tag, 0, strlen($this->_config['blockClose'])) == $this->_config['blockClose']) {
            $tag = substr($tag, strlen($this->_config['blockClose']));
            $res = $this->onBlockTagClose($tag, $params);
        } else {
            $res = $this->onVarEchoToken($token, $params);
        }
        return $res;
    }

    /**
     * Add modifier surrounding for expression
     * @param $expression
     * @return array
     * @throws \Exception
     */
    private function _processModifiers($expression) {
        $mStart     = '';
        $mEnd       = '';
        $rawEcho    = false;

        /** process modifiers */
        if (strpos($expression, '|') !== false && strpos($expression, '||') === false) {
            $modifiers = explode('|', $expression);
            $expression  = array_shift($modifiers);
            foreach ($modifiers as $modifier) {
                $params = array();
                if (strpos($modifier, ':') !== false) {
                    $params = explode(':', $modifier);
                    $modifier = array_shift($params);
                }
                if ($modifier == 'raw') {
                    $rawEcho = true;
                    continue;
                }

                if ($this->isCallable($modifier)) {
                    $mStart = $modifier . '(' . $mStart;
                    if ($modifier !== 'raw') {
                        foreach($params as $param) {
                            $mEnd .= ','.$param;
                        }
                    }
                    $mEnd  .= ')';
                } else {
                    throw new \Exception('SLOT compiler error: undefined modifier '.$modifier);
                }
            }
        }

        return array(
            $expression,
            $mStart,
            $mEnd,
            $rawEcho
        );
    }

    private function onVarEchoToken($token) {
        $res = '<?php ';

        $varExpression  = $this->compileExpression($token);
        $varValue       = $varExpression;
        $isFunctional   = preg_match('#[-+*/\(|]+#', $varValue);
        $isRaw          = strpos($token, 'raw') !== false;
        /** process 01.vars escaping strategies */
        if ($this->getEscapingStrategy() == 'html' && $isRaw && !$isFunctional) {
            if (strpos($varExpression, 'htmlentities') === false) {
                $varValue  = 'htmlentities(' . $varValue . ', ENT_COMPAT, "UTF-8")';
            }
        }
        if ($isFunctional || ($isRaw && strpos($token, 'raw:full') !== false) || (strpos($varValue, '$') === false)) {
            $res .= 'echo '. $varValue;
        } else {
            $res .= 'echo !empty('. $varExpression . ') ? ' . $varValue . ': ""';
        }
        $res .= '; ?>';
        return $res;
    }

    public function invokeBlock($tag, $params) {
        echo $this->onBlockTagOpen($tag, $params);
    }

    /**
     * @param $tag
     * @param $token
     * @return string
     */
    private function onBlockTagOpen($tag, $token) {
        if ($this->_blocks->getDeepValue($tag . '/paired')) {
            $this->_expectations[] = $tag;
        }
        /**
         * @var BaseBlock $blockObject
         */
        $blockObject            = $this->getBlockObject($tag, $token);
        $this->_tokenStack[]    = $blockObject;

        return $blockObject->processBlockStart();
    }

    /**
     * @param $tag
     * @param $token
     * @return string
     * @throws \Exception
     */
    private function onBlockTagClose($tag, $token) {
        if ($this->_blocks->getDeepValue($tag . '/paired')) {
            if (!empty($this->_expectations)) {
                $expected = $this->_expectations[count($this->_expectations) - 1];
                if ($expected != $tag) {
                    throw new \Exception('Error parsing template: Unexpected tag "'.$tag.'", expected: "'.$expected.'"');
                } else {
                    array_pop($this->_expectations);
                }
            }
        } else {
            throw new \Exception('Error parsing template: Unexpected closing tag');
        }

        $blockObject = $this->getBlockObject($tag, $token);

        return $blockObject->processBlockEnd();
    }

    /**
     * @param $tag\
     * @param $token
     * @return BaseBlock
     */
    private function getBlockObject($tag, $token) {
        $blockClassName = '\Solve\Slot\Blocks\\' . Inflector::camelize($tag . 'Block');
        /**
         * @var BaseBlock $blockObject
         */
        $blockObject         = new $blockClassName($token, $this);
        return $blockObject;
    }

    private function isCallable($callable) {
        return in_array($callable, $this->_predefinedMethods) || $callable[0] === '!' || is_callable($callable);
    }

    /**
     * @param string $escapingStrategy
     */
    public function setEscapingStrategy($escapingStrategy) {
        $this->_escapingStrategy = $escapingStrategy;
    }

    /**
     * @return string
     */
    public function getEscapingStrategy() {
        return $this->_escapingStrategy;
    }

    /**
     * Set Slot instance
     * @param Slot $slot
     */
    public function setSlot(Slot $slot) {
        $this->_slotInstance = $slot;
    }

    /**
     * Get Slot instance
     * @return Slot
     */
    public function getSlot() {
        return $this->_slotInstance;
    }

    public function parseSpacedArguments($string) {
        $string      = trim($string);
        $spacedParts = array();
        $spacePattern = '#"(?:\\\\.|[^\\\\"])*"|\S+#';
        preg_match_all($spacePattern, $string, $spacedParts);

        return $spacedParts[0];
    }


}