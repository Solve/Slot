<?php
/*
 * This file is a part of Solve framework.
 *
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 * @copyright 2009-2014, Alexandr Viniychuk
 * created: 05.01.14 01:34
 */

namespace Solve\Slot;
use Solve\Storage\ArrayStorage;
use Solve\Utils\FSService;
use Solve\Utils\Inflector;


/**
 * Class Slot
 * @package Solve\Slot
 *
 * Class Slot is used to ...
 *
 * @version 1.0
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 */
class Slot {

    /**
     * @var array config
     */
    private $_config = array(
        'forceCompile'  => true,
    );

    /**
     * @var Compiler
     */
    private $_compiler;

    /**
     * @var string path to compiled templates
     */
    private $_compileDir;

    /**
     * @var string
     */
    private $_templateDir;

    private $_tplVars;

    public function __construct() {
        $this->_config      = new ArrayStorage($this->_config);
        $this->_compiler    = new Compiler($this);

        $this->registerInternalBlocks();
    }

    /**
     * @return Compiler
     */
    public function getCompiler() {
        return $this->_compiler;
    }

    /**
     * @param string $compileDir
     */
    public function setCompileDir($compileDir) {
        $this->_compileDir  = $compileDir;
        $this->_tplVars    = array();

        FSService::makeWritable($this->_compileDir);
    }

    /**
     * @return string
     */
    public function getCompileDir() {
        return $this->_compileDir;
    }

    /**
     * @param string $templateDir
     */
    public function setTemplateDir($templateDir) {
        $this->_templateDir = $templateDir;
    }

    /**
     * @return string
     */
    public function getTemplateDir() {
        return $this->_templateDir;
    }

    protected function registerInternalBlocks() {
        $blocks = GLOB(__DIR__ . '/Blocks/*.php');
        foreach($blocks as $block) {
            $blockName = lcfirst(substr($block, strrpos($block, '/')+1, -9));
            if ($blockName !== 'base') {
                $this->registerBlock($blockName);
            }
        }
    }

    public function registerBlock($block, $namespace = '\Solve\Slot\Blocks\\') {
        $className = Inflector::camelize($block . 'Block');
        $r = new \ReflectionClass($namespace . $className);
        $doc = $r->getDocComment();

        $config = array(
            'paired'    => strpos($doc, '@paired') !== false,
            'runtime'   => strpos($doc, '@runtime') !== false,
            'namespace' => $namespace
        );
        $this->_compiler->registerBlock($block, $config);
    }

    public function processTemplate($templatePath) {
        $source = file_get_contents($templatePath);
        return $this->_compiler->compileSource($source);
    }

    /**
     * @param $templatePath
     * @param array $variables
     * @param array|ArrayStorage $params
     * @return string
     * @throws \Exception
     */
    public function fetchTemplate($templatePath, $variables = array(), $params = array()) {
        if (!empty($variables)) {
            $this->_tplVars = $variables;
        }
        if (empty($params['absolute'])) {
            $templateFile = $templatePath;
            $templatePath = $this->getTemplateDir() . $templatePath;
        } else {
            $templateFile = substr($templatePath, strrpos($templatePath, '/')+1);
        }

        if (is_file($templatePath)) {
            $compilePath = $this->getCompiledPath($templateFile);
            if ($this->_config['forceCompile'] || !is_file($compilePath)) {
                $old_files = GLOB($this->getCompileDir() . str_replace('/', '_', $templateFile) . '.*');
                foreach($old_files as $file) if (is_file($file)) @unlink($file);

                $content = '<?php $__lv = &$this->_tplVars; ?>';
                $content .= $this->processTemplate($templatePath);

                file_put_contents($compilePath, $content);
            }
        } else {
            throw new \Exception('File not found:' . $templatePath);
        }
        ob_start();
        if (is_file($compilePath)) {
            include $compilePath;
        }
        $output = ob_get_clean();



        return $output;
    }

    public function getCompiledPath($templateFile) {
        $templatePath = $this->_templateDir . $templateFile;
        return $this->getCompileDir() . str_replace('/', '_', $templateFile) . '.' . filectime($templatePath) . '.php';
    }
} 