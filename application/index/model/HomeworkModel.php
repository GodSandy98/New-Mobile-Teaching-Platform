<?php
namespace app\index\model;

use think\Model;

class HomeworkModel extends Model
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
        $this->db = db('homework');
    }

    //定义主键
    protected $pk = 'id';

    private $keylist = [
        'id',
        'userID',
        'lessionID',
        'title',
        'content',
        'starttime',
        'endtime',
        'createtime',
        'file_MD5',
        'knowledgePointID',
        'test_case',
        'correct_result',
        'test_case1',
        'correct_result1'
    ];

    //根据查询条件查询结果
    public function getListByCon($condition,$limit = null){
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
    
    public function deleteOne($id,$lessionID){
        try{
            $a=$this->db->where('id',$id)->where('lessionID',$lessionID)->delete();
            $ans = $a;
        }catch(\Exception $e){
            $ans = $e->getMessage();
        }
        return $ans;
    }

    public function getTestCases($id) {
        try {
            $data = $this->db->where('id', $id);
            $data = $this->db->select();
        } catch(\Exception $e) {
            $data = $e->getMessage();
        }
        return $data;
    }

    public function getHomeworkIDsByLessonID($lessonID){        
        $this->db->where('lessionID', $lessonID);
        $data = $this->db->select();
        if ($data) {
            $res = array();
            for($i=0; $i<count($data); $i++){
                $res[$i] = $data[$i]['id'];
            }
            return $res;
        } else {
            die('出错啦');
        }
    }
}