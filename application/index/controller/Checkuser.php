<?php
namespace app\index\controller;

use think\Session;

class Checkuser extends EmptyClass
{
    public function _initialize(){   //构造方法里面检测Session
        parent::_initialize();    //调用父类的构造函数
        if(!Session::has('username') || !Session::has('username')){
            $this->redirect('index/Login/index');      //重定向到登录界面    “模块/控制器中的.php文件/方法”
        }
    }
}
