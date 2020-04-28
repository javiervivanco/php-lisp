<?php

require_once 'types.php';
require_once 'Token.php';
require_once 'AST.php';


class Reader
{
    protected $position = 0;
    protected $tokens;
    function __construct($tokens)
    {
        $this->tokens = $tokens;
    }
    public function next()
    {
        $v = $this->peek();
        $this->position++;
        return $v;
    }
    public function peek()
    {
        return $this->position >= count($this->tokens) ? null : $this->tokens[$this->position];
    }
    static function str($str) : AST {

    }
}



function read_atom($reader)
{
    $token = $reader->next();
    $ref_return = new stdClass();
    return  read_numeric($token, $ref_return) 
        || read_string($token, $ref_return)
        || check_lr_string($token[0])
        || read_keyword($token, $ref_return)
        || read_nil($token, $ref_return)
        || read_true($token, $ref_return)
        || read_false($token, $ref_return)
        ? $ref_return->value  // devuelvo read_*
        : create::symbol($token); // o crea symbol
}

function read_keyword($token, stdClass $ref_return){
    if ($token[0] === Token::KEYWORD) {
        $ref_return->value = create::keyword(substr($token, 1));
        return true;
    }
}
function read_true($token, stdClass $ref_return){
    if (in_array($token,[Token::TRUE,Token::TRUE_1,Token::TRUE_2])) {
        $ref_return->value=true;
        return true;
    }
}
function read_false($token, stdClass $ref_return){
    if (in_array($token,[Token::FALSE,Token::FALSE_1,Token::FALSE_2])) {
        $ref_return->value=false;
        return true;
    }
}

function read_nil($token, stdClass $ref_return){
    if ($token === Token::NIL) {
        $ref_return->value=NULL;
        return true;
    }
}
function check_lr_string($token){
    if ($token === Token::LR_STRING) {
        throw new WaitingEnding(sprintf('expected %s, got EOF',Token::LR_STRING));
    }
}
function read_string($token, stdClass $ref_return){
    if (preg_match("/^\"(?:\\\\.|[^\\\\\"])*\"$/", $token)) {
        $str = substr($token, 1, -1);
        $str = str_replace('\\\\', chr(0x7f), $str);
        $str = str_replace('\\"', '"', $str);
        $str = str_replace('\\n', "\n", $str);
        $str = str_replace(chr(0x7f), "\\", $str);
        $ref_return->value= $str;
        return true;
    }
}
function read_numeric($token, stdClass $ref_return){
    if (preg_match(Token::REG_INT, $token)) {
        $ref_return->value= intval($token, 10);
        return true;
    }
    if(is_numeric($token)){
        $ref_return->value= $token + 0;
        return true;
    }
}
function read_hash_map($reader)
{
    $lst = read_list_common($reader, enumList::LIST, Token::L_HASHMAP, Token::R_HASHMAP);
    return call_user_func_array('create::hash_map', $lst->getArrayCopy());
}
function read_list_common(Reader $reader, $constr , $start, $end ){
    $ast = $constr();
    $token = $reader->next();
    if ($token !== $start) {
        throw new Exception("expected '" . $start . "'");
    }
    while (($token = $reader->peek()) !== $end) {
        if ($token === "" || $token === null) {
            throw new WaitingEnding("expected '" . $end . "', got EOF");
        }
        $ast[] = read_form($reader);
    }
    $reader->next();
    return $ast;
}

function read_list(Reader $reader)
{
   return read_list_common($reader, enumList::LIST, Token::L_PAREN, Token::R_PAREN);
}

function read_vector(Reader $reader){
    return read_list_common($reader, enumList::VECTOR, Token::L_VECTOR, Token::R_VECTOR);
}
/** Leo forma */
function read_form(Reader $reader)
{
    $token = $reader->peek();
    switch ($token) {

        case Token::QUOTE:
            $reader->next();
            return create::list(
                create::symbol(SpecialForm::QUOTE),
                read_form($reader)
            );
        case Token::QUASIQUOTE:
            $reader->next();
            return create::list(
                create::symbol(SpecialForm::QUASIQUOTE),
                read_form($reader)
            );
        case Token::UNQUOTE:
        case Token::UNQUOTE_1:
            $reader->next();
            return create::list(
                create::symbol(SpecialForm::UNQUOTE),
                read_form($reader)
            );
        case Token::SPLICE_UNQUOTE:
        case Token::SPLICE_UNQUOTE_1:

            $reader->next();
            return create::list(
                create::symbol(SpecialForm::SPLICE_UNQUOTE),
                read_form($reader)
            );
        case Token::META:
                $reader->next();
                return create::list(
                    create::symbol('meta'),
                    read_form($reader)
                );
    
        case Token::WITH_META:
            $reader->next();
            $meta = read_form($reader);
            return create::list(
                create::symbol('with-meta'),
                read_form($reader),
                $meta
            );

        case Token::DEREF:
            $reader->next();
            return create::list(
                create::symbol('deref'),
                read_form($reader)
            );

        case Token::NATIVE:
            $reader->next();
            return create::list(
                create::symbol('to-native'),
                read_form($reader)
            );

        case Token::R_PAREN:
            throw new Exception("unexpected " . Token::R_PAREN);
        case Token::L_PAREN:
            return read_list($reader);
        case Token::R_VECTOR:
            throw new Exception("unexpected " . Token::R_VECTOR);
        case Token::L_VECTOR:
            return read_vector($reader);
        case Token::R_HASHMAP:
            throw new Exception("unexpected " . Token::R_HASHMAP);
        case Token::L_HASHMAP:
            return read_hash_map($reader);

        default:
            return read_atom($reader);
    }
}


function read_str($str)
{
    $tokens = Token::tokenize($str);
    if (count($tokens) === 0) {
        throw  new BlankException();
    }
    $ast = read_form(new Reader($tokens));
    return $ast;
}

