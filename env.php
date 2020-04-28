<?php

require_once 'types.php';


class Env {
    public $data=[];
    /**
     * @var Env
     */
    public $outer = null;

    public function __construct($outer, $binds=NULL, $exprs=NULL) {
        $this->outer = $outer;
        if ($binds) {
            $this->bind($binds,$exprs);
        }
        Log::debug_ast([$binds,$exprs],__METHOD__);
    }
    public function bind($binds,$exprs){
        if (is::sequential($exprs)) {
            $exprs = $exprs->getArrayCopy();
        }

        for ($i=0; $i<count($binds); $i++) {
            if ($binds[$i]->value === Token::VARIADIC or $binds[$i]->value ===Token::VARIADIC_1) {
                $this->bind_exprs($binds[$i+1]->value,$exprs,$i);
                break;
            } else {
                if ($exprs !== NULL && $i < count($exprs)) {
                    $this->data[$binds[$i]->value] = $exprs[$i];
                } else {
                    $this->data[$binds[$i]->value] = NULL;
                }
            }
        }
    }
    protected function bind_exprs($value,$exprs,$i){
        if ($exprs !== NULL && $i < count($exprs)) {
            $lst = call_user_func_array('create::list', array_slice($exprs, $i));
        } else {
            $lst = create::list();
        }
        $this->data[$value] = $lst;
    }
    public function set(tSymbol $key,$value){
        //echo "[env] {$key->value}\n";nnnn 
        //debug_print_backtrace();
        $this->data[$key->value]=$value;
        Log::debug_ast(['#ENV-'.spl_object_id($this),$key, $value],__METHOD__);

        return $value;
    }

    public function find(tSymbol $k): ?Env{
        if(!is_scalar($k->value)){
            throw new LangError(pr_str($k->value). ' can not find');
        }
        if(array_key_exists($k->value, $this->data)){
            return $this;
        } elseif($this->outer instanceof Env) {
            return $this->outer->find($k);
        }
        return null; 
    }
    public function get(tSymbol $k){
        if(($env = $this->find($k)) == null){
            throw new Exception("'{$k->value}' not found");
        }
        return $env->data[$k->value];
    }

    public function __toString(){
        $pr=[];
        foreach($this->data as $k => $data){
           $pr[]= sprintf("\t%-20s%s",$k, pr_str($data));
        }
        return implode("\n",$pr)."\n";
    }
}