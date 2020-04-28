<?php
require_once 'std_common.php';
require_once 'reader.php';
require_once 'printer.php';

require_once 'lisp3.php';

//php://stdin
$lisp = new lisp();
$functions = [
'+' => function (...$elements_of_sum) { return array_sum(array_map('intval',$elements_of_sum)); },
'-' => function ($a, $b) { return intval($a - $b,10); },
'*' => function ($a, $b) { return intval($a * $b,10); },
'/' => function ($a, $b) { return intval($a / $b,10); },
'print' => function ($str) { return $str; },
'list' => function (...$elements_of_list) { return call_user_func_array('_list', $elements_of_list); },

'error' => function ($code, $msg='') { return ; },
'if' => function ($cond, $eval_true, $eval_false=null) { if($cond){return $eval_true;}else{return $eval_false;}; return; }

];
foreach ($functions as $symbol => $function) {
    $lisp->repl_env->set(_symbol($symbol), $function);

}


$lisp->read_eval_print_loop();

