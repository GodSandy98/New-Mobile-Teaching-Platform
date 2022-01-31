<?php
namespace app\index\model;

use think\Model;

class ResourceModel extends Model
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
        $this->db = db('resource');
    }

    private $keylist = [
        'MD5',
        'type',
        'address',
        'usedNum',
        'createTime'
    ];
    
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
    
    public function insertOneInfo($md5,$address){
        $data = [];
        $data['MD5'] = $md5;
        $res = $this->getListByCon($data);
        if(empty($res)){
            $data['address'] = $address;
            try{
                $res1 = $this->db->insert($data);
            }catch(\Exception $e){
                $res1 = $e->getMessage();
            }
            if($res1 === 1){
                return true;
            }else{
                return $res1;
            }
        }else{
            return true;
        }
    }
    
    public function deleteByKey($key){
        return $this->db->delete($key);
    }

    public function getFileAddress($file_MD5) {
        $this->db->where('MD5', $file_MD5);
        $data = $this->db->select();
        return $data[0]['address'];
    }
}