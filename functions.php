<?php


function vd() {
    $arguments = func_get_args();
    if (count($arguments)) {
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            if(!headers_sent()) {
                header('Content-Type: text/html; charset=utf-8');
            }
            echo '<pre>';
        }

        $last = array_pop($arguments);
        foreach($arguments as $item) {
            echoSingleVar($item);
        }

        if ($last !== '!@#') {
            echoSingleVar($last);
            die();
        }
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            echo '</pre>';
        } else echo "\n";
    }
}

function echoSingleVar($var) {
//    if (is_object($var) && is_callable(array($var, 'getArray'))) {
//        $v = $var->getArray();
//        echo Inflector::dumperGet($v) . "\n";
//    } else {
    echo \Solve\Utils\Inflector::dumperGet($var) . "\n";
//    }

}
/**
 * You can pass optional parameter
 * wtf() - print backtrace
 * wtf($somevar == "how this value could be there?") - print backtrace only if expression is true
 */
function wtf() {
    $args = func_get_args();
    if (empty($args) || $args[0]) {
        echo "<pre>";
        debug_print_backtrace();
    }
}