<?php
require_once 'std_common.php';
require_once 'reader.php';
require_once 'printer.php';

require_once 'lisp2.php';

//php://stdin
$lisp = new lisp();
$lisp->env=[
'+' => function ($a, $b) { return intval($a + $b,10); },
'-' => function ($a, $b) { return intval($a - $b,10); },
'*' => function ($a, $b) { return intval($a * $b,10); },
'/' => function ($a, $b) { return intval($a / $b,10); },
'print' => function ($v) { return $v; },
'error' => function ($code, $msg='') { return ; },
'if' => function ($cond, $eval_true, $eval_false=null) { if($cond){return $eval_true;}else{return $eval_false;}; return; }

];

$lisp->read_eval_print_loop();

