<?php
namespace app\index\model;

use think\Model;

class ShareModel extends Model
{
    //定义主键
    protected $pk = 'MD5';

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
        $this->db = db('share');
    }

    private $keylist = [
        'id',
        'userID',
        'lessionID',
        'name',
        'resource_md5',
        'createtime'
    ];

    public function getListByCon($condition, $limit=null, $field = null, $group = null){
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
        $this->db->order('createtime desc');
        if(!empty($limit)){
            $this->db->limit($limit);
        }
        return $this->db->select();
    }
    
    public function insertOneShareFile($md5,$userID,$lessionID,$name){
        $data = [];
        $data['userID'] = $userID;
        $data['lessionID'] = $lessionID;
        $data['name'] = $name;
        $data['resource_md5'] = $md5;
        $data['id'] = md5(time().$userID.$md5.$name);
        try{
            $res = $this->db->insert($data);
        }catch(\Exception $e){
            $res = $e->getMessage();
        }
        if($res === 1){
            return true;
        }else{
            return $res;
        }
    }
    
    public function deleteOne($id,$lessionID){
        try{
            $a=$this->db->where('id',$id)->where('lessionID',$lessionID)->delete();
            $ans = $a;
        }catch(\Exception $e){
            $ans = $e->getMessage();
        }
        return $ans;
    }
}