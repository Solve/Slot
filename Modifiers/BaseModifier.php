<?php
/*
 * This file is a part of Solve framework.
 *
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 * @copyright 2009-2014, Alexandr Viniychuk
 * created: 21.02.14 22:23
 */

namespace Solve\Slot\Modifiers;
use Solve\Slot\Compiler;


/**
 * Class BaseModifier
 * @package Solve\Slot\Modifiers
 *
 * Class BaseModifier is used to ...
 *
 * @version 1.0
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 */
class BaseModifier {

    protected $_params      = array();

    /**
     * @var Compiler
     */
    protected $_compiler;


    public function __construct($token, $compiler) {
        $this->_token    = $token;
        $this->_compiler = $compiler;
    }


} 