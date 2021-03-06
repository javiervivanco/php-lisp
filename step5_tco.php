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

    static function readline($prompt=null) {
      $line = readline($prompt);
      if ($line === false) { return NULL; }
      //stdout::write($line);
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
        while(true){
            #echo __METHOD__ .' ' .pr_str($ast) . "\n";
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
                    $ast = $ast[2];
                    $env = $let_env;
                    break; // Continue loop (TCO)
                case "do":
                    eval_ast($ast->slice(1, -1), $env); // recorta la cola
                    $ast = $ast[count($ast)-1]; // evalua la cola
                    break;
                case "if":
                    $cond = self::eval($ast[1], $env);
                    if ($cond === NULL || $cond === false) {
                        if (count($ast) === 4) { 
                            $ast = $ast[3]; 
                        }else{ 
                            $ast=NULL; 
                        }
                    } else {
                        $ast=$ast[2];
                    }
                  break; // Continue loop (TCO)
                case "fn*":
                case "lambda":
                case "λ":
                    return create::function('repl::eval', 'native',
                         $ast[2], $env, $ast[1]);

                default:
                    $el = eval_ast($ast, $env);
                    $f = $el[0];
                    $args = array_slice($el->getArrayCopy(), 1);

                    if ($f->type === 'native') {
                        $ast = $f->ast;
                        $env = $f->gen_env($args);
                        // Continue loop (TCO)
                    } else {
                        return $f->apply($args);
                    }
            }
        }
    } 
    function run($line){
        return self::eval(read_str($line),$this->env);
    }
    function read_eval_print_loop(){
        // repl loop
        $prev_line=null;
        do {
            try {
                if($prev_line){
                    $line .= ' '.self::readline("\t");
                    $prev_line=null;
                }else{
                    $line = self::readline(self::$prompt);
                }
                
                if ($line === NULL) { break; }
                if ($line !== "") {
                    $ast=self::read($line);

                    readline_add_history($line);
                    self::print(
                        self::eval(
                            $ast,
                            $this->env));
                }
            } catch (BlankException $e) {
                continue;
            }catch (WaitingEnding $e){
                $prev_line=true;
                //readline_on_new_line();
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
'='=>      function ($a, $b) { return is::equal($a, $b); },

//'*' => function ($a, $b) { return intval($a * $b,10); },
//'/' => function ($a, $b) { return intval($a / $b,10); },
//'print' => function ($str) { return $str; },
 
'error' => function ($code, $msg='') { return ; },
//'if' => function ($cond, $eval_true, $eval_false=null) { if($cond){return $eval_true;}else{return $eval_false;}; return; }

];
$repl->run("(def! not (fn* (a) (if a false true)))");

foreach ($functions as $symbol => $function) {
    $repl->env->set(create::symbol($symbol), create::function($function));

}
foreach ($core_ns as $symbol => $function) {
    $repl->env->set(create::symbol($symbol), create::function($function));

}

$repl->read_eval_print_loop();
echo "\n[exit]\n";