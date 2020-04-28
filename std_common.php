<?php
require 'log.php';

class stdin{
    static function gets() : string{
        return fgets(STDIN);
    }
    static function eof() : bool{
        return feof(STDIN);
    }
    static function write ( $str) : int{
        return fwrite(STDIN, $str);
    }
}

class stdout{
    static function write (string $str) : int{
        return fwrite(STDOUT, $str);
    }
}

class stderr{
    static function write (string $str) : int{
        return fwrite(STDERR, $str);
    }
}