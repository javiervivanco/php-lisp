<?php
// Errors/Exceptions
require_once 'SpecialForm.php';
require_once 'create.php';
require_once 'is.php';
require_once 'TypeFunction.php';
require_once 'exceptions.php';
require_once 'Interop.php';


class enumList {
    const LIST='create::list';
    const VECTOR='create::vector';

}
// Atoms
class tAtom {
    public $value = NULL;
    public $meta = NULL;
    public function __construct($value) {
        $this->value = $value;
    }
}


class tSymbol{
    const NATIVE = 'native';
    const QUOTE='quote';
    const QUASIQUOTE='quasiquote';

    const NUMBER='number';
    const ATOM  = 'atom';
    const EXPR  = 'expr';  
    public $value = NULL;
    public $meta = NULL;
    public function __construct($value) {
        $this->value = $value;
    }
}


class tSeq extends ArrayObject {
    public function slice($start, $length=NULL) {
        $sc = new $this();
        if ($start >= count($this)) {
            $arr = array();
        } else {
            $arr = array_slice($this->getArrayCopy(), $start, $length);
        }
        $sc->exchangeArray($arr);
        return $sc;
    }
    public function exchangeArray($input){
        //if(!empty($input)) //Log::debug_ast($input,__METHOD__);
        parent::exchangeArray($input);
    }
}
 
// Lists
class tList extends tSeq {
    public $meta = NULL;
}


class tVector extends tSeq {
    public $meta = NULL;
}

class tHashMap extends tSeq {
    public $meta = NULL;
}



function _assoc_BANG(tHashMap $hm) {
    $args = func_get_args();
    if (count($args) % 2 !== 1) {
        throw new Exception("Odd number of assoc arguments");
    }
    for ($i=1; $i<count($args); $i+=2) {
        $ktoken = $args[$i];
        $vtoken = $args[$i+1];
        // TODO: support more than string keys
        if (gettype($ktoken) !== "string") {
            throw new Exception("expected hash-map key string, got: " . gettype($ktoken));
        }
        $hm[$ktoken] = $vtoken;
    }
    return $hm;
}

function _dissoc_BANG(tHashMap $hm) {
    $args = func_get_args();
    for ($i=1; $i<count($args); $i++) {
        $ktoken = $args[$i];
        if ($hm && $hm->offsetExists($ktoken)) {
            unset($hm[$ktoken]);
        }
    }
    return $hm;
}




