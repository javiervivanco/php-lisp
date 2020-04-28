<?php

class LangError extends Exception {
    public $obj = null;
    public function __construct($obj) {
        parent::__construct("Error", 0, null);
        $this->obj = $obj;
    }
}

class BlankException extends Exception
{
}

class WaitingEnding extends Exception
{
}