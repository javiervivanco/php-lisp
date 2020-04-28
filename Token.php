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
    const UNQUOTE_2 = ',';
    const QUASIQUOTE = '`';
    const NATIVE = 'php\/';
    const DEREF = '@';
    const SPLICE_UNQUOTE = '~@';
    const SPLICE_UNQUOTE_1 = '%@';
    const SPLICE_UNQUOTE_2 = ',@';
    const WITH_META = '^';
    const META = '$';
    const VARIADIC = '&';
    const VARIADIC_1 = '...';
    const VARIADIC_2 = '.';

    const NIL  = 'nil';
    const TRUE = '#T';
    const TRUE_1 = '#t';
    const TRUE_2 = 'true';

    const FALSE = 'false';
    const FALSE_1 = '#f';
    const FALSE_2 = '#F';

    const LR_STRING = '"';

    const KEYWORD = ':';
    const WS = '[\s]*';
    const COMMENT = ';';
    static function tokenize($str)
    {
        $pattern = sprintf('/%s(%s)/'
            ,self::WS
            ,implode('|',[
                self::NATIVE
               ,self::SPLICE_UNQUOTE
               ,self::SPLICE_UNQUOTE_1
               ,self::SPLICE_UNQUOTE_2
                    ,sprintf('[%s]',implode('', [
                    '\\'.self::L_VECTOR
                    ,'\\'.self::R_VECTOR
                    ,self::L_HASHMAP
                    ,self::R_HASHMAP
                    ,self::L_PAREN
                    ,self::R_PAREN
                    ,self::QUOTE
                    ,self::QUASIQUOTE
                    ,self::UNQUOTE
                    ,self::UNQUOTE_1
                    ,self::UNQUOTE_2
                    ,self::WITH_META
                    ,self::DEREF
                    ,self::META]))
            ,self::LR_STRING."(?:\\\\.|[^\\\\\"])".'*'.self::LR_STRING.'?'
            ,self::COMMENT.'.*'
            //,"[^\s\[\]{}('\"`,;)]".'*'
            , sprintf('[%s]%s'
                , implode( '', [
                    self::WITH_META
                    ,'\s'
                    ,'\\'.self::L_VECTOR
                    ,'\\'.self::R_VECTOR
                    ,self::L_HASHMAP
                    ,self::R_HASHMAP
                    ,sprintf('(%s)',implode('',[
                         self::QUOTE
                        ,self::LR_STRING
                        ,self::QUASIQUOTE
                        ,self::UNQUOTE
                        ,self::UNQUOTE_1
                        ,self::UNQUOTE_2 //<<<!!!
                        ,self::COMMENT
                    ]))])
                ,'*')
            ])
            ) ;
       preg_match_all($pattern, $str, $matches);
       return array_values(
            array_filter(
                $matches[1],
                function ($s) {
                    return $s !== '' && $s[0] !== Token::COMMENT;
                }
            )
        );
    }
    

}

