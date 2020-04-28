<?php
require_once 'std_common.php';
require_once 'reader.php';
require_once 'printer.php';

require_once 'core.php';


require_once 'eval_ast.php';
require_once 'env.php';
class repl {
    /**
     * @var Env
     */
    public $env;   
    static public $prompt = "\n\e[0;31muser> \e[0m";
    function __construct (Env $env = null){
        $this->env =  $env ?? new Env(null);  
    }

    static function readline() {
      $line = readline(self::$prompt);
      if ($line === false) { return NULL; }
      //stdout::write($line);
      readline_add_history($line);
        if(stdin::eof() ){
            echo "\n[exit]\n";
            exit;
        }
        return $line;
    }

    static public function read($line) {
             return read_str($line);
     }

    static function print($ast){
        echo pr_str($ast);return true;
         if(stdout::write(pr_str($ast)) <1){
             throw new Exception("error");
         }
         return true;
     }

    static function eval( $ast, $env){

        if (!is::list($ast)) {
            return eval_ast($ast, $env);
        }
        if ($ast->count() === 0) {
            return $ast;
        }

        // apply list
        // apply list
        $a0 = $ast[0];
        $a0v = is::symbol($a0) ? $a0->value : $a0;
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
            case "do":
                #$el = eval_ast(array_slice($ast->getArrayCopy(), 1), $env);
                $el = eval_ast($ast->slice(1), $env);
                return $el[count($el)-1];
            case "if":
                $cond = self::eval($ast[1], $env);
                if ($cond === NULL || $cond === false) {
                    if (count($ast) === 4) { return self::eval($ast[3], $env); }
                    else                   { return NULL; }
                } else {
                    return self::eval($ast[2], $env);
                }
            case "fn*":
            case "lambda":
                return function(...$args) use ($env, $ast ) {
                    $fn_env = new Env($env, $ast[1], $args);

                    return self::eval($ast[2], $fn_env);
                };
            default:
                $el = eval_ast($ast, $env);
                $f = $el[0];
                if(!is_callable($f)){
                    $f_fail=var_export($f,true);
                    #stderr::write(sprintf("<fail> %s not function\n\n",$f_fail));
                    throw new Exception("{$f_fail} not function");
                }
                return call_user_func_array($f, array_slice($el->getArrayCopy(), 1));
        }

    } 
    function run($line){
        return self::eval(read_str($line),$this->env);
    }
    function read_eval_print_loop(){
        // repl loop
    do {
        try {
            $line = self::readline();
            if ($line === NULL) { break; }
            if ($line !== "") {
                self::print(
                    self::eval(
                        self::read($line),
                        $this->env));
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
//php://stdin
$repl = new repl();
$functions = [
'+' => function (...$elements_of_sum) { return array_sum(array_map('intval',$elements_of_sum)); },
'-' => function ($a, $b) { return intval($a - $b,10); },
'*' => function ($a, $b) { return intval($a * $b,10); },
'/' => function ($a, $b) { return intval($a / $b,10); },
'print' => function ($str) { return $str; },
 
'error' => function ($code, $msg='') { return ; },
//'if' => function ($cond, $eval_true, $eval_false=null) { if($cond){return $eval_true;}else{return $eval_false;}; return; }

];
$repl->run("(def! not (fn* (a) (if a false true)))");

foreach ($functions as $symbol => $function) {
    $repl->env->set(create::symbol($symbol), $function);

}
foreach ($core_ns as $symbol => $function) {
    $repl->env->set(create::symbol($symbol), $function);

}

$repl->read_eval_print_loop();
echo "\n[exit]\n";