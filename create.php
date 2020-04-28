<?php


class create {
    static function atom($val)    { return new tAtom($val); }
    static function keyword($name) { return chr(0x7f).$name; }
    static function symbol($value) { return new tSymbol($value);}
    static function list(...$args) {
        $v = new tList();
        $v->exchangeArray($args);
        return $v;
    }

    static function vector(...$args) {
        $v = new tVector();
        $v->exchangeArray($args);
        return $v;
    }

    static function env_keys($env){
        $env_keys= create::list();
        $env_keys->exchangeArray(array_map(function($k){return create::symbol($k);}, array_keys($env->data)));
        return $env_keys;
    }
    static function hash_map(...$args) {
        if (count($args) % 2 === 1) {
            throw new Exception("Odd number of hash map arguments");
        }
        $hm = new tHashMap();
        array_unshift($args, $hm);
        return call_user_func_array('_assoc_BANG', $args);
    }
    static function function($func, 
                    $type='platform',
                    $ast=NULL, 
                    $env=NULL, 
                    $params=NULL, 
                    $ismacro=False) : tFunction {
        return new tFunction($func, $type, $ast, $env, $params, $ismacro);
    }
}
