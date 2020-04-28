<?php

class Token
{
    const REG_INT = '/^-?[0-9]+$/';
    const L_PAREN  = '(';
    const R_PAREN  = ')';

    const L_VECTOR = '[';
    const R_VECTOR = ']';

    const L_HASHMAP = '{';
    const R_HASHMAP = '}';

    const QUOTE = "'";
    const UNQUOTE = '~';
    const UNQUOTE_1 = '%';
    const QUASIQUOTE = '`';
    const NATIVE = 'php/';
    const DEREF = '@';
    const SPLICE_UNQUOTE = '~@';
    const SPLICE_UNQUOTE_1 = '%@';
    const WITH_META = '^';
    const META = '$';
    const VARIADIC = '&';
    const VARIADIC_1 = '...';
    const NIL  = 'nil';
    const TRUE = '#T';
    const TRUE_1 = '#t';
    const TRUE_2 = 'true';

    const FALSE = 'false';
    const FALSE_1 = '#f';
    const FALSE_2 = '#F';

    const LR_STRING = '"';

    const KEYWORD = ':';
    static function tokenize($str)
    {
        $preg['whitespace_number_or_commas'] = '[\s,]*';
        $preg['macro']                       = '~@';
        $preg['php_internal']                = 'php\/';
        $preg['special_char']                = '[\[\]{}()\'`~^@\$%]';
        $pat = "/[\s,]*(php\/|~@|%@|[\[\]{}()'`~^@\$%]|\"(?:\\\\.|[^\\\\\"])*\"?|;.*|[^\s\[\]{}('\"`,;)]*)/";
        preg_match_all($pat, $str, $matches);
        return array_values(
            array_filter(
                $matches[1],
                function ($s) {
                    return $s !== '' && $s[0] !== ';';
                }
            )
        );
    }
    

}

