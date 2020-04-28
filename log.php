<?php

class Log {
    static public $debug=false;
    static function debug_ast($ast, $from=null){
        if(self::$debug):
            echo "\e[0;31;42m{$from}\e[0m \e[0;31;47m". pr_str($ast). "\e[0m\n";
        endif;
    }
    static function debug_env(Env $env, $from){
        if(self::$debug):
            self::debug($from);
            echo '{';
            foreach ($env->data as $k => $v){
                echo "$k}{";
            }
            echo "}\n";
        endif;
    }
    static function debug($message){
        if(self::$debug):
            echo "\e[0;31;42m{$message}\e[0m\n";
        endif;
    }

}