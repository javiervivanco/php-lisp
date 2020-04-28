<?php


class AST{
    public $type;
    public $elements;
    public $values;
    public $source;
    public function head(){}
    public function  tail(){}
    public function  body(){}
    public function  __call($name,$args){
        if(substr($name,0,2)=='is'){
            $func = '';
            return is::$func($this);
        }
    }

}