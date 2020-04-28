<?php
class Lisp0{

    static protected $prompt = "\n\e[0;31muser> \e[0m";

    static function readline() {
        stdout::write(self::$prompt);
        $line = stdin::gets();//$this->prompt);
        if(stdin::eof()){
            echo "\n[exit]\n";
            exit;
        }
        return $line;
    }
    function read() {
        return self::readline();
    }

    function eval($ast){
        return $ast;
    }
    function print($line){
        if(stdout::write($line) <1){
            throw new Exception("error");
        }
        return true;
    }
    function read_eval_print_loop(){
        while(  
            $this->print(
                $this->eval(
                    $this->read())));
    }
}