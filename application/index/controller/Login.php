<?php
namespace app\index\controller;

use app\index\model\UserModel;
use think\Controller;
use think\Session;

class Login extends Controller
{
    public function index()
    {
        Session::clear();
        return $this->fetch('Login');
    }
    
    public function ajax_checkLogin(){
        $uname = input('post.username');
        $upwd = input('post.password');
        $b=UserModel::getInstance()->checkUsernameAndPassword($uname,$upwd);
        $data = array();
        if($b!=null){
            Session::set('username',$b['username']);     //不能用$b->username,必须用$b['username']
            Session::set('userID',$b['ID']);
            Session::set('usertype',$b['type']);
            Session::set('truename',$b['name']);
            $data['code'] = 0;
            $data['data'] = '登陆成功';
            UserModel::getInstance()->recordLastLoginTime($uname);
        }else{
            $data['code'] = 1;
            $data['errmsg'] = '用户名或密码错误';
        }
        return $data;
    }
    
    public function ajax_register(){
        $uname = input('post.username');
        $upwd = input('post.password');
        $utname = input('post.truename');
        $usid = input('post.studentid');
        $uphn = input('post.phonenumber');
        $uemail = input('post.email');
        $data = array();
        $a=UserModel::getInstance()->checkUsernameIfExist($uname);
        if($a===false)
        {
            $data['errmsg'] = "用户名已存在，请重新输入";
            return $data;
        }
        else {
            $c=UserModel::getInstance()->insertUsernameAndPassword($uname,$upwd,$utname,$usid,$uphn,$uemail);
            if($c===1){
                $data['code'] = 0;
            }else{
                $data['code'] = 1;
                $data['errmsg'] = $c;
            }
            return $data;
        }
    }
}
