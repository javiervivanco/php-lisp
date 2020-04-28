<?php 
require_once 'eval.php';
class lisp {
    public $repl_env=[];
    static public $prompt = "\n\e[0;31muser> \e[0m";

    static function readline() {
        stdout::write(self::$prompt);
        $line = stdin::gets();//$this->prompt);
        if(stdin::eof()){
            echo "\n[exit]\n";
            exit;
        }
        return $line;
    }
    static public function read() {
        try {
             return read_str(self::readline());
        }
        catch (\Exception $e){
             stderr::write(sprintf("<fail> %s\n%s\n",$e->getMessage(),$e->getTraceAsString()));
             return read_str(sprintf('(error %s)',$e->getCode()));
        }
 
     }
 
 
     static function print($ast){
         if(stdout::write(pr_str($ast)) <1){
             throw new Exception("error");
         }
         return true;
     }
    static function eval( $ast, $env){
        if (!_is_list($ast)) {
            return eval_ast($ast, $env);
        }
        if ($ast->count() === 0) {
            return $ast;
        }
    
        // apply list
        $el = eval_ast($ast, $env);
        $f = $el[0];
        return call_user_func_array($f, array_slice($el->getArrayCopy(), 1));
    } 
    function read_eval_print_loop(){
        while(  
            self::print(
                self::eval(
                    self::read(),
                    $this->repl_env)));
    }
}