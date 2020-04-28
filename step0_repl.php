<?php

require_once 'std_common.php';
require_once 'lisp0';
//php://stdin
$lisp = new lisp0();
$lisp->read_eval_print_loop();

