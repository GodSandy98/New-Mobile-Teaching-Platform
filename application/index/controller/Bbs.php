<?php
namespace app\index\controller;
use app\index\model\Lession_userModel;
use app\index\model\LessionModel;
use app\index\model\Bbs_questionModel;
use app\index\model\Bbs_answerModel;
use app\index\model\UserModel;



class Bbs extends Checkuser{

    private static $_instance;

    public static function getInstance()
    {
        if(!(self::$_instance instanceof self)){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    public function bbs_q()
    {
        $lessionID=(int)input('get.lessionID');
        if(empty($lessionID)) $this->redirect('index/index/index');
        
        $userID = session('userID');
        $con = ['userID'=>$userID,'lessionID'=>$lessionID];
        $res = Lession_userModel::getInstance()->getListByCon($con);
        if(empty($res)) $this->redirect('index/index/index');
        
        $lessionInfo = LessionModel::getInstance()->getListByCon(['id'=>$lessionID]);
        if(empty($lessionInfo)) $this->redirect('index/index/index');
        
        $lessionInfo = $lessionInfo[0];  //$lessionInfo是一个二维数组，虽然它里面只有一行数据，但也是二维数组，所以要用$lessionInfo[0]来指代这行数据。
        $lessionInfo['id'] = sprintf("%010d", $lessionInfo['id']);    //将课程号补成10位的
        //$this->assign('userID',$userID);
        $this->assign('lessionID',$lessionID);
        $this->assign('lessionInfo',$lessionInfo);
        if($lessionInfo['masterID'] === $userID){
            $this->assign('ifMaster','master');
        }else{
            $this->assign('ifMaster','');
        }
        $this->assign('usertype',session('usertype'));
        return $this->fetch('bbs_q');
    }
    public function bbs_input(){
        $lessionID=input('get.lessionID');
        if(empty($lessionID)) $this->redirect('index/index/index');
        
        $userID = session('userID');
        $con = ['userID'=>$userID,'lessionID'=>$lessionID];
        $res = Lession_userModel::getInstance()->getListByCon($con);
        if(empty($res)) $this->redirect('index/index/index');
        
        $lessionInfo = LessionModel::getInstance()->getListByCon(['id'=>$lessionID]);
        if(empty($lessionInfo)) $this->redirect('index/index/index');
        
        $lessionInfo = $lessionInfo[0];  //$lessionInfo是一个二维数组，虽然它里面只有一行数据，但也是二维数组，所以要用$lessionInfo[0]来指代这行数据。
        $lessionInfo['id'] = sprintf("%010d", $lessionInfo['id']);    //将课程号补成10位的
        //$this->assign('userID',$userID);
        $this->assign('lessionID',$lessionID);
        $this->assign('lessionInfo',$lessionInfo);
        if($lessionInfo['masterID'] === $userID){
            $this->assign('ifMaster','master');
        }else{
            $this->assign('ifMaster','');
        }
        $this->assign('usertype',session('usertype'));
        return $this->fetch('bbs_input');
    }
    public function question_detail(){
        $lessionID=input('get.lessionID');
        if(empty($lessionID)) $this->redirect('index/index/index');
        $id = input('get.id');
        $userID = session('userID');
        $con = ['userID'=>$userID,'lessionID'=>$lessionID];
        $res = Lession_userModel::getInstance()->getListByCon($con);
        if(empty($res)) $this->redirect('index/index/index');
    
        $lessionInfo = LessionModel::getInstance()->getListByCon(['id'=>$lessionID]);
        if(empty($lessionInfo)) $this->redirect('index/index/index');
    
        $lessionInfo = $lessionInfo[0];  //$lessionInfo是一个二维数组，虽然它里面只有一行数据，但也是二维数组，所以要用$lessionInfo[0]来指代这行数据。
        $lessionInfo['id'] = sprintf("%010d", $lessionInfo['id']);    //将课程号补成10位的
        //$this->assign('userID',$userID);
        $this->assign('id',$id);
        $this->assign('lessionID',$lessionID);
        $this->assign('lessionInfo',$lessionInfo);
        if($lessionInfo['masterID'] === $userID){
            $this->assign('ifMaster','master');
        }else{
            $this->assign('ifMaster','');
        }
        $this->assign('usertype',session('usertype'));
        return $this->fetch('question_detail');
    }
    
    public function searchByConditionAnswer($con){
        return Bbs_answerModel::getInstance()->getListByCon($con);
    }
    public function searchByConditionQuestion($con){
        return Bbs_questionModel::getInstance()->getListByCon($con);
    }
    public function searchByCondition_question($con,$limit=null,$field=null,$group=null){
        return Bbs_questionModel::getInstance()->getListByCon($con,$limit,$field,$group);
    }
    public function searchByCondition_answer($con,$limit=null,$field=null,$group=null){
        return Bbs_answerModel::getInstance()->getListByCon($con,$limit,$field,$group);
    }
    
    
    
    
    public function ajax_get_bbsquestion_input(){
        $lessionID=input('get.lessionID');   //注意，添加在url后面的方式发过来的数据在后端要用get方法接收！！！
        $userID=session('userID');
        $title=input('post.content1');
        $question=input('post.content2');
       if(!$title || !$question){
            $data['code']=1;
            return false;
        } 
        $res=Bbs_questionModel::getInstance()->insertOne($lessionID, $userID, $title, $question);
        $data = [];
        if($res==1){
            $data['code']=0;
            $data['data']='发表问题成功！';
            return $data;
        }
        else{
            $data['code']=$res;
            return $data;
        }
    }
    public function ajax_get_bbsanswer_input(){
        $questionID=input('get.questionID');   //注意，添加在url后面的方式发过来的数据在后端要用get方法接收！！！
        $userID=session('userID');
        
        $question=input('post.content1');
        if(!$question){
            $data['code']=1;
            return false;
        }
        $res=Bbs_answerModel::getInstance()->insertOne($userID, $questionID,$question);
        $data = [];
        if($res==1){
            $data['code']=0;
            $data['data']='回复成功！';
            return $data;
        }
        else{
            $data['code']=$res;
            return $data;
        }
    }
    public function ajax_get_bbsreanswer_input(){
        $questionID=input('get.questionID');   //注意，添加在url后面的方式发过来的数据在后端要用get方法接收！！！
        $userID=session('userID');
        $appointID = input('post.appointID');
        $question=input('post.content1');
        if(!$question){
            $data['code']=1;
            return false;
        }
        $res=Bbs_answerModel::getInstance()->insertOneNew($userID, $questionID,$question,$appointID);
        $data = [];
        if($res==1){
            $data['code']=0;
            $data['data']='回复成功！';
            return $data;
        }
        else{
            $data['code']=$res;
            return $data;
        }
    }
    

    public function ajax_getQuestionListByLessionID(){
        $lessionID = input('post.lessionID');
        $data = [];
        if(empty($lessionID)){
            $data['code'] = 1;
            $data['errmsg'] = '缺少课程ID';
        }
        $res = $this->getQuestionListByLessionID($lessionID);
         
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
    
    //这是给具体的每个用户问题界面使用的，在这个页面主要是列出用户所有的提问信息！
    public function ajax_getQuestionListByuserID(){
        $userID = session('userID');
        $data = [];
        if(empty($userID)){
            $data['code'] = 1;
            $data['errmsg'] = '缺少用户ID';
        }
        $res = $this->getQuestionListByuserID($userID);
        //var_dump($res); EXIT;
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
    
    //这是给具体问题界面使用的，因为具体界面只显示具体的一个问题，所以要根据ID来查询数据库！


    public function ajax_getQuestionListByID(){
        $id=input('post.id');
        $data = [];
        if(empty($id)){
            $data['code'] = 1;
            $data['errmsg'] = '缺少id';
        }
        $res = $this->getQuestionListByID($id);
         
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
    //根据不同的questionID来查找answer信息！
    public function ajax_getAnswerListByquestionID(){
        $questionID=input('post.questionID');
        $data = [];
        if(empty($questionID)){
            $data['code'] = 1;
            $data['errmsg'] = '缺少questionID';
        }
        $res = $this->getAnswerListByquestionID($questionID);
         
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
    //根据不同的userID来查找answer信息！
    public function ajax_getAnswerListByuserID(){
        $userID = session('userID');
        $data = [];
        if(empty($userID)){
            $data['code'] = 1;
            $data['errmsg'] = '缺少userID';
        }
        $res = $this->getAnswerListByuserID($userID);
         
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
    
    //根据不同的id来查找answer信息！
    public function ajax_getAnswerListByID(){
        $id=input('post.id');
        $data = [];
        if(empty($id)){
            $data['code'] = 1;
            $data['errmsg'] = '缺少id';
        }
        $res = $this->getAnswerListByID($id);
         
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
    
    //内部调用，根据课程ID获取用户列表
    //成功 返回array
    //失败 返回string
    public function getQuestionListByLessionID($lessionID){
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
        $res1 = Bbs_questionModel::getInstance()->getListByCon($con1);
        if(!is_array($res1)){
            return $res1;     //如果不是数组，说明查找出错了！
        }
        if(empty($res1)){
            return $res1;
        }
        if(is_array($res1)){
            $a = array();
            for($i=0;$i<count($res1);$i++){
                array_push($a, $res1[$i]['userID']);
            }
            $con = ['ID'=>array('op'=>'in','val'=>$a)];
            $user = UserModel::getInstance()->getListByCon($con);
            for($i=0;$i<count($res1);$i++){
                for($j=0;$j<count($user);$j++){
                    if($res1[$i]['userID'] === $user[$j]['ID']){
                        $res1[$i]['name'] = $user[$j]['name'];
                    }
                }
            }
            return $res1;
        }
    }
    
    //内部调用，根据用户ID获取用户提问列表
    //成功 返回array
    //失败 返回string
    public function getQuestionListByuserID($userID){
        if(empty($userID)) return '缺少用户ID';
        
        $con1 = ['userID'=>$userID];
        $res1 = Bbs_questionModel::getInstance()->getListByCon($con1);
        if(!is_array($res1)){
            return $res1;     //如果不是数组，说明查找出错了！
        }
        if(empty($res1)){
            return '此用户没有发表问题！';
        }
        if(is_array($res1)){
        $user = UserModel::getInstance()->getListByCon(['ID'=>$userID]);
        for($i=0;$i<count($res1);$i++){
              if($res1[$i]['userID'] === $user[0]['ID']){
                  $res1[$i]['name'] = $user[0]['name'];
                 }
          }
          $t = array();
          for($i=0; $i<(count($res1)/2); $i++){
              $t = $res1[$i];
              $res1[$i] = $res1[count($res1)-$i-1];
              $res1[count($res1)-$i-1] = $t;
          }
          return $res1;
            return $res1;
        }
    }
    public function getQuestionListByID($id){
        if(empty($id)) return '缺少id';
        
        $con1 = ['id'=>$id];
        $res1 = Bbs_questionModel::getInstance()->getListByCon($con1);
        if(!is_array($res1)){
            return $res1;     //如果不是数组，说明查找出错了！
        }
        if(empty($res1)){
            return '没有用户留言讨论！';
        }
        if(is_array($res1)){
            $a = array();
            for($i=0;$i<count($res1);$i++){
                array_push($a, $res1[$i]['userID']);
            }
            $con = ['ID'=>array('op'=>'in','val'=>$a)];
            $user = UserModel::getInstance()->getListByCon($con);
            for($i=0;$i<count($res1);$i++){
                for($j=0;$j<count($user);$j++){
                    if($res1[$i]['userID'] === $user[$j]['ID']){
                        $res1[$i]['name'] = $user[$j]['name'];
                    }
                }
            }
            $t = array();
            for($i=0; $i<(count($res1)/2); $i++){
                $t = $res1[$i];
                $res1[$i] = $res1[count($res1)-$i-1];
                $res1[count($res1)-$i-1] = $t;
            }
            return $res1;
        }
    }
    public function getAnswerListByquestionID($questionID){
        if(empty($questionID)) return '缺少questionID';
    
        $con1 = ['questionID'=>$questionID];
        $res1 = Bbs_answerModel::getInstance()->getListByCon($con1);
        if(!is_array($res1)){
            return $res1;     //如果不是数组，说明查找出错了！
        }
        if(empty($res1)){
            return '没有用户回复！';
        }
        if(is_array($res1)){
            $a = array();
            for($i=0;$i<count($res1);$i++){
                array_push($a, $res1[$i]['userID']);
            }
            $con = ['ID'=>array('op'=>'in','val'=>$a)];
            $user = UserModel::getInstance()->getListByCon($con);
            for($i=0;$i<count($res1);$i++){
                for($j=0;$j<count($user);$j++){
                    if($res1[$i]['userID'] === $user[$j]['ID']){
                        $res1[$i]['name'] = $user[$j]['name'];
                    }
                }
            }
            $t = array();
            for($i=0; $i<(count($res1)/2); $i++){
                $t = $res1[$i];
                $res1[$i] = $res1[count($res1)-$i-1];
                $res1[count($res1)-$i-1] = $t;
            }
            return $res1;
        }
    }
    
    public function getAnswerListByuserID($userID){
        if(empty($userID)) return '缺少userID';
    
        $con1 = ['userID'=>$userID];
        $res1 = Bbs_answerModel::getInstance()->getListByCon($con1);
        if(!is_array($res1)){
            return $res1;     //如果不是数组，说明查找出错了！
        }
        if(empty($res1)){
            return '您尚未发表过回复信息！';
        }
        if(is_array($res1)){
            $user = UserModel::getInstance()->getListByCon(['ID'=>$userID]);
            for($i=0;$i<count($res1);$i++){
                    if($res1[$i]['userID'] === $user[0]['ID']){
                        $res1[$i]['name'] = $user[0]['name'];
                    }
            }
            $t = array();
            for($i=0; $i<(count($res1)/2); $i++){
                $t = $res1[$i];
                $res1[$i] = $res1[count($res1)-$i-1];
                $res1[count($res1)-$i-1] = $t;
            }
            return $res1;
        }
    }
    
    public function getAnswerListByID($id){
        if(empty($id)) return '缺少id';
    
        $con1 = ['id'=>$id];
        $res1 = Bbs_answerModel::getInstance()->getListByCon($con1);
        if(!is_array($res1)){
            return $res1;     //如果不是数组，说明查找出错了！
        }
        if(empty($res1)){
            return '没有用户回复！';
        }
        if(is_array($res1)){
            $t = array();
            for($i=0; $i<(count($res1)/2); $i++){
                $t = $res1[$i];
                $res1[$i] = $res1[count($res1)-$i-1];
                $res1[count($res1)-$i-1] = $t;
            }
            return $res1;

        }
    }
    
}