<?php

class eval_stage {
    static function ast(){

    }
}

// eval
function eval_ast($ast, $env) {

    if (is::symbol($ast)) {
        $return = $env->get($ast);
        if(Log::$debug){
            $debug = create::list();
            $debug->exchangeArray([create::symbol('get-value-from-env'),$ast]);
            Log::debug_ast($debug,__METHOD__);
        }
        return $return;
    } elseif (is::sequential($ast)) {
        return eval_sequential($ast,$env);
    } elseif (is::hash_map($ast)) {
        return eval_hashmap($ast,$env);
    } else {
        if(Log::$debug){
            $debug = create::list();
            $debug->exchangeArray([$ast]);
            Log::debug_ast($debug,__METHOD__);
        }
        return $ast;
    }
}
function eval_hashmap($ast,$env){
    $new_hm = create::hash_map();
    foreach (array_keys($ast->getArrayCopy()) as $key) {
        $new_hm[$key] = repl::eval($ast[$key], $env);
    }
    return $new_hm;

}
function eval_sequential($ast,$env){
    $el = is::list($ast) ?  create::list() : create::vector();
    foreach ($ast as $a) { 
        $el[] = repl::eval($a, $env); 
    }
    return $el;

}
 