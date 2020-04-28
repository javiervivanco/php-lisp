<?php 
require_once 'eval_ast.php';
require_once 'env.php';
class lisp {
    /**
     * @var Env
     */
    public $repl_env;   
    static public $prompt = "\n\e[0;31muser> \e[0m";
    function __construct (Env $repl_env = null){
        $this->repl_env =  $repl_env ?? new Env();  
    }

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
             return read_str(self::readline());
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
            // apply list
            $a0 = $ast[0];
            $a0v = _is_symbol($a0) ? $a0->value : $a0;
            switch ($a0v) {
            case "def!":
                $res = self::eval($ast[2], $env);
                return $env->set($ast[1], $res);
            case "let*":
                $a1 = $ast[1];
                $let_env = new Env($env);
                for ($i=0; $i < count($a1); $i+=2) {
                    $let_env->set($a1[$i], self::eval($a1[$i+1], $let_env));
                }
                return self::eval($ast[2], $let_env);
            default:
                $el = eval_ast($ast, $env);
                $f = $el[0];
                if(!is_callable($f)){
                    stderr::write(sprintf("<fail> %s\n\n","{$f[0]->value} not function"));
                    throw new Exception("{$f[0]->value} not function");
                }
                return call_user_func_array($f, array_slice($el->getArrayCopy(), 1));
            }

    } 
    function read_eval_print_loop(){
        // repl loop
    do {
        try {
            $line = self::read();
            if ($line === NULL) { break; }
            if ($line !== "") {
                self::print(self::eval($line,$this->repl_env));
            }
        } catch (BlankException $e) {
            continue;
        } catch (\Exception $e){
            stderr::write(sprintf("<fail> %s\n%s\n",$e->getMessage(),$e->getTraceAsString()));
            #return read_str(sprintf('(error %s)',$e->getCode()));
       }
    } while (true);
    }
}