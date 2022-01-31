<?php
namespace app\index\model;

use think\Model;

class Homework_submitModel extends Model
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
        $this->db = db('homework_submit');
    }

    //定义主键
    protected $pk = 'id';

    private $keylist = [
        'id',
        'userID',
        'homeworkID',
        'score',
        'filename',
        'file_MD5',
        'createtime',
        'compile_result',
        'run_result',
        'run_time',
        'chachong'
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
        $this->db->order('id desc');
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

    public function getFileMD5ById($id) {
        $this->db->where('id', $id);
        $data = $this->db->select();
        if($data) {
            return $data[0]['file_MD5'];
        } else {
            die('出错啦');
        }
    }

    public function getFileNameById($id) {
        $this->db->where('id', $id);
        $data = $this->db->select();
        if($data) {
            return $data[0]['filename'];
        } else {
            die('出错啦');
        }
    }
    
    public function getUserIDByFileMD5($MD5) {
        $this->db->where('file_MD5', $MD5);        
        $data = $this->db->select();
        if($data) {
            return $data[0]['userID'];
        } else {
            die('出错啦');
        }
    }

    public function saveCompileResult($attributes) {
        $sql = "update homework_submit set compile_result = '". $attributes['compile_result'] ."' , run_result = '". $attributes['run_result'] ."' ,run_time = ". $attributes['run_time'] ." ,score = ". $attributes['score'] ." where id = ".$attributes['id'];
        try{
            $res = $this->db->execute($sql);
        }catch(\Exception $e){
            $res =$e->getMessage();
            return 0;
        }
        
        if($res == 1) {
            return 1;
        } else {
            return 0;
        }
    }

    public function setChachong($id1, $id2) {
        $sql = "update homework_submit set chachong = 1 where id in (". $id1 .",". $id2 .")";
        try {
            $res = $this->db->execute($sql);
        }catch(\Exception $e) {
            $res = $e->getMessage();
            return 0;
        }

        if($res == 2) {
            return 1;
        } else {
            return 0;
        }
    }

    public function getBianyiCorrectNum($id) {
        $sql = "select count(*) num from homework_submit where userID = ". $id ." and compile_result = '编译成功'";
        $res = $this->db->query($sql);
        return $res[0]['num'];
    }

    public function getRunCorrectNum($id) {
        $sql = "select count(*) num from homework_submit where userID = ". $id ." and run_result = '结果正确'";
        $res = $this->db->query($sql);
        return $res[0]['num'];
    }
}