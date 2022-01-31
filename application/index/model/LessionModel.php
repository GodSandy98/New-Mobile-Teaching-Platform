<?php
namespace app\index\model;

use think\Model;

class LessionModel extends Model
{
    //定义主键
    protected $pk = 'id';

    private static $_instance;
    
    private $db;

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
        $this->db = db('lession');
    }
    
    private $keylist = [
        'id',
        'img',
        'lessionName',
        'masterID',
        'createTime'
    ];
    
    //根据查询条件查询结果$condision = ['id'=>'123','img'=>'asdfasdf','lessionName'=>array('op'=>'in','val'=>array('1','2','3'))]
    public function getListByCon($condition){
        foreach ($condition as $key=>$val){
            foreach ($this->keylist as $k){
                if($key == $k){
                    if(is_array($val)){
                        if($val['op'] && $val['val']){
                            $this->db->where($key,$val['op'],$val['val']);
                        }
                    }else{
                        $this->db->where($key,$val);
                    }
                    break;
                }
            }
        }
        return $this->db->select();
    }
    
    public function rewriteLessionName($lessionName,$courseID) {
        try{
            $a=$this->db->where('id', $courseID)->update(['lessionName' => $lessionName]);
            $ans = $a;
        }catch(\Exception $e){
            $ans =$e->getMessage();
        }
        return $ans;
    }
    
    //创建一门课程，成功返回true，失败返回string型错误原因
    public function createLession($userID,$lessionName) {
        if(empty($userID)){
            return '缺少用户名';
        }else if(empty($lessionName)){
            return '缺少课程名称';
        }
        $user = UserModel::getInstance()->getListByCon(['ID'=>$userID]);
        if(empty($user)){
            return '没有此用户';
        }else if($user[0]['type'] !== 'teacher'){
            return '此用户身份不是教师';
        }
        try{
            $res = $this->db->insert(['lessionName'=>$lessionName,'masterID'=>$userID]);
        }catch(\Exception $e){
            $res =$e->getMessage();
        }
        if($res === 1){
            $lessionId = $this->db->getLastInsID();
            if($res2 = Lession_userModel::getInstance()->insertOne($lessionId,$userID) === 1){
                return true;
            }else{
                return '插入课程关系失败:'.$res2;
            }
        }else{
            return '插入课程失败:'.$res;
        }
    }
}