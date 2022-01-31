<?php
namespace app\index\controller;

use app\index\model\HomeworkModel;
use app\index\model\Homework_submitModel;
use app\index\model\ResourceModel;

class Homework extends Checkuser
{

    private static $_instance;

    public static function getInstance()
    {
        if(!(self::$_instance instanceof self)){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function getHomeworkList($lessionID,$limit=null){
        if(empty($lessionID)){
            return '缺少课程ID';
        }
        
        $con = [];
        $con['lessionID'] = $lessionID;
        
        try{
            $res = HomeworkModel::getInstance()->getListByCon($con,$limit);
        }catch(\Exception $e){
            $res = $e->getMessage();
        }
        
        if(is_array($res)){
            return $res;
        }else{
            return '查询数据库出错：'.$res;
        }
    }
    
    //往数据库中写入一条记录
    public function addOneHomework($con){
        return HomeworkModel::getInstance()->insertOneRecord($con);
    }
    
    
    //往数据库中写入（更新）一条记录
    public function addOneHomework_submit($con){
        $con1 = [];
        $con1['userID'] = $con['userID'];
        $con1['homeworkID'] = $con['homeworkID'];
        $res1 = Homework_submitModel::getInstance()->getListByCon($con1);
        if(empty($res1)){
            return Homework_submitModel::getInstance()->insertOneRecord($con);
        }else{
            return $this->updateOneHomework_submit($con, $res1[0]['id']);
        }
    }
    
    public function updateOneHomework_submit($con,$id){
        return Homework_submitModel::getInstance()->updateOneRecord($con, $id);
    }
    
    //判断上传文件的作业是否已经存在
    public function checkIfHomeworkExist($con,$total,$username,$controller,$function){
        $md5 = $con['file_MD5'];
        $filename = $con['filename'];
        $data = [];
        if(empty($md5) || empty($filename)){
            $data['code'] = 105;
            $data['errmsg'] = '缺少必要参数';
            return $data;
        }
        
        $res1 = File::getInstance()->checkIfFileExist($md5);
        if(!is_array($res1)){
            $data['code'] = 101;
            $data['data'] = $res1;
            return $data;
        }
        if(empty($res1)){
            $data['code'] = 0;
            $data['data'] = File::getInstance()->checkFile($md5, $username,$total);
            return $data;
        }else{
            $res2 = $controller->$function($con);
            if($res2 === true){
                $data['code'] = 1;
                $data['data'] = '提交作业成功！';
                return $data;
            }else{
                $data['code'] = 102;
                $data['errmsg'] = $res2;
                return $data;
            }
        }
    }
    
    //设置作业并上传文件
    public function uploadHomeworkwithFile($con,$file,$total,$index,$md5,$username,$controller,$function){
        try{
            $res = File::getInstance()->SaveFileAndData($con, $file, $total, $index, $md5, $username,$controller,$function);
        }catch(\Exception $e){
            $res = $e->getMessage();
        }
        if(is_array($res)){
            return $res;
        }else{
            $data = [];
            $data['code'] = 203;
            $data['errmsg'] = $res;
            return $data;
        }
        
    }
    
    public function searchByCondition($con){
        return HomeworkModel::getInstance()->getListByCon($con);
    }
    
    public function searchByCondition_homework_submit($con,$limit=null,$field=null,$group=null){
        return Homework_submitModel::getInstance()->getListByCon($con,$limit,$field,$group);
    }
    
    public function ajax_checkFile_homework_submit(){
        $userID = session('userID');
        $homeworkID = input('post.homeworkID');
        $total = input('post.total');
        $filename = input('post.filename');
        $md5 = input('post.md5');
        
        $data = [];
        
        if(empty($homeworkID) || empty($total) || empty($filename) || empty($md5) || empty($userID)){
            $data['code'] = 1011;
            $data['errmsg'] = '缺少必要参数';
            return $data;
        }
        
        $homework_res = HomeworkModel::getInstance()->getListByCon(['id'=>$homeworkID]);
        
        if(empty($homework_res)){
            $data['code'] = 1013;
            $data['errmsg'] = '无此作业';
            return $data;
        }
        
        $lessionID = $homework_res[0]['lessionID'];
        
        if((Lession::getInstance()->checkIfUserInLession($userID, $lessionID)) !== true){
            $data['code'] = 1012;
            $data['errmsg'] = '您不在此课程中';
            return $data;
        }
        
        if((Lession::getInstance()->checkIfUserHasAuthorityToSubmit($userID, $lessionID)) !== true){
            $data['code'] = 1019;
            $data['errmsg'] = '您没有权限上传作业';
            return $data;
        }
        
        $starttime = strtotime($homework_res[0]['starttime']);
        $endtime = strtotime($homework_res[0]['endtime']);
        $nowtime = time();
        
        if($nowtime < $starttime || $nowtime > $endtime){
            $data['code'] = 1015;
            $data['errmsg'] = '不在上传作业的时间内';
            return $data;
        }
        
        $con = [];
        $con['userID'] = $userID;
        $con['homeworkID'] = $homeworkID;
        $con['filename'] = $filename;
        $con['file_MD5'] = $md5;
        
        try{
            $res = Homework::getInstance()->checkIfHomeworkExist($con,$total,session('username'),Homework::getInstance(),'addOneHomework_submit');
        }catch(\Exception $e){
            $res = $e->getMessage();
        }
        if(!is_array($res)){
            $data['code'] = 1013;
            $data['errmsg'] = $res;
            return $data;
        }else{
            return $res;
        }
        
    }
    
    public function ajax_submitHomework_withfile(){
        $file = request()->file('file');
        $userID = session('userID');
        $homeworkID = input('post.homeworkID');
        $total = input('post.total');
        $filename = input('post.filename');
        $md5 = input('post.md5');
        $index = input('post.index');
        
        $data = [];
        
        if(empty($homeworkID) || empty($total) || empty($filename) || empty($md5) || empty($userID) || empty($index)){
            $data['code'] = 1011;
            $data['errmsg'] = '缺少必要参数';
            return $data;
        }
        
        $homework_res = HomeworkModel::getInstance()->getListByCon(['id'=>$homeworkID]);
        
        if(empty($homework_res)){
            $data['code'] = 1013;
            $data['errmsg'] = '无此作业';
            return $data;
        }
        
        $lessionID = $homework_res[0]['lessionID'];
        
        if((Lession::getInstance()->checkIfUserInLession($userID, $lessionID)) !== true){
            $data['code'] = 1012;
            $data['errmsg'] = '您不在此课程中';
            return $data;
        }
        
        if((Lession::getInstance()->checkIfUserHasAuthorityToSubmit($userID, $lessionID)) !== true){
            $data['code'] = 1019;
            $data['errmsg'] = '您没有权限上传作业';
            return $data;
        }
        
        $starttime = strtotime($homework_res[0]['starttime']);
        $endtime = strtotime($homework_res[0]['endtime']);
        $nowtime = time();
        
        if($nowtime < $starttime || $nowtime > $endtime){
            $data['code'] = 1015;
            $data['errmsg'] = '不在上传作业的时间内';
            return $data;
        }
        
        $con = [];
        $con['userID'] = $userID;
        $con['homeworkID'] = $homeworkID;
        $con['filename'] = $filename;
        $con['file_MD5'] = $md5;
        
        try{
            $res = Homework::getInstance()->uploadHomeworkwithFile($con,$file,$total,$index,$md5,session('username'),Homework::getInstance(),'addOneHomework_submit');
        }catch(\Exception $e){
            $res = $e->getMessage();
        }
        if(is_array($res)){
            return $res;
        }else{
            $data['code'] = 2032;
            $data['errmsg'] = $res;
            return $data;
        }
        
    }
    
    public function ajax_getUserSubmitByHomeworkID(){
        $homeworkID = input('post.homeworkID');
        $userID = session('userID');
        
        $data = [];
        
        if(empty($homeworkID) || empty($userID)){
            $data['code'] = 101;
            $data['errmsg'] = '缺少必要参数';
            return $data;
        }
        
        $res = Homework_submitModel::getInstance()->getListByCon(['homeworkID'=>$homeworkID,'userID'=>$userID]);
        
        $data['code'] = 0;
        if(empty($res)){
            $data['data'] = null;
        }else{
            $data['data'] = $res[0]['filename'];
        }   
        return $data;
    }

    public function deleteOneHomework($homeworkID, $lessionID){
        return HomeworkModel::getInstance()->deleteOne($homeworkID, $lessionID);
    }

    /**
     * y
     */
    public function saveCompileResult() {
        $attributes = [];
        $attributes['compile_result'] = input('get.compile_result');
        $attributes['run_result'] = input('get.run_result');
        $attributes['run_time'] = input('get.run_time');
        $attributes['id'] = input('get.id');
        $attributes['score'] = input('get.score');
        $res = Homework_submitModel::getInstance()->saveCompileResult($attributes);
        if($res == 1) {
            return json_encode(array(
                'code' => 1));
        } else {
            return json_encode(array(
                'code' => 1,
                'id' => $attributes['id']));
        }
    }

    public function getFileNameById($id) {
        $res = Homework_submitModel::getInstance()->getFileNameById($id);
        return $res;
    }

    public function getFileMD5ById($id) {
        $res = Homework_submitModel::getInstance()->getFileMD5ById($id);
        return $res;
    }

    public function getUserIDByMD5andHomeworkID($MD5, $homeworkID) {
        $res = Homework_submitModel::getInstance()->getUserIDByMD5andHomeworkID($MD5, $homeworkID);
        return $res;
    }

    public function getUserIDWithSameFile($homeworkID) {
        $res = Homework_submitModel::getInstance()->getUserIDWithSameFile($homeworkID);
        return $res;
    }

    public function getFileAddress($file_MD5) {
        $res = ResourceModel::getInstance()->getFileAddress($file_MD5);
        return $res;
    }
    
    public function getTestCases($id) {
        $data = HomeworkModel::getInstance()->getTestCases($id);
        return $data;
    }

    public function getFileIDByUserIDandHomeworkID($user, $homework) {
        $res = Homework_submitModel::getInstance()->getFileIDByUserIDandHomeworkID($user, $homework);
        return $res;
    }

    public function getCopyNum($userID,$lessonID,$chachong)
    {
        $ids = HomeworkModel::getInstance()->getHomeworkIDsByLessonID($lessonID); // 得到该课程所有作业ID
        
        $data = 0;
        foreach($ids as $id){
            $temp = Homework_submitModel::getInstance()->getcopyNumByhomeworkIDanduserID($id,$userID, $chachong);
            $data += $temp[0]['copyNum'];
        }
        return $data;
    }
}