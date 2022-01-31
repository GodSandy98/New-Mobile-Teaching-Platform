<?php
namespace app\test\controller;

class Index
{
    public function index()
    {
        return 'b';
    }
    
    public function test()
    {
        $index = controller('test/test','controller');
        $index::getInstance()->index();
        $index2 = controller('test/test','controller');
        $index2::getInstance()->index();
    }
}
