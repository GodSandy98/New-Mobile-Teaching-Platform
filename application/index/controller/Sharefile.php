<?php
namespace app\index\controller;

use app\index\model\ResourceModel;
use app\index\model\ShareModel;
use extend\pdf\office2pdf;
use app\index\model\Homework_submitModel;
use app\index\model\UserModel;
use app\index\model\HomeworkModel;

class Sharefile extends Checkuser{

    private static $_instance;

    public static function getInstance()
    {
        if(!(self::$_instance instanceof self)){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    private function saveShareFile($md5,$userID,$lessionID,$name){
        return ShareModel::getInstance()->insertOneShareFile($md5, $userID, $lessionID, $name);
    }
    
    public function shareFile($file,$filename,$total,$index,$md5,$lessionID){
        $data = [];
        try{
            $res = File::getInstance()->uploadFile($file, $md5, $total, $index, session('username'));
            if($res['code'] === 0){
                $data['code'] = $res['code'];
                $data['data'] = $res['data'];
                if($data['data'] == $total){
                    $res1 = ResourceModel::getInstance()->insertOneInfo($md5, config('file_save_path').DS.$md5);
                    if($res1 !== true){
                        $data['code'] = 2;
                        $data['errmsg'] = $res1;
                        return $data;
                    }else{
                        $res2 = ShareModel::getInstance()->insertOneShareFile($md5, session('userID'), $lessionID, $filename);
                        if($res2 !== true){
                            $data['code'] = 4;
                            $data['errmsg'] = $res2;
                            return $data;
                        }
                    }
                }
            }else if($res['code'] === 1){
                $data['code'] = $res['code'];
                $data['errmsg'] = $res['msg'];
                return $data;
            }else{
                $data['code'] = 101;
                $data['errmsg'] = $res['msg'];
                return $data;
            }
        }catch(\Exception $e){
            $data['code'] = 3;
            $data['errmsg'] = $e->getMessage();
            return $data;
        }
        return $data;
    }
    
    public function checkFile($md5,$username,$total,$lessionID,$filename){
        $con = [];
        $con['MD5'] = $md5;
        $data = [];
        $res = ResourceModel::getInstance()->getListByCon($con);  //拿着MD5值去resource表中查看是否有此文件
        if(!is_array($res)){
            $data['code'] = 101;
            $data['errmsg'] = $res;
            return $data;
        }
        if(empty($res)){
            $data['code'] = 0;
            $data['data'] = File::getInstance()->checkFile($md5, $username,$total);   //去查看临时文件夹中是否有此文件，
            //如果有就返回现在上传到的文件的段数，以返回前端供进度动画条使用
            return $data;
        }else{
            $res1 = ShareModel::getInstance()->insertOneShareFile($md5, session('userID'), $lessionID, $filename);
            if($res1 === true){
                $data['code'] = 1;
                $data['data'] = '分享资源成功';
                return $data;
            }else{
                $data['code'] = 102;
                $data['errmsg'] = $res1;
                return $data;
            }
        }
    }
    
    //内部调用，获取分享资源列表
    public function getShareFileList($lessionID,$limit=null,$searchByName=null) {
        if(empty($lessionID)){
            return '缺少课程ID';
        }
        
        $con = [];
        $con['lessionID'] = $lessionID;
        if(!empty($searchByName)){
            $arr = [];
            $arr['op'] = 'like';
            $arr['val'] = '%'.$searchByName.'%';
            $con['name'] = $arr;
        }
        
        try{
            $res = ShareModel::getInstance()->getListByCon($con,$limit);
        }catch(\Exception $e){
            $res = $e->getMessage();
        }
        
        if(is_array($res)){
            foreach ($res as $k => $v){
                $res[$k]['file_type'] = Index::getInstance()->getFileType($v['name']);
            }
            return $res;
        }else{
            return '查询数据库出错：'.$res;
        }
    }
    
    public function dowloadFile(){
        $id = input('get.id');
        $type = input('get.type');
        
        if(empty($id) || empty($type)){
            echo '缺少必要参数';
            exit;
        }
        
        $res = $this->getFile_address($id,$type);
        
        if($res['code'] === 0){
            $address = $res['address'];
            $name = $res['name'];
            $md5 = $res['md5'];
        }else{
            echo $res['errmsg'];
            exit;
        }
        
        if($type === 'ShareFile'){
            ShareFileRecord::getInstance()->dowload_addOneRecord($id, session('userID'));
        }else if($type ==='Submit'){
            //2017年3.2 更改下载文件名为学号_作业号.后缀
            $ss = explode(".", $name);
            $ss = $ss[1];
            $con1['id'] = $id;          
            $res1 = Homework_submitModel::getInstance()->getListByCon($con1);            
            $userid = $res1[0]['userID'];
            $homeworkID = $res1[0]['homeworkID'];
            $con3['id'] = $homeworkID;
            $res3 = HomeworkModel::getInstance()->getListByCon($con3);
            $teacherid = $res3[0]["userID"];
            if($teacherid == 4){
                $con2['ID'] = $userid;          
                $res2 = UserModel::getInstance()->getListByCon($con2);
                $res0 =$res2[0]['studentID'];            
                $name =$res0."_".$homeworkID.".".$ss;
            }
        }
         
           
           
           
            
            
            
        File::getInstance()->dowloadFile($address,$name,$md5);
    }
    
    public function getFile_address($id,$type){
        if($type === 'ShareFile'){
            $con1 = [];
            $con1['id'] = $id;
            $res1 = ShareModel::getInstance()->getListByCon($con1);
            if(empty($res1)){
                $data['code'] = 2;
                $data['errmsg'] = '没有此条记录';
                return $data;
            }
            $md5 = $res1[0]['resource_md5'];
            $name = $res1[0]['name'];
        
            if(Lession::getInstance()->checkIfUserInLession(session('userID'), $res1[0]['lessionID']) !== true){
                $data['code'] = 3;
                $data['errmsg'] = '您没有访问此文件权限';
                return $data;
            }
        
            if(empty($md5)){
                $data['code'] = 4;
                $data['errmsg'] = '文件不存在';
                return $data;
            }
        }else if($type === 'Homework'){
            $con1 = [];
            $con1['id'] = $id;
            $res1 = Homework::getInstance()->searchByCondition($con1);
            if(empty($res1)){
                $data['code'] = 5;
                $data['errmsg'] = '没有此条记录';
                return $data;
            }
            $md5 = $res1[0]['file_MD5'];
            $name = $res1[0]['filename'];
        
            if(Lession::getInstance()->checkIfUserInLession(session('userID'), $res1[0]['lessionID']) !== true){
                $data['code'] = 6;
                $data['errmsg'] = '您没有访问此文件权限';
                return $data;
            }
        
            if(empty($md5)){
                $data['code'] = 7;
                $data['errmsg'] = '文件不存在';
                return $data;
            }
            
        }else if($type === 'Submit'){
            $con1 = [];
            $con1['id'] = $id;
            $res1 = Homework::getInstance()->searchByCondition_homework_submit($con1);
            if(empty($res1)){
                $data['code'] = 8;
                $data['errmsg'] = '没有此条记录';
                return $data;
            }
            $md5 = $res1[0]['file_MD5'];
            $name = $res1[0]['filename'];
        
            if(session('usertype') !== 'teacher' && $res1[0]['userID'] !== session('userID')){
                $data['code'] = 9;
                $data['errmsg'] = '您没有访问此文件权限';
                return $data;
            }
        
            $homeworkID = $res1[0]['homeworkID'];
            if(empty($homeworkID)){
                $data['code'] = 10;
                $data['errmsg'] = '缺少作业ID';
                return $data;
            }
        
            $res2 = Homework::getInstance()->searchByCondition(['id'=>$homeworkID]);
            if(empty($res2)){
                $data['code'] = 11;
                $data['errmsg'] = '没有此条作业记录';
                return $data;
            }
        
            if(Lession::getInstance()->checkIfUserInLession(session('userID'), $res2[0]['lessionID']) !== true){
                $data['code'] = 12;
                $data['errmsg'] = '您没有访问此文件权限';
                return $data;
            }
        
            if(empty($md5)){
                $data['code'] = 13;
                $data['errmsg'] = '文件不存在';
                return $data;
            }
        }else{
            $data['code'] = 14;
            $data['errmsg'] = '无此类型';
            return $data;
        }
        
        $con2 = [];
        $con2['MD5'] = $md5;
        $res2 = ResourceModel::getInstance()->getListByCon($con2);
        if(empty($res2)){
            $data['code'] = 14;
            $data['errmsg'] = '源文件记录不存在';
            return $data;
        }
        
        $data['code'] = 0;
        $data['address'] = $res2[0]['address'];
        $data['name'] = $name;
        $data['md5'] = $md5;
        return $data;
    }
    
    public function ajax_getShareFileAddress(){
        $id = input('post.id');
        $type = input('post.type');
        
        $data = [];
        
        if(empty($id) || empty($type)){
            $data['code'] = 1;
            $data['errmsg'] = '缺少必要参数';
            return $data;
        }
        
        $address = $this->getFile_address($id,$type);
        
        if($address['code'] === 0){
            $data['code'] = 0;
            $data['data'] = str_replace(DS,'/','uploads'.DS.$address['address']);
        }else{
            $data = $address;
        }
        
        if($type === 'ShareFile'){
            ShareFileRecord::getInstance()->preview_addOneRecord($id, session('userID'));
        }
        
        return $data;
        
    }
    
    public function ajax_getShareFileAddress_office(){
        $id = input('post.id');
        $type = input('post.type');
        
        $data = [];
        
        if(empty($id) || empty($type)){
            $data['code'] = 1;
            $data['errmsg'] = '缺少必要参数';
            return $data;
        }
        
        $address = $this->getFile_address($id,$type);
        
        if($address['code'] === 0){
            $address = str_replace(DS,'/','uploads'.DS.$address['address']);
        }else{
            $data = $address;
            return $data;
        }
        
        if(!file_exists(ROOT_PATH.'public'.DS.$address.'.pdf')){
            try{
                $res = office2pdf::getInstance()->run(ROOT_PATH.'public'.DS.$address, ROOT_PATH.'public'.DS.$address.'.pdf');
            }catch(\Exception $e){
                $res = $e->getMessage();
            }
            if(!is_numeric($res) || $res <= 0){
                $data['code'] = 123;
                $data['errmsg'] = '创建pdf文件失败';
                return $data;
            }
        }
        
        $data['code'] = 0;
        $data['data'] = $address.'.pdf';
        
        if($type === 'ShareFile'){
            ShareFileRecord::getInstance()->preview_addOneRecord($id, session('userID'));
        }
        
        return $data;
        
    }
    
    public function searchByCondition($condition){
        return ShareModel::getInstance()->getListByCon($condition);
    }
    
    public function previewVideo(){
        $id = input('get.id');
        $type = input('get.type');
        
        if(empty($id) || empty($type)){
            echo '缺少必要参数';
            exit;
        }
        
        $res = $this->getFile_address($id,$type);
        
        if($res['code'] === 0){
            $address = $res['address'];
            $name = $res['name'];
            $md5 = $res['md5'];
        }else{
            echo $res['errmsg'];
            exit;
        }
        
        File::getInstance()->watchVideo($address,$name,$md5);
    }
    
    public function deleteOneShareFile($id,$lessionID){
        return ShareModel::getInstance()->deleteOne($id, $lessionID);
    }
    
    public function test(){
        echo 'a<br/>';
        $address = 'uploads'.DS.'file\1258af715e0e3d679e45577864dbcea7';
        try{
            $res = office2pdf::getInstance()->run(ROOT_PATH.'public'.DS.$address, ROOT_PATH.'public'.DS.$address.'.pdf',true);
        }catch (\Exception $e){
            $res = $e->getMessage();
        }
        echo $res;
    }
    
}