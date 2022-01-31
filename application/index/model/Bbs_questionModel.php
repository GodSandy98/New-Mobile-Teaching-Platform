<?php
namespace app\index\model;

use think\Model;

class Bbs_questionModel extends Model{
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
        $this->db = db('bbs_q');
    }
    
    //定义主键
    protected $pk = 'id';
    
    private $keylist = [
        'id',
        'userID',
        'lessionID',
        'title',
        'content',
        'answerNum',
        'createtime'
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
    
    public function insertOne($lessionID,$userID,$title,$question){
        $data = [];
        $data['userID'] = $userID;
        $data['lessionID'] = $lessionID;
        $data['title'] = $title;
        $data['content'] = $question;
        try{
            $a=$this->db->insert($data);
            $ans = $a;
        }catch(\Exception $e){
            $ans =$e->getMessage();
        }       
        if($ans === 1){
            return 1;
        }
        else{
            return $ans;
        }
    }
    
    
}