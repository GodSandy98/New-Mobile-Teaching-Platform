<?php
namespace app\index\controller;

use app\index\model\Lession_applyModel;
use app\index\model\Lession_userModel;
use app\index\model\LessionModel;
use app\index\model\UserModel;

class Lession extends Checkuser{
    
    private static $_instance;
    
    public static function getInstance()
    {
        if(!(self::$_instance instanceof self)){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    //课程页
    public function index()
    {
        $lessionID = (int)(input('get.id'));
        if(empty($lessionID)) $this->redirect('index/index/index');
        
        $userID = session('userID');
        $con = ['userID'=>$userID,'lessionID'=>$lessionID];
        $res = Lession_userModel::getInstance()->getListByCon($con);
        if(empty($res)) $this->redirect('index/index/index');
        
        $lessionInfo = LessionModel::getInstance()->getListByCon(['id'=>$lessionID]);
        if(empty($lessionInfo)) $this->redirect('index/index/index');
        
        $lessionInfo = $lessionInfo[0];
        $lessionInfo['id'] = sprintf("%010d", $lessionInfo['id']);
        //$this->assign('userID',$userID);
        $this->assign('lessionID',$lessionID);
        $this->assign('lessionInfo',$lessionInfo);
        if($lessionInfo['masterID'] === $userID){
            $this->assign('ifMaster','master');
        }else{
            $this->assign('ifMaster','');
        }
        $this->assign('usertype',session('usertype'));
        return $this->fetch('index');
    }
    
    public function bbs_q(){
        $this->assign('userID',session('userID'));
        return $this->fetch('bbs_q');
    }
    
    //返回特定用户ID的课程
    public function ajax_get_index_lession(){
        $userID = session('userID');
        if(empty($userID)) return false;
        $lessions = $this->getLessionsByUserID($userID);  //$lession中是当前用户已选课程的全部信息。
        $data = array();
        foreach($lessions as $v){
            $p = array();
            $p['name'] = $v['lessionName'];
            $p['url'] = __URL__.'/index/Lession/index?id='.$v['id'];    //php中的连接操作用'.'来表示，html中用'+'来表示
            $p['infoNum'] = 0;
            $p['id'] = sprintf("%010d", $v['id']);   //将从数据库中读取的课程号前面补上9个零，让其变成总长度为十位的数，以控制格式。
            array_push($data,$p);
        }
        $ans = [];
        $ans['code'] = 0;
        $ans['data'] = $data;
        return $ans;
    }
    
    //查看某用户是否有某课程
    public function ajax_checkIfUserInLession(){
        $userID = input('post.userID');
        $lessionID = input('post.lessionID');
        $data = [];
        if(empty($userID) || empty($lessionID)){
            $data['code'] = 1;
            $data['errmsg'] = '参数不足';
            return $data;
        }
        $data['code'] = 0;
        $res = $this->checkIfUserInLession($userID,$lessionID);
        if($res === true || $res === false){
            $data['data'] = $res;
        }else{
            $data['code'] = 2;
            $data['errmsg'] = $res;
        }
        return $data;
    }
    public function ajax_checkUserInLession_Apply(){
        $lessionID=input('post.lessionID');
        $result1 = Lession_applyModel::getInstance()->getListByCon(['lessionID'=>$lessionID]);
        if(!empty($result1)){
            $data['code'] = 0;
            $u = array();
            for($i=0;$i<count($result1);$i++){
                array_push($u, $result1[$i]['userID']);
            }
            $con = ['ID'=>array('op'=>'in','val'=>$u)];
            $user = UserModel::getInstance()->getListByCon($con);
            $data['data']=$user;
            return $data;
        }else{
            $data['code'] =1;
            $data['data'] = $result1;
            return $data;
        }
    }
    //学生主动添加课程
    public function ajax_addLession(){
        $userID = session('userID');
        $lessionID = input('post.lessionID');
        $data = [];
        $data['code'] = 0;
        $result = $this->checkIfUserInLession($userID,$lessionID);
        if($result===true){
            $data['code']=3;
            $data['errmsg']='您已选此课程！';
            return $data;
        }
        $array=array("userID"=>$userID, "lessionID"=>$lessionID);
        $result1 = Lession_applyModel::getInstance()->getListByCon($array);
        if(!empty($result1)){
            $data['code']=4;
            $data['errmsg']='您申请加入此课程，无需再次申请！';
            return $data;
        }
        $lessions = LessionModel::getInstance()->getListByCon(['id'=>$lessionID]);
        if(empty($lessions)){
            $data['code'] = 1;
            $data['errmsg'] = '无此课程';
            return $data;
        }
        $inlession = Lession::getInstance()->checkIfUserInLession($userID, $lessionID);
        if($inlession===true){
            $data['code'] = 3;
            $data['errmsg'] = '已在班级中';
            return $data;
        }
        $inapply = Lession_applyModel::getInstance()->getListByCon(['userID'=>$userID,'lessionID'=>$lessionID]);
        if(!empty($inapply)){
            $data['code'] = 3;
            $data['errmsg'] = '已申请，请耐心等待';
            return $data;
        }

        $res = Lession_applyModel::getInstance()->insertOne($lessionID,$userID);
        if($res === 1){
            $data['data'] = '申请发送成功，请等待管理员确认！';
        }else{
            $data['code'] = 2;
            $data['errmsg'] = $res;
        }
        return $data;
    }
    
    //通过学生的加入课程申请并将学生加入课程
    public function ajax_addUserToLession_Apply(){
        $userID = input('post.userID');
        $lessionID = input('post.lessionID');
        $data = [];
        
        if(empty($lessionID) || empty($userID)){
            $data['code'] = 7;
            $data['errmsg'] = '缺少必要参数';
            return $data;
        }
        
        $lessions = LessionModel::getInstance()->getListByCon(['id'=>$lessionID]);
        if(empty($lessions)){
            $data['code'] = 3;
            $data['errmsg'] = '无此课程';
            return $data;
        }
        if($lessions[0]['masterID'] != session('userID')){
            $data['code'] = 4;
            $data['errmsg'] = '无此权限';
            return $data;
        }
        
        $data['code'] = 0;
        $res = Lession_userModel::getInstance()->insertOne($lessionID,$userID);
        if($res === 1){
            $res1=Lession_applyModel::getInstance()->deleteOne($lessionID,$userID);
            if($res1!==null)
            {
                $data['data'] = '添加成功';
            }
            else{
                $data['code'] = 3;
                $data['errmsg'] = $res1;
            }
        }else{
            $data['code'] = 2;
            $data['errmsg'] = $res;
        }
        return $data;
    }
    
    //将某学生加入到某课程中
    public function ajax_addUserToLession(){
        $userID = input('post.userID');
        $lessionID = input('post.lessionID');
        $data = [];
        if(empty($userID) || empty($lessionID)){
            $data['code'] = 1;
            $data['errmsg'] = '参数不足';
            return $data;
        }
        $lessions = LessionModel::getInstance()->getListByCon(['id'=>$lessionID]);
        if(empty($lessions)){
            $data['code'] = 3;
            $data['errmsg'] = '无此课程';
            return $data;
        }
        if($lessions[0]['masterID'] != session('userID')){
            $data['code'] = 4;
            $data['errmsg'] = '无此权限';
            return $data;
        }
        $data['code'] = 0;
        $res = Lession_userModel::getInstance()->insertOne($lessionID,$userID);
        if($res === 1){
            $res2 = Lession_applyModel::getInstance()->deleteOne($lessionID, $userID);
            $data['code'] = 0;
            $data['data'] = '添加成功';
            return $data;
        }else{
            $data['code'] = 2;
            $data['errmsg'] = $res;
        }
        return $data;
    }
    
    //根据课程ID获取用户列表
    public function ajax_getUserListByLessionID(){ 
        $lessionID = input('post.lessionID');        
        $data = [];
        if(empty($lessionID)){
            $data['code'] = 1;
            $data['errmsg'] = '缺少课程ID';
        }            
        if(($this->checkIfUserInLession(session('userID'), $lessionID)) !== true){
            $data['code'] = 1032;
            $data['errmsg'] = '您不在此课程中';
            return $data;
        }       
        $res = $this->getUserListByLessionID($lessionID);   
        if(is_array($res)){
            $data['code'] = 0;
            $data['data'] = $res;
            return $data;
        }else{
            $data['code'] = 2;
            $data['errmsg'] = $res;
            return $data;
        }
    }
    public function ajax_getNogroupUserListByLessionID(){
        $lessionID = input('post.lessionID');
        $data = [];
        if(empty($lessionID)){
            $data['code'] = 1;
            $data['errmsg'] = '缺少课程ID';
        }
        if(($this->checkIfUserInLession(session('userID'), $lessionID)) !== true){
            $data['code'] = 1032;
            $data['errmsg'] = '您不在此课程中';
            return $data;
        }
        $res = $this->getNogroupUserListByLessionID($lessionID);
        if(is_array($res)){
            $data['code'] = 0;
            $data['data'] = $res;
            return $data;
        }else{
            $data['code'] = 2;
            $data['errmsg'] = $res;
            return $data;
        }
    }
    //根据课程ID获取申请用户列表
    public function ajax_getApplyListByLessionID(){
        $lessionID = input('post.lessionID');      
        $data = [];
        if(empty($lessionID)){
            $data['code'] = 1;
            $data['errmsg'] = '缺少课程ID';
        }   
        $u = array();
        $res = Lession_applyModel::getInstance()->getListByCon(['lessionID'=>$lessionID]);
        if(empty($res)){
            $data['code'] = 0;
            $data['data'] = [];
            return $data;
        }
        for($i=0;$i<count($res);$i++){
            array_push($u, $res[$i]['userID']);            
        }             
        $con = ['ID'=>array('op'=>'in','val'=>$u)];       
        $user = UserModel::getInstance()->getListByCon($con);
        //return var_dump($user);
        if(is_array($user)){
            $data['code'] = 0;
            $data['data'] = $user;
            return $data;
        }else{
            $data['code'] = 2;
            $data['errmsg'] = $user;
            return $data;
        }
    }
    //处理申请后，删除申请表中对应的数据
    public function ajax_deleteLession_apply(){
        $userID = input('post.userID');
        $lessionID = input('post.lessionID');
        $data = [];
        $res = Lession_applyModel::getInstance()->deleteOne($lessionID, $userID);
        if(!empty($res)){
            $data['code'] = 0;
            return $data;
        }else{
            $data['code'] = 1;
            $data['errmsg'] = '没有该条申请记录';
            return $data; 
        }
    }
    //这里是退出课程botton处的使用的方法，将用户从Lession_user表中删除
    public function ajax_deleteLession_user1(){
        $userID = session('userID');   //userID是当前登录的用户，尽量放在后台处理，如果放在前台，那么可能会被攻击。
        $lessionID = input('post.lessionID');
        $data = [];
        $res = Lession_userModel::getInstance()->deleteOne($lessionID, $userID);
        if(!empty($res)){
            $data['code'] = 0;
            return $data;
        }else{
            $data['code'] = 1;
            $data['errmsg'] = '什么事情也没做！';
            return $data;
        }
    }
    
    public function ajax_rewriteLessionName(){
        $NewCourseName = input('post.NewCourseName');
        $CourseID = input('post.CourseID');
        $res = LessionModel::getInstance()->rewriteLessionName($NewCourseName,$CourseID);
        $data = [];
        if($res===1){
            $data['code'] = 0;
            $data['data'] = '修改成功！';
        }else{
            $data['code'] = 1;
            $data['errmsg'] = $res;
        }
        return $data;
    }
    
    //获取资源分享列表S
    public function ajax_getShareFileList(){
        $lessionID = input('post.lessionID');
        $limit = input('post.limit');
        $search = input('post.searchName');
        
        $data = [];
        if(empty($lessionID)){
            $data['code'] = 1;
            $data['errmsg'] = '缺少必要参数';
            return $data;
        }
        
        if(($this->checkIfUserInLession(session('userID'), $lessionID)) !== true){
            $data['code'] = 1032;
            $data['errmsg'] = '您不在此课程中';
            return $data;
        }
       
        $shareFileList = Sharefile::getInstance()->getShareFileList($lessionID,$limit,$search);
        
        if(is_array($shareFileList)){
            $data['code'] = 0;
            $data['data'] = $shareFileList;
            return $data;
        }else{
            $data['code'] = 2;
            $data['errmsg'] = $shareFileList;
            return $data;
        }
    }
    
    //获取作业列表
    public function ajax_getHomeworkList(){
        $userID = session('userID');
        $lessionID = input('post.lessionID');
        $limit = input('post.limit');
    
        $data = [];
        if(empty($lessionID)){
            $data['code'] = 1;
            $data['errmsg'] = '缺少必要参数';
            return $data;
        }
        
        if(($this->checkIfUserInLession(session('userID'), $lessionID)) !== true){
            $data['code'] = 1032;
            $data['errmsg'] = '您不在此课程中';
            return $data;
        }
        
        $homeworkList = Homework::getInstance()->getHomeworkList($lessionID,$limit); 
    
        if(is_array($homeworkList)){
            foreach ($homeworkList as $k => $v){
                $homeworkList[$k]['file_type'] = Index::getInstance()->getFileType($v['filename']);
                $homeworkList[$k]['StudentFileID'] = Homework::getInstance()->getFileIDByUserIDandHomeworkID($userID, $homeworkList[$k]['id']);
            }
            $data['code'] = 0;
            $data['data'] = $homeworkList;
            return $data;
        }else{
            $data['code'] = 2;
            $data['errmsg'] = $homeworkList;
            return $data;
        }
    }
    
    
    //内部调用，根据课程ID获取用户列表
    //成功 返回array
    //失败 返回string
    public function getUserListByLessionID($lessionID,$ifCheckSubmit = false){
        if(empty($lessionID)) return '缺少课程ID';        
        $con3 = ['id'=>$lessionID];
        $res3 = LessionModel::getInstance()->getListByCon($con3);
        if(!is_array($res3)){
            return $res3;    //如果不是数组，说明查找出错了！如果查询没出错的话，一定会返回一个数组，即使返回的是空数组！
        }
        if(empty($res3)){
            return '无此课程';    //如果是空，说明没有这门课程！
        }
        //上面这个if用来判断输入的lessionID是否正确！
        $con1 = ['lessionID'=>$lessionID];
        //$res1里面存储了所有选修了这门课的学生
        $res1 = Lession_userModel::getInstance()->getListByCon($con1);
        if(!is_array($res1)){
            return $res1;     //如果不是数组，说明查找出错了！
        }
        if(empty($res1)){
            return '此课程没有用户';
        }        
        $arr = array();
        $user_lession = array();
        foreach ($res1 as $v){
            $user_lession[$v['userID']] = $v;
            if($ifCheckSubmit && $v['authority_upload'] !== 1){
                continue;
            }
            array_push($arr,$v['userID']);   //将userID取出放入数组$arr中
        }       
        $con2 = ['ID'=>array('op'=>'in','val'=>$arr)];
        $res2 = UserModel::getInstance()->getListByCon($con2);
        if(!is_array($res2)){
            return $res2;       //如果不是数组，说明查找出错了！
        }    
        $masterID = $res3[0]['masterID'];
        foreach($res2 as $k => $v){
            $res2[$k]['authority_upload'] = isset($user_lession[$v['ID']])?$user_lession[$v['ID']]['authority_upload']:0;
            $res2[$k]['group'] = $user_lession[$v['ID']]['group'];//将group加入arr。
            $res2[$k]['IsHeadman'] = $user_lession[$v['ID']]['IsHeadman'];
            if($v['ID'] === $masterID){
                $res2[$k]['type'] = 'master';
            }
        }        
        usort($res2,function($a,$b){     //快速排序，$a,$b是$res2这个二维数组中的任意位置的两个一维数组
            $x = 0;
            $y = 0;
            switch ($a['type']){
                case 'master': $x = 3;break;
                case 'teacher': $x = 2;break;
                case 'student': $x = 1;break;
            }
            switch ($b['type']){
                case 'master': $y = 3;break;
                case 'teacher': $y = 2;break;
                case 'student': $y = 1;break;
            }
            $z = $y-$x;          //根据$y-$x值得正负来判断那个应该排在前面
            if($z != 0) return $z;
            else return $a['studentID'] - $b['studentID'];
        });
        return $res2;
    }
    
    //判断用户课程记录是否存在
    public function checkIfUserInLession($userID,$lessionID){
        if(empty($userID)) return '缺少用户ID';
        if(empty($lessionID)) return '缺少课程ID';
        $con = ['userID'=>$userID,'lessionID'=>$lessionID];
        if(empty(Lession_userModel::getInstance()->getListByCon($con))){
            return false;
        }else{
            return true;
        }
    }
    
    //判断用户课程记录是否有权限上传作业
    public function checkIfUserHasAuthorityToSubmit($userID,$lessionID){
        if(empty($userID)) return '缺少用户ID';
        if(empty($lessionID)) return '缺少课程ID';
        $con = ['userID'=>$userID,'lessionID'=>$lessionID];
        $res = Lession_userModel::getInstance()->getListByCon($con);
        if(empty($res)){
            return false;
        }else{
            if($res[0]['authority_upload'] === 1){
                return true;
            }else{
                return false;
            }
        }
    }
    
    //内部调用，返回userID的课程
    public function getLessionsByUserID($ID){
        if(empty($ID)){
            return false;
        }
        $con1 = array();
        $con1['userID'] = $ID;
        $le_u = Lession_userModel::getInstance()->getListByCon($con1);  //$le_u里面存储了当前登录用户所有已选的课程。
        if(empty($le_u)) return [];
        $con2 = array();
        $lessionIds = array();
        foreach ($le_u as $v){
            array_push($lessionIds,$v['lessionID']);
        }
        $con2['id'] = array(
            'op' => 'in',
            'val' => $lessionIds
        );
        $leList = LessionModel::getInstance()->getListByCon($con2);  //$leList里面存储了当前用户已选的课程的所有信息。
        return $leList;
    }
    
    //内部调用，创建课程
    public function createLession($userID,$lessionName){
        if(empty($userID)){
            return '用户ID为空';
        }else if(empty($lessionName)){
            return '课程名称为空';
        }
        $res = LessionModel::getInstance()->createLession($userID,$lessionName);
        return $res;
    }
    
    public function changeLession_UserModel($authority_upload,$lessionID,$userID){
        $condition = [];
        $condition['authority_upload'] = $authority_upload;
        return Lession_userModel::getInstance()->updateOneRecord($condition, $lessionID, $userID);
    }
    public function changeLession_UserModelgroup($group,$lessionID,$userID){
        $condition = [];
        $condition['group'] = $group;
        return Lession_userModel::getInstance()->updateOneRecord($condition, $lessionID, $userID);
    }
    public function changeLession_UserModelIsHeadman($headman,$lessionID,$userID){
        $condition = [];
        $condition['IsHeadman'] = $headman;
        return Lession_userModel::getInstance()->updateOneRecord($condition, $lessionID, $userID);
    }
    public function searchFromLessionModel($condition){
        return LessionModel::getInstance()->getListByCon($condition);
    }
    
    public function deleteFromLessionModel($lessionID, $userID){
        return Lession_userModel::getInstance()->deleteOne($lessionID, $userID);
    }
    
    //内部调用，根据课程ID获取未分组用户列表
    //成功 返回array
    //失败 返回string
    public function getNogroupUserListByLessionID($lessionID,$ifCheckSubmit = false){
     if(empty($lessionID)) return '缺少课程ID';        
        $con3 = ['id'=>$lessionID];
        $res3 = LessionModel::getInstance()->getListByCon($con3);
        if(!is_array($res3)){
            return $res3;    //如果不是数组，说明查找出错了！如果查询没出错的话，一定会返回一个数组，即使返回的是空数组！
        }
        if(empty($res3)){
            return '无此课程';    //如果是空，说明没有这门课程！
        }
        //上面这个if用来判断输入的lessionID是否正确！
        $arr0=array();
        array_push($arr0,"");
        array_push($arr0,"0");
        array_push($arr0,null);
        $con1 = ['lessionID'=>$lessionID,'group'=>array('op'=>'in','val'=>$arr0)];
        //$res1里面存储了所有选修了这门课的学生
        $res1 = Lession_userModel::getInstance()->getListByCon($con1);
        if(!is_array($res1)){
            return $res1;     //如果不是数组，说明查找出错了！
        }
        if(empty($res1)){
            return '此课程没有用户';
        }        
        $arr = array();
        $user_lession = array();
        foreach ($res1 as $v){
            $user_lession[$v['userID']] = $v;
            if($ifCheckSubmit && $v['authority_upload'] !== 1){
                continue;
            }
            array_push($arr,$v['userID']);   //将userID取出放入数组$arr中
        }       
        $con2 = ['ID'=>array('op'=>'in','val'=>$arr)];
        $res2 = UserModel::getInstance()->getListByCon($con2);
        if(!is_array($res2)){
            return $res2;       //如果不是数组，说明查找出错了！
        }    
        $masterID = $res3[0]['masterID'];
        foreach($res2 as $k => $v){
            $res2[$k]['authority_upload'] = isset($user_lession[$v['ID']])?$user_lession[$v['ID']]['authority_upload']:0;
            $res2[$k]['group'] = $user_lession[$v['ID']]['group'];//将group加入arr。
            $res2[$k]['IsHeadman'] = $user_lession[$v['ID']]['IsHeadman'];
            if($v['ID'] === $masterID){
                $res2[$k]['type'] = 'master';
            }
        }        
        usort($res2,function($a,$b){     //快速排序，$a,$b是$res2这个二维数组中的任意位置的两个一维数组
            $x = 0;
            $y = 0;
            switch ($a['type']){
                case 'master': $x = 3;break;
                case 'teacher': $x = 2;break;
                case 'student': $x = 1;break;
            }
            switch ($b['type']){
                case 'master': $y = 3;break;
                case 'teacher': $y = 2;break;
                case 'student': $y = 1;break;
            }
            $z = $y-$x;          //根据$y-$x值得正负来判断那个应该排在前面
            if($z != 0) return $z;
            else return $a['studentID'] - $b['studentID'];
        });
        return $res2;
    }
    
}