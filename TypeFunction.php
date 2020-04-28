<?php 
class tFunction {
    public $func = NULL;
    public $type = self::NATIVE;   // 'native' or 'platform'
    const NATIVE = 'native';
    public $meta = NULL;
    public $ast = NULL;
    public $env = NULL;
    public $params = NULL;
    public $ismacro = False;
    public function __construct($func, 
                                $type,
                                $ast, 
                                $env, 
                                $params, 
                                $ismacro=False) {
        $this->func = $func;
        $this->type = $type;
        $this->ast = $ast;
        $this->env = $env;
        $this->params = $params;
        $this->ismacro = $ismacro;
        //Log::debug_ast([$this],'new-lambda');

        //Log::debug_ast(['macro?'=>$ismacro,'ast'=>pr_str($ast),'params'=>$params,'env'=>$env]);

        Log::debug_ast(create::list(create::symbol('new-lambda'), create::symbol('#:macro?'),$ismacro,['params'=>$params],['ast'=>$ast],['env'=> empty($env)? null: create::env_keys($env)]));

    }
    public function __invoke(...$args) {
        if ($this->type === self::NATIVE) {
            $fn_env = new Env($this->env,
                              $this->params, 
                              $args);
            $evalf = $this->func;
            return $evalf($this->ast, $fn_env);
        } else {
            return call_user_func_array($this->func, $args);
        }
    }
    public function gen_env($args) {
        return new Env($this->env, $this->params, $args);
    }
    public function apply($args) {
        return call_user_func_array([&$this, '__invoke'],$args);
    }
}
