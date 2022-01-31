<?php
namespace app\index\model;

use think\Model;

class Lession_applyModel extends Model
{
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
        $this->db = db('lession_apply');
    }

    //定义主键
    protected $pk = 'id';

    private $keylist = [
        'id',
        'userID',
        'lessionID'
    ];

    //根据查询条件查询结果
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
                }
            }
        }
        return $this->db->select();
    }

    public function insertOne($lessionID,$userID){
        $data = [];
        $data['userID'] = $userID;
        $data['lessionID'] = $lessionID;
        try{
            $a=$this->db->insert($data);
            $ans = $a;
        }catch(\Exception $e){
            $ans =$e->getMessage();
        }
        return $ans;
    }
    public function deleteOne($lessionID,$userID){
        try{
            $a=$this->db->where('lessionID',$lessionID)->where('userID',$userID)->delete();
            $ans = $a;
        }catch(\Exception $e){
            $ans =$e->getMessage();
        }
        return $ans;
    }
}