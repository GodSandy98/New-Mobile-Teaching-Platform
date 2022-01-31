<?php
namespace app\index\controller;

use app\index\model\UserModel;
use think\helper\hash\Md5;
class User extends Checkuser{
    
    private static $_instance;
    
    public static function getInstance()
    {
        if(!(self::$_instance instanceof self)){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    public function userInfo()
    {
        $userID = session('userID');
        if(empty($userID)) $this->redirect('index/index/index');
        $userInfo = UserModel::getInstance()->getListByCon(['ID'=>$userID]);
        if(empty($userInfo)) $this->redirect('index/index/index');
        $userInfo = $userInfo[0];
        $this->assign('userID',$userID);
        $this->assign('userInfo',$userInfo);
        return $this->fetch('userInfo');
    }
    public function searchByCondition($con){
        return UserModel::getInstance()->getListByCon($con);
    }

    public function getNameByID($id) {
        $res = UserModel::getInstance()->getNameByID($id);
        return $res;
    }
    
    public function ajax_searchUserByUserOrStudentID(){
        $userInfo = input('post.UserName');
        $userList = UserModel::getInstance()->searchByStudentIDOrUsername($userInfo);
        $data = array();
        $data['code'] = 0;
        $data['data'] = $userList;
        return $data;
    }
    public function ajax_checkPasswordOfUser(){
        $ID = session('userID');
        $password = input('post.oldpass');  
        $pass = Md5($password);
        $data = [];       
        if(empty($ID) || empty($password)){
            $data['code'] = 1023;
            $data['errmsg'] = '缺少必要参数';
            return $data;
        }
        $array=array("ID"=>$ID, "password"=>$password);
        $res= UserModel::getInstance()->getListByCon($array);
        if(empty($res)){
            $data['code'] = 1111;
            $data['errmsg'] = '原密码错误';
            return $data;
        }else{
            $data['code'] = 0;
            $data['data'] = "原密码正确";
            return $data;
        }
        
    }
    public function ajax_changeInfoOfUser(){
        $ID = session('userID');
        $kind = input('post.kind');
        $value = input('post.value');
        $data = [];       
        if(empty($ID) || empty($kind) || empty($value)){
            $data['code'] = 1023;
            $data['errmsg'] = '缺少必要参数';
            return $data;
        }
        
        $user= UserModel::getInstance()->getListByCon(['ID'=>$ID]);
      
        if(empty($user)){
            $data['code'] = 1011;
            $data['errmsg'] = '没有此用户';
            return $data;
        }
        if($kind=="password"){ $value = Md5($value);}
        
        $res = UserModel::getInstance()->updateOneRecord([$kind=>$value], $ID);        
        if($res === true){
            $data['code'] = 0;
            $data['data'] = '更新成功';
            return $data;
        }else{
            $data['code'] = 2;
            $data['errmsg'] = $res;
            return $data;
        }
    }
   
    
}