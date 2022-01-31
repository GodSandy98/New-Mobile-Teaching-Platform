<?php
namespace app\index\model;

use think\Model;

class statistic_previewModal extends Model
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
        $this->db = db('statistic_preview');
    }

    //定义主键
    protected $pk = 'id';

    private $keylist = [
        'id',
        'userID',
        'shareFileID',
        'lasttime',
        'Num',
    ];
    
    //根据查询条件查询结果
    public function getListByCon($condition,$limit = null,$field = null,$group = null){
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
        if(!empty($limit)){
            $this->db->limit($limit);
        }
        if(!empty($field)){
            $this->db->field($field);
        }
        if(!empty($group)){
            $this->db->group($group);
        }
        return $this->db->select();
    }
    
    public function insertOneRecord($condition){
        try{
            $res = $this->db->insert($condition);
        }catch(\Exception $e){
            $res =$e->getMessage();
        }
        if($res === 1){
            return true;
        }else{
            return $res;
        }
    }
    
    public function updateOneRecord($condition,$id){
        try{
            $res = $this->db->where('id',$id)->update($condition);
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