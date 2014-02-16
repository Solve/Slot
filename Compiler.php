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

        $quotes = array('"', "'");

        $quotedPattern = "#(?:(?:\"(?:\\\\\"|[^\"])+\")|(?:'(?:\\'|[^'])+'))#isU";
        $matches = array();
//        preg_match_all($quotedPattern, $expression, $matches);
        $expression = preg_replace_callback($quotedPattern, array($this, '_processQuotedHash'), $expression);

        $expression = $this->_processUnquotedExpression($expression);
//vd($expression);


//        $haystack = "something else before 'Lars\' Teststring in quotes' something else after";
//        preg_match("/(?:(?:\"(?:\\\\\"|[^\"])+\")|(?:'(?:\\'|[^'])+'))/is",$haystack,$match);

//        if (in_array($expression[0], $quotes) && ($expression[0] == $expression[mb_strlen($expression) - 1])) {
//            return $expression;
//        }
        $expression = str_replace(array_keys($this->_hashesQuoted), $this->_hashesQuoted, $expression);
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
                $hash = '*'.md5($item).'*';
                $highLevelHash[$hash] = '[' . $this->_processUnquotedExpression(substr($item, 1, -1)) . ']';
                $expression = str_replace($item, $hash, $expression);
            }
//            $isSimpleExpression = false;
        }

        if (strpos($expression, '.') !== false) {
            $pointPattern = '#\.#';
            $pointedParts = preg_split($pointPattern, $expression, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            $expression = array_shift($pointedParts);
            foreach($pointedParts as $part) {
                $expression .= '[' . $this->_processUnquotedExpression($part) . ']';
            }
            $isSimpleExpression = false;
        }

        if ($isSimpleExpression) {
            $varPattern = '#\*\w+\*|\w+#is';
            $expression = preg_replace_callback($varPattern, array($this, '_processVarExpression'), $expression);
        }

//        preg_match_all($varPattern, $expression, $matches);
        $expression = str_replace(array_keys($highLevelHash), $highLevelHash, $expression);
        return $expression;
    }


    /**
     * Preg match replace callback
     * @param $pregMatchResult
     * @return string
     */
    private function _processVarExpression($pregMatchResult) {
        if (($pregMatchResult[0][0] == '*') && ($pregMatchResult[0][strlen($pregMatchResult[0]) - 1] == '*')) {
            return $pregMatchResult[0];
        }
        return '$__lv[\'' . $pregMatchResult[0] . '\']';
    }

    /**
     * Preg match replace callback
     * @param $pregMatchResult
     * @return string
     */
    private function _processQuotedHash($pregMatchResult) {
        $hash = '*' . md5($pregMatchResult[0]) . '*';
        $this->_hashesQuoted[$hash] = $pregMatchResult[0];
        return $hash;
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
                $res = '<?php $this->_compiler->invokeBlock(\''.$tag.'\', '. $this->compileExpressionToken($params) .'); ?>';
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

    public function invokeBlock($tag, $params) {
        echo $this->onBlockTagOpen($tag, $params);
    }

    public function compileExpressionToken($varToken) {
        if (is_array($varToken)) $varToken = array_pop($varToken);

        $varToken = trim($varToken);
        if ($varToken[0] == "'" && $varToken[strlen($varToken) - 1] == "'") {
            return $varToken;
        } elseif (is_numeric($varToken)) {
            return $varToken;
        }
        $eStart      = '';
        $eEnd        = '';
        $msc      = $this->processModifiers($varToken);
        $varToken = $msc[0];
        $mStart   = $msc[1];
        $mEnd     = $msc[2];

        $eStart .= '$__lv';

        if (strpos($varToken, '.') !== false) {
            $varParts = explode('.', $varToken);
        } else {
            $varParts = array($varToken);
        }
        foreach($varParts as $varPart) {
            if (strpos($varPart, '(') !== false) {
                //(\b(?<!['"])[a-zA-Z_][a-zA-Z_0-9]*\b(?!['"]))
                //'/[\w\d]+(?=(?:(?:(?:[^"\\]++|\\.)*+"){2})*+(?:[^"\\]++|\\.)*+$)/'
                return $mStart . \preg_replace_callback('#(("|->)?[\w\d]+"?)#is', array($this, 'compileSingleExpression'), $varPart) . $mEnd;
            } else {
                $eStart .= $this->compileVarOrFunction($varPart);
            }
        }
        return $mStart . $eStart . $eEnd . $mEnd;
    }

    public function compileLogicalExpression($expression) {
        $msc        = $this->processModifiers($expression);
        $expression = $msc[0];

        if ($expression[0] !== '(') {
            $expression = '(' . $expression . ')';
        }

        $bracedRegexp = '#\(([->\'"\\.\!=\[\]\w\d\s]+(\([\w\d]*\))?)\)#ismU';
//        $braced = array();
//        preg_match_all($bracedRegexp, $expression, $braced);
//        vd($expression, $braced);
        $expression = preg_replace_callback($bracedRegexp, array($this, 'onLogicalExpressionToken'), $expression);
        if ($msc[1] && ($expression[0] == '(')) {
            $expression = substr($expression, 1, -1);
        }

        return $msc[1] . $expression . $msc[2];
    }

    public function onLogicalExpressionToken($pregMatchResults) {
//        vd($pregMatchResults);
        $result = '';
        $input = $pregMatchResults[0];
        if (!empty($pregMatchResults[1])) {
            $input = $pregMatchResults[1];
        }
        $varParts = explode(' ', $input);
        foreach($varParts as $part) {
            $part = trim($part);
            if (strlen($part)) {
                if (!in_array($part, array('==', '!=', '<', '>', '===', '!===')) && !is_numeric($part)) {
                    $part = $this->compileExpressionToken($part);
                }
                $result .= ' '.$part;
            }
        }
        $result = '(' . trim($result) . ')';
        return $result;
    }

    private function compileSingleExpression($pregMatchResults) {
        $str = $pregMatchResults[0];
        if (($str[0] == '"') || (substr($str, 0, 2) == '->')) {
            return $str;
        }
        if ($str[0] == '!' || in_array($str, $this->_predefinedMethods)) {
            return $str;
        }
        return '$__lv' . $this->compileVarOrFunction($str);
    }

    private function processModifiers($varToken) {
        $mStart     = '';
        $mEnd       = '';
        $rawEcho    = false;

        /** process modifiers */
        if (strpos($varToken, '|') !== false && strpos($varToken, '||') === false) {
            $modifiers = explode('|', $varToken);
            $varToken  = array_shift($modifiers);
            foreach ($modifiers as $modifier) {
                if ($modifier == 'raw') {
                    $rawEcho = true;
                    continue;
                }

                if ($this->isCallable($modifier)) {
                    $mStart = $modifier . '(' . $mStart;
                    $mEnd  .= ')';
                } else {
                    throw new \Exception('SLOT compiler error: undefined modifier '.$modifier);
                }
            }
        }

        return array(
            $varToken,
            $mStart,
            $mEnd,
            $rawEcho
        );
    }

    /**
     * @param string $varToken
     * @return string
     */
    public function compileVarOrFunction($varToken) {
        if ($varToken[0] == '"') return $varToken;

        if (strpos($varToken, '->') !== false) {
            $objectParts = explode('->', $varToken);
            $res = '[\'' . array_shift($objectParts) . '\']';
            foreach($objectParts as $part) {
                $res .= '->' . $part;
            }
            return $res;
        } elseif (strpos($varToken, '()') !== false) {
            return '->' . $varToken;
        } elseif (strpos($varToken, '[') !== false) {
            $workToken      = $varToken;
            $arrayKeyPreg  = '#\[([\w\d]+)\]#smU';
            $arrayTokens = array();
            $iterations = 0;
            $tempKeys = array();
            preg_match($arrayKeyPreg, $workToken, $tempKeys);
            while (count($tempKeys) > 0) {
                $iterations++;
                $tempKeys = array();
                preg_match($arrayKeyPreg, $workToken, $tempKeys);
                if (!empty($tempKeys[0])) {
                    $replaceKey = '__key__' . count($arrayTokens) . '';
                    $arrayTokens[$replaceKey] = $tempKeys[0];
                    $workToken = preg_replace($arrayKeyPreg, $replaceKey, $workToken);
                }
            }
            $keysIndexes = array_keys($arrayTokens);
            for($i = count($keysIndexes)-1; $i >=0 ; --$i) {
                $index  = $keysIndexes[$i];
                $search = $arrayTokens[$index];
                $replaced = preg_replace_callback($arrayKeyPreg, array($this, 'compileArrayKey'),  $search);
                if ($i == count($keysIndexes) -1) {
                    $workToken = str_replace($index, $replaced, $workToken);
                } else {
                    $workToken = str_replace($index . "']", "']" . $replaced, $workToken);
                }

            }

            $keyOpenIndex = strpos($workToken, '[');
            $arrayVar = $this->compileVarOrFunction(substr($workToken, 0, $keyOpenIndex));
            return $arrayVar . substr($workToken, $keyOpenIndex);
        } elseif (preg_match('#[-+*/]+#', $varToken)) {
            $varParts = explode(' ', $varToken);
            $res = '';
            foreach($varParts as $part) {
                $part = trim($part);
                if (strlen($part)) {
                    if (is_numeric($part)) {
                        $res .= ' '.$part;
                    } elseif (in_array($part, array('+', '-', '*', '/'))) {
                        $res .= ' '.$part;
                    } else {
                        $res .= '[\'' . $part . '\']';
                    }
                }
            }
            return $res;
        } else {
            return '[\'' . $varToken . '\']';
        }
    }

    private function compileArrayKey($key) {
        $res = '[';
        $res .= $this->compileExpressionToken($key[1]);

        return $res . ']';
    }

    private function onVarEchoToken($token) {
        $res = '<?php ';

        $varExpression  = $this->compileExpressionToken($token);
        $varValue       = $varExpression;
        $isFunctional   = preg_match('#[-+*/\(]+#', $varValue);
        /** process simple escaping strategies */
        if ($this->getEscapingStrategy() == 'html' && (strpos($token, 'raw') === false) && !$isFunctional) {
            if (strpos($varExpression, 'htmlentities') === false) {
                $varValue  = 'htmlentities(' . $varValue . ', ENT_COMPAT, "UTF-8")';
            }
        }
        if ($isFunctional) {
            $res .= 'echo '. $varValue;
        } else {
            $res .= 'echo !empty('. $varExpression . ') ? ' . $varValue . ': ""';
        }
        $res .= '; ?>';
        return $res;
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
        $paramsParts = explode(' ', $string);
        $args        = array();
        foreach ($paramsParts as $part) {
            if (!empty($part)) {
                if (is_string($part) && $part[0] == "'" && $part[strlen($part)-1] == "'") {
                    $part = substr($part, 1, -1);
                }
                $args[] = $part;
            }
        }

        return $args;
    }


}