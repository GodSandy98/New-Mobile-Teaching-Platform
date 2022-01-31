<?php
namespace app\test\controller;

class Test
{
    
    private static $_instance;
    
    public static function getInstance()
    {
        echo 'In'.'<br/>';
        if(!(self::$_instance instanceof self)){
            echo 'true'.'<br/>';
            self::$_instance = new self();
        }
        echo 'out'.'<br/>';
        return self::$_instance;
    }
    
    public function index()
    {
        echo 'test';
    }
}