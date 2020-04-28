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
        $matches[] = SpecialForm::ENV;
        $matches[] = SpecialForm::DO;
        $matches[] = SpecialForm::TRY;
        $matches[] = SpecialForm::LET;
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

    static function unquote($ast) {
        Log::debug_ast($ast, __METHOD__);
        return $ast=$ast[1];
        return ;
    }
    static function splice_unquote($ast){
        $ast = create::list(create::symbol('concat'), 
                    $ast[0][1],
                    self::quasiquote($ast->slice(1)));
        Log::debug_ast($ast,__METHOD__);
        return $ast;
    }
    static function __quasiquote($ast) {
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
    
    static function quasiquote($ast) {
        if (!is::pair($ast)) {
            $ast= create::list(create::symbol(SpecialForm::QUOTE), $ast);
            Log::debug_ast($ast, __METHOD__);
            return $ast;
           //return  $ast= create::list($ast);
        } elseif (is::unquote($ast)) {
            return self::unquote($ast);
        } elseif (is::splice_unquote($ast)) {
            return self::splice_unquote($ast);
        } else {
            //echo 'else';
            if(count($ast->slice(1))>0)
            {
                $ast = create::list(create::symbol('cons'), self::quasiquote($ast[0]), self::quasiquote($ast->slice(1)));
            } else {
                $ast = create::list(create::symbol('list'), 
                self::quasiquote($ast[0]));
            }
        }
        Log::debug_ast($ast, __METHOD__);
        return $ast;
    }

    static function macroexpand($ast, $env) {
        while (is::macro_call($ast, $env)) { // extande recursivamente
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
                case SpecialForm::ENV:
                    return create::env_keys($env);
                case  SpecialForm::DEFINE:
                    $res = self::eval($ast[2], $env);   
                    return $env->set($ast[1], $res);
                case SpecialForm::LET:
                    if(count($ast) <> 3) throw new LangError("let malformed expected 3 parts ".pr_str($ast));
                    $a1 = $ast[1];
                    if(!is_countable($a1)) throw new LangError("let malformed (let* <env-asign> <tail>) <env-asign> ". pr_str($a1));

                    $let_env = new Env($env);
                    $let_assign_env = [];
                    for ($i=0; $i < count($a1); $i++) {
                        $let_assign_env[$i] = new Env($env);

                        if(!is::sequential($a1[$i])){
                            throw new LangError("let malformed (let ( [<symbol> <sexp> ...]) <tail>) <sexp> not found for ". pr_str($a1[$i]));
                        }
                        $let_assign_env[$i]->set($a1[$i][0], self::eval($a1[$i][1], $let_assign_env[$i]));
                    }
                    for ($i=0; $i < count($a1); $i++){
                        $let_env->set($a1[$i][0], $let_assign_env[$i]->get($a1[$i][0]));
                    }
                    $ast = $ast[2];
                    $env = $let_env;
                    break; // Continue loop (TCO)
    
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
                    //return $ast;
                case SpecialForm::QUASIQUOTE:
                    $ast= self::quasiquote($ast[1]);
                    break; // Continue loop (TCO)
                case SpecialForm::DEFINE_MACRO:
                    $f1 = self::eval($ast[2], $env);
                    $func = clone $f1; 
                    $func->ismacro = true;
                    return $env->set($ast[1], $func);
                case SpecialForm::MACROEXPAND:
                case SpecialForm::MACROEXPAND_1:
                    return self::macroexpand($ast[1], $env);
                case SpecialForm::PHP:
                    $res = eval($ast[1]);
                    return Interop::to_language($res);

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
                    return create::function('repl::eval', tFunction::NATIVE,
                         $ast[2], $env, $ast[1]);

                case SpecialForm::NATIVE:
                    return Interop::to_native($ast[1]->value, $env);
                default:
                    $el = eval_ast($ast, $env);
                    $f = $el[0];
                    if(!($f instanceof tFunction)){
                        throw new LangError(sprintf('\'%s\' not is a fn*',pr_str($f)));
                    }
                    $args = array_slice($el->getArrayCopy(), 1);

                    if ($f->type === tFunction::NATIVE) {
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
        $read = read_str($line);
        Log::debug_ast($read,__METHOD__);
        return self::eval($read,$this->env);
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
$repl->run((sprintf('(def! *DIR* "%s")', __DIR__))); 
foreach (getenv() as $key => $value){
    $repl->run((sprintf('(def! *%s* "%s")', $key, $value)));

}

$repl->run((sprintf('(def! *OS_DS* "%s")', DIRECTORY_SEPARATOR))); 
$repl->run('(def! *FILE_CORE* (str  *DIR* *OS_DS* "core.mal"))'); 
$repl->run('(def! *host-language* "php")');
$repl->run('(println (str "Mal [" *host-language* "]"))');

$repl->run('(try* (load-file *FILE_CORE*) (catch* exc (prn (str "[ERROR] " *FILE_CORE* ) "exc is:" exc)) )'); 

if (count($argv) > 1) {
    $repl->run('(load-file "' . $argv[1] . '")');
    //exit(0);
}

$repl->read_eval_print_loop();
echo "\n[exit]\n";