<?php 
//function _throw($obj) { throw new LangError($obj); }
 

// String functions
function print_str(...$args) {
    $ps = array_map(function ($obj) { return pr_str($obj, True); },
                    $args);
    return implode(" ", $ps);
}

function str(...$args) {
    $ps = array_map(function ($obj) { return pr_str($obj, False); },
                    $args);
    return implode("", $ps);
}

function prn(...$args) {
    $ps = array_map(function ($obj) { return pr_str($obj, True); },
                    $args);
    print implode(" ", $ps) . "\n";
    return null;
}

function println(...$args) {
    $ps = array_map(function ($obj) { return pr_str($obj, False); },
                    $args);
    print implode(" ", $ps) . "\n";
    return null;
}


// Number functions
function time_ms() {
    return intval(microtime(1) * 1000);
}


// Hash Map functions
function assoc($src_hm) {
    $args = func_get_args();
    $hm = clone $src_hm;
    $args[0] = $hm;
    return call_user_func_array('_assoc_BANG', $args);
}

function dissoc($src_hm) {
    $args = func_get_args();
    $hm = clone $src_hm;
    $args[0] = $hm;
    return call_user_func_array('_dissoc_BANG', $args);
}

function get($hm, $k) {
    if (($hm instanceof tSeq) && $hm->offsetExists($k)) {
        return $hm[$k];
    } else {
        return NULL;
    }
}


function keys($hm) {
    return call_user_func_array('create::list',
        array_map('strval', array_keys($hm->getArrayCopy())));
}
function vals($hm) {
    return call_user_func_array('create::list', array_values($hm->getArrayCopy()));
}


// Sequence functions
function cons($a, tSeq $b) {
    $tmp = $b->getArrayCopy();
    array_unshift($tmp, $a);
    $l = new tList();
    $l->exchangeArray($tmp);
    return $l;
}

function concat(...$args) {
    $tmp = array();
    foreach ($args as $arg) {
        if($arg instanceof tSeq) {
            $tmp = array_merge($tmp, $arg->getArrayCopy());
        } else {
            throw new LangError(pr_str($arg)." Not sequence");
        }
    }
    $l = new tList();
    $l->exchangeArray($tmp);
    return $l;
}

function nth(tSeq $seq, $idx) {
    Log::debug_ast([__METHOD__, $seq, $idx] ,);

    if ($idx < $seq->count()) {
        return $seq[$idx];
    } else {
        throw new Exception("nth: index out of range");
    }
}

function first(tSeq $seq = null) {
    Log::debug_ast($seq ,__METHOD__);

    if ($seq === NULL || scount($seq) === 0) {
        return NULL;
    } else {
        return $seq[0];
    }
}

function rest(tSeq $seq = null) {
    if ($seq === NULL) {
        return new tList();
    } else {
        $l = new tList();
        $l->exchangeArray(array_slice($seq->getArrayCopy(), 1));
        return $l;
    }
}


function scount(Countable $seq=null) { return ($seq === NULL ? 0 : $seq->count()); }

function apply($f) {
    $args = array_slice(func_get_args(), 1);
    $last_arg = array_pop($args)->getArrayCopy();
    return $f->apply(array_merge($args, $last_arg));
}

function map($f, tSeq $seq) {
    $l = new tList();
    # @ to surpress warning if $f throws an exception
    @$l->exchangeArray(array_map($f, $seq->getArrayCopy()));
    return $l;
}
function filter($f, $seq) {
    $l = new tList();
    # @ to surpress warning if $f throws an exception
    @$l->exchangeArray(array_filter($seq->getArrayCopy(),$f));
    return $l;
}
function conj($src, ...$args) {
    $tmp = $src->getArrayCopy();
    if (is::list($src)) {
        foreach ($args as $arg) { array_unshift($tmp, $arg); }
        $s = new tList();
    } else {
        foreach ($args as $arg) { $tmp[] = $arg; }
        $s = new tVector();
    }
    $s->exchangeArray($tmp);
    return $s;
}

function seq($src) {
    if (is::list($src)) {
        if (count($src) == 0) { return NULL; }
        return $src;
    } elseif (is::vector($src)) {
        if (count($src) == 0) { return NULL; }
        $tmp = $src->getArrayCopy();
        $s = new tList();
        $s->exchangeArray($tmp);
        return $s;
    } elseif (is::string($src)) {
        if (strlen($src) == 0) { return NULL; }
        $tmp = str_split($src);
        $s = new tList();
        $s->exchangeArray($tmp);
        return $s;
    } elseif (is::nil($src)) {
        return NULL;
    } else {
        throw new Exception("seq: called on non-sequence");
    }
    return $s;
}



// Metadata functions
function with_meta($obj, $m) {
    $new_obj = clone $obj;
    $new_obj->meta = $m;
    return $new_obj;
}

function meta($obj) {
    return $obj->meta;
}


// Atom functions
function deref($atm) { return $atm->value; }
function reset_BANG($atm, $val) { return $atm->value = $val; }
function swap_BANG($atm, $f, ...$args) { 
    array_unshift($args, $atm->value);
    $atm->value = call_user_func_array($f, $args);
    return $atm->value;
}


