<?php
require_once 'std_common.php';
require_once 'reader.php';
require_once 'printer.php';

require_once 'lisp1.php';

//php://stdin
$lisp = new lisp1();
$lisp->read_eval_print_loop();

