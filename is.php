<?php
class is
{
    static function keyword($obj) : bool
    {
        return is_string($obj) && strpos($obj, chr(0x7f)) === 0;
    }
    static function atom($atm) : bool
    {
        return $atm instanceof tAtom;
    }
    static function symbol($v) : bool
    {
        return $v instanceof tSymbol;
    }
    static function list($v):bool {
        return $v instanceof tList;
    }
    static function hash_map($v) : bool
    {
        return $v instanceof tHashMap;
    }
    static function vector($v) : bool
    {
        return $v instanceof tVector;
    }
    static function unquote($ast)
    {
        return is::symbol($ast[0]) && $ast[0]->value === SpecialForm::UNQUOTE;
    }
    static function splice_unquote($ast){
        return is::pair($ast[0]) 
        && is::symbol($ast[0][0]) 
        && $ast[0][0]->value === SpecialForm::SPLICE_UNQUOTE;
    }
    static function quote($ast){
        return (is::list($ast) && isset($ast[0]) && is::symbol($ast[0]) && $ast[0]->value ==SpecialForm::QUOTE) ; 
    }

    static function macro_call($ast, $env) {
        return is::pair($ast) && // es un par
            is::symbol($ast[0]) && // es un symbol
            $env->find($ast[0]) && // existe en el entorno Env
            $env->get($ast[0])->ismacro; // es una funcion macro
    }

    static function sequential($seq) : bool
    {
        return is::list($seq) or is::vector($seq);
    }
    static function function($obj):bool { return $obj instanceof tFunction; }
                static function fn($obj) : bool
    {
        return $obj instanceof Closure;
    }
    //scalar
    static function true($obj) : bool
    {
        return $obj === true;
    }
    static function false($obj) : bool
    {
        return $obj === false;
    }
    static function number($obj) : bool
    {
        return !is_string($obj) && is_numeric($obj);
    }
    static function empty($seq):bool { return $seq->count() === 0; }
                static function nil($obj) : bool
    {
        return $obj === null;
    }
    static function string($obj) :bool
    {
        return is_string($obj) && strpos($obj, chr(0x7f)) !== 0;
    }
    static function pair($x) : bool
    {
        return is::sequential($x) and count($x) > 0; // un sequencia no vacia
    }

    static function equal($a, $b) : bool
    {
        $ota = gettype($a) === "object" ? get_class($a) : gettype($a);
        $otb = gettype($b) === "object" ? get_class($b) : gettype($b);
        if (!($ota === $otb or (is::sequential($a) and is::sequential($b)))) {
            return false;
        } elseif (is::symbol($a)) {
            return is::equal_symbol($a, $b);
        } elseif (is::list($a) or is::vector($a)) {
            return is::equal_list($a, $b);
        } elseif (is::hash_map($a, $b)) {
            return is::equal_hash_map($a, $b);
        } else {
            return $a === $b;
        }
    }

    static function equal_symbol($a, $b) : bool
    {
        //print "ota: $ota, otb: $otb\n";
        return $a->value === $b->value;
    }
    static function equal_hash_map($a, $b) : bool
    {
        if ($a->count() !== $b->count()) {
            return false;
        }
        $hm1 = $a->getArrayCopy();
        $hm2 = $b->getArrayCopy();
        foreach (array_keys($hm1) as $k) {
            if (!is::equal($hm1[$k], $hm2[$k])) {
                return false;
            }
        }
        return true;
    }

    static function equal_list($a, $b) : bool
    {
        if ($a->count() !== $b->count()) {
            return false;
        }
        for ($i = 0; $i < $a->count(); $i++) {
            if (!is::equal($a[$i], $b[$i])) {
                return false;
            }
        }
        return true;
    }

    static function contains($hm, $k)
    {
        return array_key_exists($k, $hm);
    }
}