// core_ns is namespace of type functions
$core_ns = array(
    '='=>      function ($a, $b) { return is::equal($a, $b); },
    'pair?'=>  function ($is_pair) { return is::pair($is_pair); },

    'throw'=>  function ($a) { throw new LangError($a); },
    'nil?'=>   function ($a) { return is::nil($a); },
    'true?'=>  function ($a) { return is::true($a); },
    'false?'=> function ($a) { return is::false($a); },
    'number?'=> function ($a) { return is::number($a); },
    'symbol'=> function (...$args) { return call_user_func_array('create::symbol', $args); },
    'symbol?'=> function ($a) { return is::symbol($a); },
    'keyword'=> function (...$args) { return call_user_func_array('create::keyword', $args); },
    'keyword?'=> function ($a) { return is::keyword($a); },

    'string?'=> function ($a) { return is::string($a); },
    'random'=> function ($min,$max) { return rand($min,$max); },
    'fn?'=>    function($a) { return is::fn($a) || (is::function($a) && !$a->ismacro ); },
    'macro?'=> function($a) { return is::function($a) && $a->ismacro; },
    'pr-str'=> function (...$args) { return call_user_func_array('print_str', $args); },
    'str'=>    function (...$args) { return call_user_func_array('str', $args); },
    'prn'=>    function (...$args) { return call_user_func_array('prn', $args); },
    'println'=>function (...$args) { return call_user_func_array('println', $args); },
    'readline'=>function ($a) { return repl::readline($a); },
    'read-string'=>function ($a) { return read_str($a); },
    'slurp'=>  function ($a) { return file_get_contents($a); },
    '<'=>      function ($a, $b) { return $a < $b; },
    '<='=>     function ($a, $b) { return $a <= $b; },
    '>'=>      function ($a, $b) { return $a > $b; },
    '>='=>     function ($a, $b) { return $a >= $b; },
    '+'=>      function ($a, $b) { return $a + $b; },

//    '+'=>      function (...$args) { array_map(function($n){if(!is_numeric($n)) throw new Exception(sprintf('%s is not number',pr_str($n)));},$args);return array_sum($args); },
    '-'=>      function ($a, $b) { return $a - $b; },
    '*'=>      function (...$args) { array_map(function($n){if(!is_numeric($n)) throw new Exception(sprintf('%s is not number',pr_str($n)));},$args);return array_product($args); },
    '/'=>      function ($a, $b) { return $a / $b; },
    'time-ms'=>function () { return time_ms(); },

    'list'=>   function (...$args) { return call_user_func_array('create::list', $args); },
    'list?'=>  function ($a) { return is::list($a); },
    'vector'=> function (...$args) { return call_user_func_array('create::vector', $args); },
    'vector?'=> function ($a) { return is::vector($a); },
    'hash-map' => function (...$args) { return call_user_func_array('create::hash_map', $args); },
    'map?'=>   function ($a) { return is::hash_map($a); },
    'assoc' => function (...$args) { return call_user_func_array('assoc', $args); },
    'dissoc' => function (...$args) { return call_user_func_array('dissoc', $args); },
    'get' =>   function ($hash_map, $key) { return get($hash_map, $key); },
    'contains?' => function ($a, $b) { return is::contains($a, $b); },
    'keys' =>  function ($a) { return keys($a); },
    'vals' =>  function ($a) { return vals($a); },
    'seq?'=> function ($a) { return is::sequential($a); },

    'sequential?'=> function ($a) { return is::sequential($a); },
    'cons'=>   function ($a, $b) { return cons($a, $b); },
    'concat'=> function (...$args) {return call_user_func_array('concat', $args); },
    'nth'=>    function ($seq, $idx=0) { return nth($seq, $idx); },
    'first'=>  function ($a) { return first($a); },
    'rest'=>   function ($a) { return rest($a); },
    'empty?'=> function ($a) { return is::empty($a); },
    'count'=>  function ($a) { return scount($a); },
    'apply'=>  function (...$args) { return call_user_func_array('apply', $args); },
    'map'=>    function ($a, $b) { return map($a, $b); },
    'filter'=>    function ($func, $seq) { return filter($func, $seq); },

    'conj'=>   function (...$args) { return call_user_func_array('conj', $args); },
    'seq'=>    function ($a) { return seq($a); },

    'with-meta'=> function ($symbol, $meta) { return with_meta($symbol, $meta); },
    'meta'=>   function ($a) { return meta($a); },
    'atom'=>   function ($a) { return create::atom($a); },
    'atom?'=>  function ($a) { return is::atom($a); },
    'deref'=>  function ($a) { return deref($a); },
    'reset!'=> function ($a, $b) { return reset_BANG($a, $b); },
    'debug' => function (bool $a) { Log::$debug = $a ;return $a; },
    'swap!'=>  function (...$args) { return call_user_func_array('swap_BANG', $args); },
);
