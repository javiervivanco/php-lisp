<?php


function pr_str($obj, $print_readably=True) : string{

    if (false && is::quote($obj)) {
        return pr_quote($obj, $print_readably);;
    } 
    if (is::list($obj)) {
       return pr_list($obj, $print_readably);;
    } elseif (is::vector($obj)) {
       return pr_vector($obj,$print_readably);
    } elseif (is::hash_map($obj)) {
        return pr_hash_map($obj,$print_readably);
    } elseif (is_string($obj)) {
        return pr_string($obj,$print_readably);
    } elseif (is_double($obj)) {
        return $obj;
    } elseif (is_integer($obj)) {
        return $obj;
    } elseif ($obj === NULL) {
        return "nil";
    } elseif ($obj === true) {
        return "true";
    } elseif ($obj === false) {
        return "false";
    } elseif (is::symbol($obj)) {
        return $obj->value;
    } elseif (is::atom($obj)) {
        return pr_atom($obj,$print_readably);
    } elseif (is::function($obj)) {
        return pr_function($obj);
    } elseif (is_callable($obj)) {  // only step4 and below
        return pr_callable($obj,$print_readably);
    } elseif (is_object($obj)) {
        return "#<object ...>";
    } elseif (is_array($obj)) {
        return pr_array($obj);
    } else {
        throw new Exception("pr_str unknown type: " . gettype($obj));
    }
}
function pr_string($obj,$print_readably){
    if (strpos($obj, chr(0x7f)) === 0) {
        return ":".substr($obj,1);
    } elseif ($print_readably) {
        $obj = preg_replace('/\n/', '\\n', preg_replace('/"/', '\\"', preg_replace('/\\\\/', '\\\\\\\\', $obj)));
        return '"' . $obj . '"';
    } else {
        return $obj;
    }

}
function pr_array($obj,$print_readably=null){
    $ret = [];
    foreach (array_keys($obj) as $k) {
    $ret[] = pr_str(is_numeric($k) ?"\"#$k\"" : "$k", $print_readably);
    $ret[] = pr_str($obj[$k], $print_readably);
    if($print_readably){
        $ret[] = "\n";
    }
}
    return "{" . implode(" ", $ret) . "}";
}
function pr_hash_map($obj,$print_readably=null){
    $ret = array();
    foreach (array_keys($obj->getArrayCopy()) as $k) {
        $ret[] = pr_str("$k", $print_readably);
        $ret[] = pr_str($obj[$k], $print_readably);
        if($print_readably){
            $ret[] = "\n";
        }
    }
    return "{\n " . implode(" ", $ret) . "}";
}

function pr_atom($obj,$print_readably){
    return "(atom " . pr_str($obj->value, $print_readably) . ")";
}

function pr_vector($obj,$print_readably){
    $ret = array();
    foreach ($obj as $e) {
        array_push($ret, pr_str($e, $print_readably));
    }
    return "[" . implode(" ", $ret) . "]";
}
function pr_quote($obj, $print_readably=true){
    return "'".pr_str($obj[1],$print_readably);
}
function pr_list($obj, $print_readably=true){
    $ret = array();

    foreach ($obj as $e) {
 
        array_push($ret, pr_str($e, $print_readably));
    }
    return "(" . implode(" ", $ret) . ")";
}

function pr_function(tFunction $f) : string{
    if($f->type == tFunction::NATIVE){
        if($f->ismacro){
            return pr_str( create::list(create::symbol('lambda'),create::symbol(':macro'), $f->params, $f->ast ));
        } else {
            return pr_str( create::list(create::symbol('lambda'), $f->params, $f->ast ));
        }
    }else{
        return pr_function_plataform($f);
    }

}

function pr_function_plataform(tFunction $f){
    $refFunc = new ReflectionFunction($f->func);
    $r[]='#(λ ';
    $r[]='[';
    $params = [];
    foreach( $refFunc->getParameters() as $param ){
        $params[]= sprintf('<#%s <%s>%s%s>', 
            $param->getPosition(), 
            $param->getName(), 
            $param->isDefaultValueAvailable() ?
            sprintf(' default:%s', $param->getDefaultValue()) : '',
            !$param->isOptional() ? ' required' : ''
        );
    }
    $r[]= implode(' ', $params);
    $r[]=']';
    $r[]='...)';
    return implode(' ',$r);
    ;
}

function pr_callable($obj,  $print_readably){
    #if(is_)
    #$refFunc = new ReflectionFunction($obj);
    $r[]='#<function ...>';
    #foreach( $refFunc->getParameters() as $param ){
    #    $r[]= sprintf("#  %s",$param->__toString());
    #}
    return implode("\n",$r);
}