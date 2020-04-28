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
    static public $prompt = "\n\e[0;32muser> \e[0m";
    static public $prompt_ = "\nuser> ";

    function __construct (Env $env = null){
        $this->env =  $env ?? new Env(null); 
        readline_completion_function([&$this,'autocompletion']);
    }

    public function autocompletion($string, $index){
        $rl_info = readline_info();
 
        // Figure out what the entire input is
        $full_input = substr($rl_info['line_buffer'], 0, $rl_info['end']);
//       var_dump($full_input);
        $matches = array();
       
        // Get all matches based on the entire input buffer
        //foreach (self::phrases_that_begin_with($full_input,array_keys($this->env->data)) as $phrase) {
        foreach ($this->env->data as $phrase =>$v) {
            // Only add the end of the input (where this word begins)
          // to the matches array
          $matches[] = substr($phrase, 0);
        }
        $matches[] = SpecialForm::DEFINE;
        $matches[] = SpecialForm::DO;
        $matches[] = SpecialForm::TRY;
        $matches[] = SpecialForm::LET_REC ;
        $matches[] = SpecialForm::LAMBDA;
        $matches[] = SpecialForm::LAMBDA_1;
        $matches[] = SpecialForm::DEFINE_MACRO;
        $matches[] = SpecialForm::MACROEXPAND;
        $matches[] = SpecialForm::QUASIQUOTE;
        $matches[] = SpecialForm::QUOTE;
        $matches[] = SpecialForm::UNQUOTE;
        $matches[] = SpecialForm::SPLICE_UNQUOTE;

        return $matches;
    }
    static function phrases_that_begin_with($string,$options){
        return array_filter($options, function($v) use ($string){
            return stripos($v,$string) === 0;
        });
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

    
    static function quasiquote($ast) {
        if (!is::pair($ast)) {
            Log::debug(__METHOD__.'::not_is_pair');
            $ast= create::list(create::symbol(SpecialForm::QUOTE), $ast);
        } elseif (self::is_symbol_unquote($ast)) {
            Log::debug(__METHOD__.'::is_symbol_unquote');

            $ast=$ast[1];
        } elseif (self::is_pair_symbol_splice_unquote($ast)) {
            Log::debug(__METHOD__.'::is_pair_symbol_splice_unquote(concat)');
            $ast = create::list(create::symbol('concat'), 
                        $ast[0][1],
                        self::quasiquote($ast->slice(1)));
        } else {
            Log::debug(__METHOD__.'::else');
            $ast = create::list(create::symbol('cons'), 
                        self::quasiquote($ast[0]),
                        self::quasiquote($ast->slice(1)));
        }
        Log::debug_ast($ast, __METHOD__);
        return $ast;
    }
    static function is_symbol_unquote($ast){
        return is::symbol($ast[0]) && $ast[0]->value === SpecialForm::UNQUOTE;
    }
    static function is_pair_symbol_splice_unquote($ast){
        return is::pair($ast[0]) 
        && is::symbol($ast[0][0]) 
        && $ast[0][0]->value === SpecialForm::SPLICE_UNQUOTE;
    }
    static function is_macro_call($ast, $env) {
        return is::pair($ast) && // es un par
            is::symbol($ast[0]) && // es un symbol
            $env->find($ast[0]) && // existe en el entorno Env
            $env->get($ast[0])->ismacro; // es una funcion macro
    }

    static function macroexpand($ast, $env) {
        while (self::is_macro_call($ast, $env)) { // extande recursivamente
            $mac = $env->get($ast[0]);
            $args = array_slice($ast->getArrayCopy(),1);
            $ast = $mac->apply($args);
        }
        return $ast;
    }

    static function eval( $ast, $env){
        while(true){
            #echo __METHOD__ .' ' .pr_str($ast) . "\n";
            if (!is::list($ast)) {
                return eval_ast($ast, $env);
            }
            $ast = self::macroexpand($ast, $env);
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
                case  SpecialForm::DEFINE:
                    $res = self::eval($ast[2], $env);   
                    return $env->set($ast[1], $res);
                case SpecialForm::LET_REC:
                    if(count($ast) <> 3) throw new LangError("let* malformed expected 3 parts ".pr_str($ast));
                    $a1 = $ast[1];
                    if(!is_countable($a1)) throw new LangError("let* malformed (let* <env-asign> <tail>) <env-asign> ". pr_str($a1));

                    $let_env = new Env($env);
                    for ($i=0; $i < count($a1); $i+=2) {
                        if(!isset($a1[$i+1])){
                            throw new LangError("let* malformed (let* (<symbol> <sexp> ...) <tail>) <sexp> not found for ". pr_str($a1[$i]));
                        }
                        $let_env->set($a1[$i], self::eval($a1[$i+1], $let_env));
                    }
                    $ast = $ast[2];
                    $env = $let_env;
                    break; // Continue loop (TCO)
                case SpecialForm::QUOTE:
                    return $ast[1];
                case SpecialForm::QUASIQUOTE:
                    $ast= self::quasiquote($ast[1]);
                    break; // Continue loop (TCO)
                case SpecialForm::DEFINE_MACRO:
                    $func = self::eval($ast[2], $env);
                    $func->ismacro = true;
                    return $env->set($ast[1], $func);
                case SpecialForm::MACROEXPAND:
                case SpecialForm::MACROEXPAND_1:

                    return self::macroexpand($ast[1], $env);
                case SpecialForm::BEGIN:
                case SpecialForm::DO:
                    eval_ast($ast->slice(1, -1), $env); // recorta la cola
                    $ast = $ast[count($ast)-1]; // evalua la cola
                    break;
                case SpecialForm::IF:
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
                case SpecialForm::LAMBDA:
                case SpecialForm::LAMBDA_1:
                    return create::function('repl::eval', 'native',
                         $ast[2], $env, $ast[1]);
                case SpecialForm::TRY: 
                    $a1 = $ast[1];
                    $a2 = $ast[2];
                    if ($a2[0]->value === SpecialForm::CATCH) {
                        try {
                            return self::eval($a1, $env);
                        } catch (LangError $e) {
                            $catch_env = new Env($env, [$a2[1]],[$e->obj]);
                            return self::eval($a2[2], $catch_env);
                        } catch (Exception $e) {
                            $catch_env = new Env($env, [$a2[1]],[$e->getMessage()]);
                            return self::eval($a2[2], $catch_env);
                        }
                    } else {
                        return self::eval($a1, $env);
                    }
                default:
                    $el = eval_ast($ast, $env);
                    $f = $el[0];
                    $args = array_slice($el->getArrayCopy(), 1);
                    if(!($f instanceof tFunction)){
                        throw new Exception(sprintf('\'%s\' not is a fn*',pr_str($f)));
                    }
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
                    $line .= self::readline(null). ' ';
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
                continue;
            } catch (\LangError $e){
                echo "Error: " . pr_str($e->obj, True) . "\n";
            } catch (\Exception $e ){
                stderr::write(sprintf("Error: %s\n%s\n",$e->getMessage(),$e->getTraceAsString()));
            } catch (\TypeError $e){
                stderr::write(sprintf("Error: %s\n%s\n",$e->getMessage(),$e->getTraceAsString()));
        }
        } while (true);
    }
}
const DEBUG = 1;
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

$repl->env->set(create::symbol('eval'), 
    create::function(function($ast) use ($repl) {
        return repl::eval($ast, $repl->env);
    }
    )
);
$_argv = create::list();
for ($i=2; $i < count($argv); $i++) {
    $_argv->append($argv[$i]);
}
$repl->env->set(create::symbol('*ARGV*'), $_argv);



foreach ($functions as $symbol => $function) {
    $repl->env->set(create::symbol($symbol), create::function($function));

}
foreach ($core_ns as $symbol => $function) {
    $repl->env->set(create::symbol($symbol), create::function($function));

}
$repl->run('(def! load-file (fn* (f) (eval (read-string (str "(do " (slurp f) "\nnil)")))))'); 

$repl->run(sprintf('(load-file "%s%score.mal")',__DIR__,DIRECTORY_SEPARATOR)); 

if (count($argv) > 1) {
    $repl->run('(load-file "' . $argv[1] . '")');
    exit(0);
}

$repl->read_eval_print_loop();
echo "\n[exit]\n";