<?php 
require_once 'lisp0.php';
class lisp1 extends lisp0{
 
    public function read() {
       try {
            return read_str(self::readline());
       }
       catch (\Exception $e){
            stderr::write(sprintf("<fail> %s\n%s\n",$e->getMessage(),$e->getTraceAsString()));
            return read_str(sprintf('(error %s)',$e->getCode()));
       }

    }

    function eval( $ast){
        return $ast;
    } 
    function print($ast){
        if(stdout::write(pr_str($ast)) <1){
            throw new Exception("error");
        }
        return true;
    }
}