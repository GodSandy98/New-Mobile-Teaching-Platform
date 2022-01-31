<?php
namespace app\index\model;

use think\Model;
use \Think\Db;
use think\helper\hash\Md5;   //供MD5加密使用

class UserModel extends Model
{
    //定义主键
    protected $pk = 'ID';
    
    private $db;
    
    private static $_instance;
    
    public static function getInstance()
    {
        if(!(self::$_instance instanceof self)){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    //自定义初始化
    protected function initialize()
    {
        //需要调用`Model`的`initialize`方法
        parent::initialize();
        //TODO:自定义的初始化
        $this->db = db('user');
    }
    
    private $keylist = [
        'ID',
        'img',
        'name',
        'phone',
        'CardId',
        'type',
        'email',
        'sex',
        'school',
        'academy',
        'major',
        'studentID',
        'username',
        'password',
        'LastLogInTime'
    ];
    
    //删除密码
    private function clearResult($result){
        if(!is_array($result)) return $result;
        foreach ($result as $key => $val){
            $result[$key] = $this->unsetPassword($result[$key]);
        }
        return $result;
    }
    
    private function unsetPassword($result){
        unset($result['password']);
        return $result;
    }
    
    public function checkUsernameAndPassword($username,$password){
        $result = $this->db
        ->where('username','like',$username)
        ->where('password',Md5($password))
        ->find();
        return $this->unsetPassword($result);

    }
    public function recordLastLoginTime($username){
        $showtime=date("Y-m-d H:i:s");
        $this->db
        ->where('username',$username)
        ->setField('LastLogInTime', $showtime);
    }
    
    public function checkUsernameIfExist($username){
        $c=$this->db->where('username',$username)->find();
        if($c === null){
            return true;
        }
        else{
            return false;
        }
    }

    public function insertUsernameAndPassword($username,$password,$utname,$usid,$uphn,$uemail){

        $data = ['username' => $username, 'password' => Md5($password), 'name'=>$utname, 'studentID'=>$usid, 'phone'=>$uphn, 'email'=>$uemail];
        $ans = null;
        
        try{
            $a=$this->db->insert($data);
            $ans = $a;
        }catch(\Exception $e){
            $ans =$e->getMessage();
        }
        return $ans;
    }
    
    //根据查询条件查询结果
    public function getListByCon($condition){
        foreach ($condition as $key=>$val){
            foreach ($this->keylist as $k){
                if($key == $k){
                    if(is_array($val)){
                        if($val['op'] && $val['val']){
                            if($key === 'password'){
                                foreach ($val['val'] as $k => $v){
                                    $val['val'][$k] = Md5($val['val'][$k]);
                                }
                            }
                            $this->db->where($key,$val['op'],$val['val']);
                        }
                    }else{
                        if($key === 'password'){
                            $val = Md5($val);
                        }
                        $this->db->where($key,$val);
                    }
                    break;
                }
            }
        }
        return $this->clearResult($this->db->select());
    }
    
    public function getNameByID($id) {
        $this->db->where('ID', $id);        
        $data = $this->db->select();
        if($data) {
            return $data[0]['name'];
        } else {
            die('出错啦');
        }
    }

    //根据用户名或者学号查询
    public function searchByStudentIDOrUsername($userName){
        $this->db->where('username|studentID',$userName);
        return $this->clearResult($this->db->select());
    }
    
    public function updateOneRecord($condition,$ID){
        try{
            $res = $this->db->where('ID',$ID)->update($condition);
        }catch(\Exception $e){
            $res =$e->getMessage();
        }
        if($res === 1){
            return true;
        }else if($res === 0){
            return '无更新数据';
        }else{
            return $res;
        }
    }
}

