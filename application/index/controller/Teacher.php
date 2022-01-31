<?php
namespace app\index\controller;

use think\Session;
use think\Console;
use phpDocumentor\Reflection\Types\This;
use app\index\model\UserModel;
use think\Log;
use app\index\model\Homework_submitModel;
use app\index\controller\MOSS;

class Teacher extends Checkuser{
    
    public function _initialize(){   //构造方法里面检测Session
        parent::_initialize();
        if(!Session::has('usertype')){
            $this->redirect('index/Login/index');      //重定向到登录界面    “模块/控制器中的.php文件/方法”
        }else if(session('usertype') !== 'teacher'){
            $this->error('权限不足');
            exit;
        }
    }

    private static $_instance;
    //public $filename;
    public static  $words = array("auto","break","case","char","const","continue","default","do","double","else","enum","extern","float","for","goto","if","int","long","register","return","short","signed","sizeof","static","struct","switch","typedef","printf"
        );
    //"auto","break","case","char","const","continue","default","do","double","else","enum","extern","float","for","goto","if","int","long","register","return","short","signed","sizeof","static","struct","switch","typedef","printf"
    

    public static function getInstance()
    {
        if(!(self::$_instance instanceof self)){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function ajax_createLession(){
        $lessionName = input('post.lessionName');
        $data = array();
        $data['code'] = 0;
        if(empty($lessionName)){
            $data['code'] = 1;
            $data['errmsg'] = '课程名称为空';
            return $data;
        }
        $userID = session('userID');
        $res = Lession::getInstance()->createLession($userID,$lessionName);
        if($res === true){
            $data['data'] = '创建成功';
        }else{
            $data['code'] = 2;
            $data['errmsg'] = $res;
        }
        return $data;
    }
    
    //分享文件
    public function ajax_ShareFile(){
        $file = request()->file('file');
        $filename = input('post.name');
        $total = input('post.total');
        $index = input('post.index');
        $md5 = input('post.md5');
        $lessionID = input('post.lessionID');
        $userID = session('userID');
        
        if((Lession::getInstance()->checkIfUserInLession($userID, $lessionID)) !== true){
            $data['code'] = 1032;
            $data['errmsg'] = '您不在此课程中';
            return $data;
        }
        
        return Sharefile::getInstance()->shareFile($file,$filename,$total,$index,$md5,$lessionID);
    }
    
    public function ajax_checkFile(){
        $md5 = input('post.md5');
        $username = session('username');
        $total = input('post.total');
        $lessionID = input('post.lessionID');
        $filename = input('post.filename');
        $userID = session('userID');
        
        if((Lession::getInstance()->checkIfUserInLession($userID, $lessionID)) !== true){
            $data['code'] = 1032;
            $data['errmsg'] = '您不在此课程中';
            return $data;
        }
        
        return Sharefile::getInstance()->checkFile($md5,$username,$total,$lessionID,$filename);
    }
    
    //没有文件的作业
    public function ajax_setHomework_withoutfile(){
        $title = input('post.title');
        $starttime = input('post.starttime');
        $endtime = input('post.endtime');
        $lessionID = input('post.lessionID');
        $userID = session('userID');
        $content = input('post.content');
        $test_case = input('post.test_case');
        $correct_result = input('post.correct_result');
        $test_case1 = input('post.test_case1');
        $correct_result1 = input('post.correct_result1');
        
        $data = [];
        
        if(empty($title) || empty($starttime) || empty($endtime) || empty($lessionID) || empty($userID) || empty($test_case) || empty($correct_result) || empty($test_case1) || empty($correct_result1)){
            $data['code'] = 1;
            $data['errmsg'] = '缺少必要参数';
            return $data;
        }
        
        if((Lession::getInstance()->checkIfUserInLession($userID, $lessionID)) !== true){
            $data['code'] = 2;
            $data['errmsg'] = '您不在此课程中';
            return $data;
        }
        
        $con = [];
        $con['userID'] = $userID;
        $con['lessionID'] = $lessionID;
        $con['title'] = $title;
        $con['starttime'] = $starttime;
        $con['endtime'] = $endtime;
        $con['content'] = $content;
        $con['test_case'] = $test_case;
        $con['correct_result'] = $correct_result;
        $con['test_case1'] = $test_case1;
        $con['correct_result1'] = $correct_result1;
        
        $res = Homework::getInstance()->addOneHomework($con);
        
        if($res === true){
            $data['code'] = 0;
            $data['data'] = '创建作业成功';
            return $data;
        }else{
            $data['code'] = 3;
            $data['errmsg'] = $res;
            return $data;
        }
    }
    
    //确认作业文件是否存在
    public function ajax_checkFile_homework(){
        $title = input('post.title');
        $starttime = input('post.starttime');
        $endtime = input('post.endtime');
        $lessionID = input('post.lessionID');
        $userID = session('userID');
        $content = input('post.content');
        $total = input('post.total');
        $filename = input('post.filename');
        $md5 = input('post.md5');
        
        $data = [];
        
        if(empty($title) || empty($starttime) || empty($endtime) || empty($lessionID) || empty($userID) || empty($md5) || empty($filename) || empty($total)){
            $data['code'] = 1011;
            $data['errmsg'] = '缺少必要参数';
            return $data;
        }
        
        if((Lession::getInstance()->checkIfUserInLession($userID, $lessionID)) !== true){
            $data['code'] = 1012;
            $data['errmsg'] = '您不在此课程中';
            return $data;
        }
        
        $con = [];
        $con['userID'] = $userID;
        $con['lessionID'] = $lessionID;
        $con['title'] = $title;
        $con['starttime'] = $starttime;
        $con['endtime'] = $endtime;
        $con['content'] = $content;
        $con['filename'] = $filename;
        $con['file_MD5'] = $md5;
        
        try{
            $res = Homework::getInstance()->checkIfHomeworkExist($con,$total,session('username'),Homework::getInstance(),'addOneHomework');
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
    
    public function ajax_setHomework_withfile(){
        $file = request()->file('file');
        $title = input('post.title');
        $starttime = input('post.starttime');
        $endtime = input('post.endtime');
        $lessionID = input('post.lessionID');
        $userID = session('userID');
        $content = input('post.content');
        $total = input('post.total');
        $filename = input('post.filename');
        $md5 = input('post.md5');
        $index = input('post.index');
        
        $data = [];
        
        if(empty($title) || empty($starttime) || empty($endtime) || empty($lessionID) || empty($userID) || empty($md5) || empty($filename) || empty($index) || empty($file) || empty($total)){
            $data['code'] = 1011;
            $data['errmsg'] = '缺少必要参数';
            return $data;
        }
        
        if((Lession::getInstance()->checkIfUserInLession($userID, $lessionID)) !== true){
            $data['code'] = 1012;
            $data['errmsg'] = '您不在此课程中';
            return $data;
        }
        
        $con = [];
        $con['userID'] = $userID;
        $con['lessionID'] = $lessionID;
        $con['title'] = $title;
        $con['starttime'] = $starttime;
        $con['endtime'] = $endtime;
        $con['content'] = $content;
        $con['filename'] = $filename;
        $con['file_MD5'] = $md5;
        
        try{
            $res = Homework::getInstance()->uploadHomeworkwithFile($con,$file,$total,$index,$md5,session('username'),Homework::getInstance(),'addOneHomework');
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
    
    public function ajax_getHomeworkSubmitList(){
        $homeworkID = input('post.homeworkID');
        $userID = session('userID');
        
        $data = [];
        
        if(empty($homeworkID) || empty($userID)){
            $data['code'] = 1023;
            $data['errmsg'] = '缺少必要参数';
            return $data;
        }
        
        $homework = Homework::getInstance()->searchByCondition(['id'=>$homeworkID]);
        
        if(empty($homework)){
            $data['code'] = 1232;
            $data['errmsg'] = '没有此项作业';
            return $data;
        }
        
        $lessionID = $homework[0]['lessionID'];
        
        if(Lession::getInstance()->checkIfUserInLession($userID, $lessionID) !== true){
            $data['code'] = 1234;
            $data['errmsg'] = '您不在此课程中';
            return $data;
        }
        
        $submitList = Homework::getInstance()->searchByCondition_homework_submit(['homeworkID' => $homeworkID]);
        
        if(!is_array($submitList)){
            $data['code'] = 1024;
            $data['errmsg'] = $submitList;
            return $data;
        }
        
        if(empty($submitList)){
            $data['code'] = 0;
            $data['data'] = $submitList;
            return $data;
        }
        
        $userIDs = [];
        foreach ($submitList as $v){
            array_push($userIDs,$v['userID']);
        }
        $con = [];
        $con['ID'] = ['op' => 'in','val'=>$userIDs];
        
        $userList = User::getInstance()->searchByCondition($con);
        if(!is_array($userList)){
            $data['code'] = 1054;
            $data['errmsg'] = $userList;
            return $data;
        }
        
        $userListHash = [];
        foreach ($userList as $v){
            $userListHash[$v['ID']] = $v;
        }
        
        foreach ($submitList as $k => $v){
            $submitList[$k]['username'] = $userListHash[$v['userID']]['name'];
            $submitList[$k]['studentID'] = $userListHash[$v['userID']]['studentID'];
            $submitList[$k]['file_type'] = Index::getInstance()->getFileType($v['filename']);
        }
        
        $data['code'] = 0;
        $data['data'] = $submitList;
        return $data;
        
    }
   
    public function ajax_chachong(){
        //获得作业list
        $homeworkID = input('post.homeworkID');   //当前作业编号
        $chachong_rate = input('post.chachong_rate'); //获得老师设定的查重率
        $data = [];
        $data['code'] = 0;
        if(empty($homeworkID)){
            $data['code'] = 1023;
            $data['errmsg'] = '缺少必要参数';
            return $data;
        }

        // 将该作业所有提交文档查重率为0
        $res = Homework_submitModel::getInstance()->setAllChachongto0($homeworkID);
        if ($res < 0) {
            $data['code'] = 10;
            $data['data'] = '数据库设置查重为0时报错';
            return $data;
        }

        $homeworklist = Homework::getInstance()->searchByCondition(['id'=>$homeworkID]);
        if(empty($homeworklist)){
            $data['code'] = 1232;
            $data['errmsg'] = '没有此项作业';
            return $data;
        }
        $submitList = Homework::getInstance()->searchByCondition_homework_submit(['homeworkID' => $homeworkID]); //得到当前作业所有文件记录
        
        if(!is_array($submitList)){
            $data['code'] = 1024;
            $data['errmsg'] = $submitList;
            return $data;
        }
        if(empty($submitList)){
            $data['code'] = 0;
            $data['data'] = $submitList;
            return $data;
        }
        $errList = [];
        $userIDs = [];
        $file_MD5s=[];
        $ids = [];
        foreach ($submitList as $v){
            $filenamev = $v['filename'];
            $filenamev = pathinfo($filenamev)['extension'];
            // 因为MOSS查重对文件格式有影响，为防止恶意提交作业导致无法查重，故修改代码
            // if($filenamev == "zip" || $filenamev == "rar" || $filenamev == "pages" || $filenamev == "class")
            if(!($filenamev == "c" || $filenamev == "cpp" || $filenamev == "java" || $filenamev == "txt" || $filenamev == "py"))
                array_push($errList, $v['userID']);
            else{
                array_push($ids, $v['id']);
                array_push($userIDs,$v['userID']);
                array_push($file_MD5s, $v['file_MD5']);
            }      
        }
        if(empty($errList))
            $data['lose'] = $errList;
        else{            
            $con = [];
            $con['ID'] = ['op' => 'in','val'=>$errList];
            
            $erruserList = User::getInstance()->searchByCondition($con);
            $userIDss=[];
            foreach ($erruserList as $v){
                array_push($userIDss,$v['name']);
            }
            $data['lose'] = $userIDss;
        }
        //$data = $userIDss;
        //return $data;
        $jieguos = array();

        // 第一版使用自带字符串匹配方式进行查重 并 返回学生姓名
        // for($i=0;$i<count($file_MD5s);$i++) {
        //     for ($j = $i + 1; $j < count($file_MD5s); $j++)
        //     {
        //         $file = "D:/xampp/htdocs/ydjxpt/public/uploads/file/";
        //         $filename1=$file.$file_MD5s[$i];
        //         $filename2=$file.$file_MD5s[$j];
        //         $id1 = $ids[$i];
        //         $id2 = $ids[$j];
        //         $name1= UserModel::getInstance()->getListByCon(['ID' => $userIDs[$i]]);
        //         $name1=$name1[0]["name"];
        //         $name2= UserModel::getInstance()->getListByCon(['ID' => $userIDs[$j]]);
        //         $name2=$name2[0]["name"];
        //         $s = $this->compare($filename1, $filename2);
        //         if ($s>90 && $s!=2 &&$s!=3) {
        //             $s = floor($s*100)/100;
        //             $jieguo['Sname1']= $name1;
        //             $jieguo['Sname2']= $name2;
        //             $jieguo['bili']=$s."%";
        //             $jieguos[]=$jieguo;
        //             // 将$id1和$id2的chachong字段设为1
        //             $res = Homework_submitModel::getInstance()->setChachong($id1, $id2);
        //         }
        //         if($s==2)
        //         {
        //             $data['code'] = 123;
        //             $data['errmsg'] = "文件丢失";
        //             return $data;
        //         }
        //         if($s==3)
        //         {
        //             $data['code'] = 123;
        //             $data['errmsg'] = "文件丢失";
        //             return $data;
        //         }
                
        //     }
        // }


        // 使用Stanford_MOSS进行查重
   
        // 抓取result网页中所需信息，包括：组数、比例、行数、MD5文件
        function matchInContent($content) {
            $DetailInList_TotalNum = "(right)";
            $DetailInList_Percent = "(\d*%)";
            $DetailInList_LineNum = "#(?<=(right>))[0-9]*#";
            $DetailInList_MD5 = "([a-zA-Z0-9]{32})";
            preg_match_all($DetailInList_TotalNum, $content, $tmpNum); //组数
            preg_match_all($DetailInList_Percent, $content, $tmpPer);  //各组相同的比例
            preg_match_all($DetailInList_LineNum, $content, $tmpLine); //各组相同的行数
            preg_match_all($DetailInList_MD5, $content, $tmpMD5);      //各组MD5文件
            $result['grpNum'] = sizeof($tmpNum[0]);
            $result['percent'] = $tmpPer[0];
            $result['line'] = $tmpLine[0];
            $result['file_md5'] = $tmpMD5[0];
            return $result;
        }

        // 根据老师设置的单次作业重复率阈值显示数据
        function showDetailsOverChachongRate($details, $rate) { //全部配对结果 & 老师设置的阈值
            $res = [];   // 记录所有大于阈值的数据
            $res['grpNum'] = 0;  // 记录数据的组数
            for($i=0; $i<$details['grpNum']; $i++) {
                $perStr1 = substr($details['percent'][$i*2], 0, strlen($details['percent'][$i*2])-1);
                $perStr2 = substr($details['percent'][$i*2 + 1], 0, strlen($details['percent'][$i*2 + 1])-1);
                $perNum1 = number_format($perStr1); // 第i组第1个文件的重复率
                $perNum2 = number_format($perStr2); // 第i组第2个文件的重复率
                $rateNum = number_format($rate);    // 老师设置的重复率阈值

                // 如果2个文件有一个超过阈值，即加入数据
                // 单选按钮，|| 还是 &&
                if($perNum1 > $rateNum && $perNum2 > $rateNum) {
                    $res['grpNum'] = $res['grpNum'] + 1;
                    $res['percent'][] = $details['percent'][$i*2];
                    $res['percent'][] = $details['percent'][$i*2 + 1];
                    $res['line'][] = $details['line'][$i];
                    $res['file_md5'][] = $details['file_md5'][$i*2];
                    $res['file_md5'][] = $details['file_md5'][$i*2 + 1];
                    $res['userid'][] = $details['userid'][$i*2];
                    $res['userid'][] = $details['userid'][$i*2 + 1];
                    $res['username'][] = $details['username'][$i*2];
                    $res['username'][] = $details['username'][$i*2 + 1];
                    $res['matchNum'][] = $i;
                }
            }

            return $res;
        }

        $filetest = ROOT_PATH."public/uploads/file/";  //文件路径
        $mossid = "592439032";   //Stanford_MOSS用户ID，建议使用者重新发邮件申请
        $moss = new MOSS($mossid);
        // $moss->addBaseFile('D:\xampp\htdocs\MOSS-PHP-master\Example.java');//可选项：添加示例文档
        // $moss->setCommentString("test1.2");                                //可选项：添加comment

        // 将所有不重复文件加入查重范围
        $fileAddList = [];        //记录上传给MOSS的文件
        $UserIDAddList = [];      //记录上传给MOSS的文件对应的UserID
        $UserIDWithSamefile = []; //记录提交了相同作业的数组
        foreach ($submitList as $sub) {
            $flag = 0;            
            $UserIDWithSamefile[$sub['file_MD5']][] = $sub['userID'];
            // 跳过后缀为zip, rar, pages的文件
            $fileExtension = pathinfo($sub['filename'])['extension'];
            // if($fileExtension == "zip" || $fileExtension == "rar" || $fileExtension == "pages" || $fileExtension == "class")
            if(!($fileExtension == "c" || $fileExtension == "cpp" || $fileExtension == "java" || $fileExtension == "txt" || $fileExtension == "py"))
                continue;

            // 查看是否有相同文件已被上传
            foreach ($fileAddList as $a) {
                if($sub['file_MD5'] == $a) {
                    $flag = 1;
                    break;
                }
            }

            // 若未在已上传文件中找到相同文件，则提交给MOSS并记录
            if($flag == 0) {
                $addFileName = $filetest.$sub['file_MD5'];
                $moss->addFile("$addFileName");
                array_push($fileAddList, $sub['file_MD5']);
            }
        }

        // 将使用同一份文件的userID转换为userName并记录
        $data['UserNameWithSameFileMD5'] = [];
        $data['SameID'] = [];
        foreach ($UserIDWithSamefile as $list) {
            if(count($list) > 1) {
                $data['SameID'][] = $list;
                for($i=0; $i<count($list); $i++) {
                    $list[$i] = User::getInstance()->getNameByID($list[$i]);
                }
                $data['UserNameWithSameFileMD5'][] = $list;
            }
        }

        foreach ($fileAddList as $f) {
            $id = Homework::getInstance()->getUserIDByMD5andHomeworkID($f, $homeworkID);
            array_push($UserIDAddList, $id);
        }
        $data['addlist'] = $fileAddList;  //上传给MOSS的所有文件
        $data['addListUserID'] = $UserIDAddList; //上传给MOSS的文件对应的UserID
        $data['loseID'] = $errList;       //文件格式错误的userID
        // $data['UserIDWithSameFile'] = Homework::getInstance()->getUserIDWithSameFile($homeworkID);  //得到提交同一份文件的学生名单
 
        $data['moss'] = $moss->send();   //将数据提交给MOSS并返回result网页
        $data['moss'] = substr($data['moss'], 0, strlen($data['moss'])-1);  //返回的result网页最后有换行符，需删除
        // $data['moss'] = "http://moss.stanford.edu/results/416988957/";  //示例result网页
        // $data['moss'] = "http://moss.stanford.edu/results/293748237/";  //示例result网页 (34-聊天程序)
        // $data['moss'] = "http://moss.stanford.edu/results/847962499/";  //示例result网页(34-1.线程优先级练习)
        $url = $data['moss'];
        $content = file_get_contents($url);  //得到result网页源代码
        $details = matchInContent($content); //得到所需信息 并 存入data['details']
        $data['details'] = $details;

        // 使用MOSS查重结果，将MD5文件转换为userID 和 userName 并记录
        for($i=0; $i<$data['details']['grpNum']*2; $i++) {
            $usrID = Homework::getInstance()->getUserIDByMD5andHomeworkID($data['details']['file_md5'][$i], $homeworkID); //通过MD5文件得到userID
            $usrName = User::getInstance()->getNameByID($usrID); //通过userID得到userName
            $data['details']['userid'][] = $usrID;
            $data['details']['username'][] = $usrName;
        }

        // 将交完全相同作业的两个人查重率设为100，将格式不对的查重率置为-1，再根据MOSS返回信息依次记录查重率
        $datain = [];     // 记录数据库更新的查重率
        if(sizeof($data['SameID']) > 0) {
            $sameid = [];
            foreach ($data['SameID'] as $ids) {
                foreach ($ids as $i) {
                    $sameid[] = $i;
                }
            }
            $datain = Homework_submitModel::getInstance()->changeChachongto100($sameid, $datain);
        }

        if(sizeof($data['loseID']) > 0) {
            $datain = Homework_submitModel::getInstance()->changeChachongto_1($data['loseID'], $datain);
        }

        if($data['details']['grpNum'] != 0) {
            $percent = [];
            foreach($data['details']['percent'] as $per){
                $percent[] = (int)(substr($per, 0, strlen($per)-1));
            }

            $datain = Homework_submitModel::getInstance()->changeChachongtoPercent($data['details']['userid'], $percent, $datain);

        }       

        if(empty($datain)){
            $data['code'] = 3;
            $data['errmsg'] = 'datain为空';
        } else {
            foreach($datain as $id=>$chachong) {
                $res= Homework_submitModel::getInstance()->updateOneChachong($id, $chachong, $homeworkID);
                
                if ($res === true) {
                    $data['code'] = 0;
                    $data['errmsg'] = '更新成功';
                    continue;
                } else {
                    $data['code'] = 2;
                    $data['errmsg'] = $res;
                }
            }
        }
        $data['datain'] = $datain;
        $data['detailsOverRate'] = showDetailsOverChachongRate($data['details'], $chachong_rate); // 得到超过作业重复率阈值的数据
        // $data['data'] = $jieguos;  // 返回第一版查重结果
        return $data;
    }
    
    //修改提交作业的成绩
    public function ajax_changeScoreOfHomeworkSubmit(){
        $userID = session('userID');
        $score = (int)(input('post.score'));
        $homeworkSubmitID = input('post.id');
        
        $data = [];
        
        if(empty($userID) || empty($score) || empty($homeworkSubmitID)){
            $data['code'] = 1023;
            $data['errmsg'] = '缺少必要参数';
            return $data;
        }
        
        $homework_submit = Homework::getInstance()->searchByCondition_homework_submit(['id'=>$homeworkSubmitID]);
        
        if(empty($homework_submit)){
            $data['code'] = 1032;
            $data['errmsg'] = '未查到此条作业记录';
            return $data;
        }
        
        $homework = Homework::getInstance()->searchByCondition(['id'=>$homework_submit[0]['homeworkID']]);
        if(empty($homework)){
            $data['code'] = 1012;
            $data['errmsg'] = '没有此条作业';
            return $data;
        }
        
        if(Lession::getInstance()->checkIfUserInLession($userID, $homework[0]['lessionID']) !== true){
            $data['code'] = 1123;
            $data['errmsg'] = '您没有权限这么做';
            return $data;
        }
        
        $res = Homework::getInstance()->updateOneHomework_submit(['score'=>$score],$homeworkSubmitID);
        
        if($res === true){
            $data['code'] = 0;
            $data['data'] = '更新成功';
            return $data;
        }else{
            $data['code'] = 2;
            $data['errmsg'] = $res;
            return $data;
        }
    }
    
    // 第一版 ajax_getHomeworkStatisticList
    // public function ajax_getHomeworkStatisticList() {
    //     $userID = session('userID');
    //     $lessionID = input('post.lessionID');
        
    //     $data = [];
        
    //     if(empty($userID) || empty($lessionID)){
    //         $data['code'] = 1023;
    //         $data['errmsg'] = '缺少必要参数';
    //         return $data;
    //     }
        
    //     if(Lession::getInstance()->checkIfUserInLession($userID, $lessionID) !== true){
    //         $data['code'] = 1033;
    //         $data['errmsg'] = '您不在此课程中';
    //         return $data;
    //     }
    //     //先找到选修了这门课的所有的学生信息
    //     //通过lessionID来找到选择了这门课程的所有学生，并且按照用户类型排序，分别为：master->teacher->student
    //     $users = Lession::getInstance()->getUserListByLessionID($lessionID,true);
        
    //     if(!is_array($users)){
    //         $data['code'] = 1034;
    //         $data['errmsg'] = $users;
    //         return $data;
    //     }
        
    //     $students = [];
    //     foreach($users as $k => $v){
    //         if($v['type'] === 'teacher' || $v['type'] === 'master'){
    //             continue;
    //         }
    //         $arr = [];
    //         $arr['name'] = $v['name'];
    //         $arr['ID'] = $v['ID'];
    //         $arr['studentID'] = $v['studentID'];
    //         $arr['avescore'] = 0;
    //         $arr['homeworkNum'] = 0;
    //         $arr['submitNum'] = 0;
    //         $arr['bianyi_correct'] = Homework_submitModel::getInstance()->getBianyiCorrectNum($v['ID']);
    //         $arr['run_correct'] = Homework_submitModel::getInstance()->getRunCorrectNum($v['ID']);
    //         array_push($students,$arr);
    //     }
        
    //     if(empty($students)){    //如果这门课程没有学生选课的话，$students就是一个空数组！
    //         $data['code'] = 0;
    //         $data['data'] = $students;
    //         return $data;
    //     }
        
    //     //$homeworkList里面存储了查出来的属于这门课的所有作业信息
    //     $homeworkList = Homework::getInstance()->searchByCondition(['lessionID'=>$lessionID]);
        
    //     if(empty($homeworkList)){
    //         $data['code'] = 0;
    //         $data['data'] = $students;   //如果这门课没有布置作业的话，就简单的把学生的信息列出来。
    //         return $data;
    //     }
        
    //     $homeworkNum = count($homeworkList);
    //     foreach ($students as $k => $v){
    //         $students[$k]['homeworkNum'] = $homeworkNum;    //每个学生都有相同的被布置的作业总数
    //     }
        
    //     $homeworkIDs = [];
        
    //     foreach ($homeworkList as $v){
    //         array_push($homeworkIDs,$v['id']);
    //     }
        
    //     $con = [];
    //     $con['homeworkID'] = ['op'=>'in','val'=>$homeworkIDs];
    //     //'userID,count(*),sum(score)'赋值给之后查询条件中的field,这样在查询中就变成'select userID,count(*),sum(score)'了,其中score是字段值。
    //     //'userID'赋值给之后查询条件中的group，用于分组查询
    //     $homeworkSubmitList = Homework::getInstance()->searchByCondition_homework_submit($con,null,'userID,count(*),sum(score)','userID');
        
    //     if(empty($homeworkSubmitList)){    //如果这门课没有人提交作业的话，就简单的把学生信息列出来，初始化的学生信息中的成绩信息为初始化值，提交作业次数是0
    //         $data['code'] = 0;
    //         $data['data'] = $students;
    //         return $data;
    //     }
        
    //     $homeworkSubmitList2 = [];
    //     foreach ($homeworkSubmitList as $v){
    //         $homeworkSubmitList2[$v['userID']] = $v;  //这样就是用userID来代替key值，我们在之后的代码中可以直接使用userID来指代一维数组
    //     }
        
    //     foreach ($students as $k => $v){
    //         if(!isset($homeworkSubmitList2[$v['ID']])){  //如果!isset()，说明这个学生一次作业也没有交过，退出循环，此学生的成绩和提交作业次数使用初始默认值。
    //             continue;
    //         }
    //         $students[$k]['avescore'] = round($homeworkSubmitList2[$v['ID']]['sum(score)']/$homeworkNum , 2);    //round函数用来保留小数点后两位
    //         $students[$k]['submitNum'] = $homeworkSubmitList2[$v['ID']]['count(*)'];
    //     }
        
    //     $data['code'] = 0;
    //     $data['data'] = $students;
    //     return $data;
        
    // }

    // 对前一版本 ajax_getHomeworkStatisticList作少许修改，因多传入$chachong，故保留第一版代码供参考
    public function ajax_getHomeworkStatisticList()
    {
        $userID = session('userID');
        $lessionID = input('post.lessionID');
        $chachong = input('post.chachong_rate');
        $data = [];

        if (empty($userID) || empty($lessionID)) {
            $data['code'] = 1023;
            $data['errmsg'] = '缺少必要参数';
            return $data;
        }

        if (Lession::getInstance()->checkIfUserInLession($userID, $lessionID) !== true) {
            $data['code'] = 1033;
            $data['errmsg'] = '您不在此课程中';
            return $data;
        }
        // 先找到选修了这门课的所有的学生信息
        // 通过lessionID来找到选择了这门课程的所有学生，并且按照用户类型排序，分别为：master->teacher->student
        $users = Lession::getInstance()->getUserListByLessionID($lessionID, true);

        if (! is_array($users)) {
            $data['code'] = 1034;
            $data['errmsg'] = $users;
            return $data;
        }

        $students = [];
        foreach ($users as $k => $v) {
            if ($v['type'] === 'teacher' || $v['type'] === 'master') {
                continue;
            }
            $arr = [];
            $arr['name'] = $v['name'];
            $arr['ID'] = $v['ID'];//不显示
            $arr['studentID'] = $v['studentID'];//不显示—>显示
            $arr['avescore'] = 0;
            $arr['homeworkNum'] = 0;
            $arr['submitNum'] = 0;
            $arr['copyNum'] = 0;
            $arr['bianyi_correct'] = Homework_submitModel::getInstance()->getBianyiCorrectNum($v['ID']);
            $arr['run_correct'] = Homework_submitModel::getInstance()->getRunCorrectNum($v['ID']);
            array_push($students, $arr);
        }

        if (empty($students)) { // 如果这门课程没有学生选课的话，$students就是一个空数组！
            $data['code'] = 0;
            $data['data'] = $students;
            return $data;
        }

        // $homeworkList里面存储了查出来的属于这门课的所有作业信息
        $homeworkList = Homework::getInstance()->searchByCondition([
            'lessionID' => $lessionID
        ]);

        if (empty($homeworkList)) {
            $data['code'] = 0;
            $data['data'] = $students; // 如果这门课没有布置作业的话，就简单的把学生的信息列出来。
            return $data;
        }

        $homeworkNum = count($homeworkList);
        foreach ($students as $k => $v) {
            $students[$k]['homeworkNum'] = $homeworkNum; // 每个学生都有相同的被布置的作业总数
        }

        $homeworkIDs = [];

        foreach ($homeworkList as $v) {
            array_push($homeworkIDs, $v['id']);
        }

        $con = [];
        $con['homeworkID'] = [
            'op' => 'in',
            'val' => $homeworkIDs
        ];
        // 'userID,count(*),sum(score)'赋值给之后查询条件中的field,这样在查询中就变成'select userID,count(*),sum(score)'了,其中score是字段值。
        // 'userID'赋值给之后查询条件中的group，用于分组查询
        $homeworkSubmitList = Homework::getInstance()->searchByCondition_homework_submit($con, null, 'userID,count(*),sum(score)', 'userID');

        if (empty($homeworkSubmitList)) { // 如果这门课没有人提交作业的话，就简单的把学生信息列出来，初始化的学生信息中的成绩信息为初始化值，提交作业次数是0
            $data['code'] = 0;
            $data['data'] = $students;
            return $data;
        }
        //如果该课程布置过作业
        //用userID作为key值
        $homeworkSubmitList2 = [];
        foreach ($homeworkSubmitList as $v) {
            $homeworkSubmitList2[$v['userID']] = $v; // 这样就是用userID来代替key值，我们在之后的代码中可以直接使用userID来指代一维数组
        }
        
        foreach ($students as $k => $v) {
            if (! isset($homeworkSubmitList2[$v['ID']])) { // 如果!isset()，说明这个学生一次作业也没有交过，退出循环，此学生的成绩和提交作业次数使用初始默认值。
                continue;
            }
            $students[$k]['avescore'] = round($homeworkSubmitList2[$v['ID']]['sum(score)'] / $homeworkNum, 2); // round函数用来保留小数点后两位
            $students[$k]['submitNum'] = $homeworkSubmitList2[$v['ID']]['count(*)'];
            $students[$k]['copyNum'] = Homework::getInstance()->getCopyNum($v['ID'], $lessionID, $chachong);
        }
        $data['code'] = 0;
        $data['data'] = $students;
        return $data;
    }

    //获取留言板的统计信息
    public function ajax_getMessageBoardStatisticList(){
        $userID = session('userID');
        $lessionID = input('post.lessionID');
    
        $data = [];
    
        if(empty($userID) || empty($lessionID)){
            $data['code'] = 1023;
            $data['errmsg'] = '缺少必要参数';
            return $data;
        }
    
        if(Lession::getInstance()->checkIfUserInLession($userID, $lessionID) !== true){
            $data['code'] = 1033;
            $data['errmsg'] = '您不在此课程中';
            return $data;
        }
        //先找到选修了这门课的所有的学生信息
        //通过lessionID来找到选择了这门课程的所有学生，并且按照用户类型排序，分别为：master->teacher->student
        $users = Lession::getInstance()->getUserListByLessionID($lessionID);
    
        if(!is_array($users)){
            $data['code'] = 1034;
            $data['errmsg'] = $users;
            return $data;
        }
    
        $students = [];
        foreach($users as $k => $v){
            if($v['type'] === 'teacher' || $v['type'] === 'master'){
                continue;
            }
            $arr = [];
            $arr['name'] = $v['name'];
            $arr['ID'] = $v['ID'];
            $arr['studentID'] = $v['studentID'];
            $arr['answertimes'] = 0;    //设置学生默认的回答次数
            $arr['questionnumber'] = 0;    //设置学生默认的提问次数
            array_push($students,$arr);   //这样拼成一个有次序的数组
        }
    
        if(empty($students)){    //如果这门课程没有学生选课的话，$students就是一个空数组！
            $data['code'] = 0;
            $data['data'] = $students;
            return $data;
        }

    
        
        //下面这段代码主要是找出这门课程中学生提问的次数
        $con = ['lessionID' => $lessionID];
        //'userID,count(*),sum(score)'赋值给之后查询条件中的field,这样在查询中就变成'select userID,count(*),sum(score)'了,其中score是字段值。
        //'userID'赋值给之后查询条件中的group，用于分组查询
        $questionList = Bbs::getInstance()->searchByCondition_question($con,null,'userID,count(*)','userID');
    
        if(empty($questionList)){    //如果这门课没有人发表问题的话，就简单的把学生信息列出来，初始化的学生信息中的提问次数为0。
            $data['code'] = 0;
            $data['data'] = $students;
            return $data;
        }
    
        $questionList2 = [];
        foreach ($questionList as $v){
            $questionList2[$v['userID']] = $v;  //这样就是用userID来代替key值，我们在之后的代码中可以直接使用userID来指代一维数组
        }
    
        foreach ($students as $k => $v){
            if(!isset($questionList2[$v['ID']])){  //如果!isset()，说明这个学生一次作业也没有交过，退出循环，此学生的成绩和提交作业次数使用初始默认值。
                continue;
            }
            //$students[$k]['questionnumber'] = round($homeworkSubmitList2[$v['ID']]['sum(score)']/$homeworkNum , 2);    //round函数用来保留小数点后两位
            $students[$k]['questionnumber'] = $questionList2[$v['ID']]['count(*)'];
        }
        
        
        
        
        //下面这段代码主要是用来找出此课程中学生的回答问题的次数
        $answerList = Bbs::getInstance()->searchByConditionAnswer(['lessionID'=>$lessionID]);
        
        if(empty($answerList)){
            $data['code'] = 0;
            $data['data'] = $students;   //如果这门课没有布置作业的话，就简单的把学生的信息列出来。
            return $data;
        }
        
        $questionIDs = [];
        
        foreach ($answerList as $v){
            array_push($questionIDs,$v['id']);
        }
        
        $con = [];
        $con['questionID'] = ['op'=>'in','val'=>$questionIDs];
        $answerList = Bbs::getInstance()->searchByCondition_answer($con,null,'userID,count(*)','userID');
        
        if(empty($answerList)){    //如果这门课没有人回答问题的话，就简单的把学生信息列出来，初始化的学生信息中的回答次数为0。
            $data['code'] = 0;
            $data['data'] = $students;
            return $data;
        }
        $answerList2 = [];
        foreach ($answerList as $v){
            $answerList2[$v['userID']] = $v;  //这样就是用userID来代替key值，我们在之后的代码中可以直接使用userID来指代一维数组
        }
        
        foreach ($students as $k => $v){
            if(!isset($answerList2[$v['ID']])){  //如果!isset()，说明这个学生一次作业也没有交过，退出循环，此学生的成绩和提交作业次数使用初始默认值。
                continue;
            }
            //$students[$k]['questionnumber'] = round($homeworkSubmitList2[$v['ID']]['sum(score)']/$homeworkNum , 2);    //round函数用来保留小数点后两位
            $students[$k]['answertimes'] = $answerList2[$v['ID']]['count(*)'];
        }

        $data['code'] = 0;
        $data['data'] = $students;
        return $data;
    
    }
 
    public function ajax_getShareFileRecordList(){
        $shareFileID = input('post.ShareFileID');
        $userID = session('userID');
        
        $data = [];
        
        if(empty($shareFileID) || empty($userID)){
            $data['code'] = 1023;
            $data['errmsg'] = '缺少必要参数';
            return $data;
        }
        
        $shareFile = Sharefile::getInstance()->searchByCondition(['id'=>$shareFileID]);
        
        if(empty($shareFile)){
            $data['code'] = 1232;
            $data['errmsg'] = '没有此分享文件';
            return $data;
        }
        
        $lessionID = $shareFile[0]['lessionID'];

        if(Lession::getInstance()->checkIfUserInLession($userID, $lessionID) !== true){
            $data['code'] = 1234;
            $data['errmsg'] = '您不在此课程中';
            return $data;
        }

        $users = Lession::getInstance()->getUserListByLessionID($lessionID);
        
        if(!is_array($users)){
            $data['code'] = 1034;
            $data['errmsg'] = $users;
            return $data;
        }
        
        $students = [];
        foreach($users as $k => $v){
            if($v['type'] === 'teacher' || $v['type'] === 'master'){
                continue;
            }
            $arr = [];
            $arr['name'] = $v['name'];
            $arr['ID'] = $v['ID'];
            $arr['studentID'] = $v['studentID'];
            $arr['previewNum'] = 0;
            $arr['lastPreviewTime'] = 'NaN';
            $arr['dowloadNum'] = 0;
            $arr['lastDowloadTime'] = 'NaN';
            array_push($students,$arr);
        }
        
        if(empty($students)){
            $data['code'] = 0;
            $data['data'] = $students;
            return $data;
        }
        
        $con = [];
        $con['shareFileID'] = $shareFileID;
        $statistic_dowload = ShareFileRecord::getInstance()->search_statistic_dowload($con);
        $statistic_preview = ShareFileRecord::getInstance()->search_statistic_preview($con);
        if(!is_array($statistic_dowload)){
            $data['code'] = 1054;
            $data['errmsg'] = $statistic_dowload;
            return $data;
        }
        if(!is_array($statistic_preview)){
            $data['code'] = 1055;
            $data['errmsg'] = $statistic_preview;
            return $data;
        }
        $dowload = [];
        $preview = [];
        foreach ($statistic_dowload as $v){
            $dowload[$v['userID']] = $v;
        }
        foreach ($statistic_preview as $v){
            $preview[$v['userID']] = $v;
        }
        foreach ($students as $k => $v){
            if(isset($dowload[$v['ID']])){
                $students[$k]['dowloadNum'] = $dowload[$v['ID']]['Num'];
                $students[$k]['lastDowloadTime'] = $dowload[$v['ID']]['lasttime'];
            }
            if(isset($preview[$v['ID']])){
                $students[$k]['previewNum'] = $preview[$v['ID']]['Num'];
                $students[$k]['lastPreviewTime'] = $preview[$v['ID']]['lasttime'];
            }
        }
        $data['code'] = 0;
        $data['data'] = $students;
        return $data;
        
    }

    //改变某一学生的上传作业权限
    public function ajax_changeUserAuthorityUpload(){
        $authority_upload = input('post.authority_upload');
        $lessionID = input('post.lessionID');
        $userID = input('post.userID');
        
        $data = [];
        if(is_null($authority_upload) || empty($lessionID) || empty($userID)){
            $data['code'] = 1238;
            $data['errmsg'] = '缺少必要参数';
            return $data;
        }
        
        if((Lession::getInstance()->checkIfUserInLession(session('userID'), $lessionID)) !== true){
            $data['code'] = 213;
            $data['errmsg'] = '您不在此课程中';
            return $data;
        }
        
        $res = Lession::getInstance()->changeLession_UserModel($authority_upload, $lessionID, $userID);
        if($res === true){
            $data['code'] = 0;
            $data['data'] = '修改成功';
        }else{
            $data['code'] = 236;
            $data['errmsg'] = $res;
        }
        return $data;
    }
    //改变学生的分组
    public function ajax_changeUserGroup(){
        $group = input('post.group');
        $lessionID = input('post.lessionID');
        $userID = input('post.userID');
        $data = [];
        if(is_null($group) || empty($lessionID) || empty($userID)){
            $data['code'] = 1238;
            $data['errmsg'] = '缺少必要参数';
            return $data;
        }
        if((Lession::getInstance()->checkIfUserInLession(session('userID'), $lessionID)) !== true){
            $data['code'] = 213;
            $data['errmsg'] = '您不在此课程中';
            return $data;
        }
    
        $res = Lession::getInstance()->changeLession_UserModelgroup($group, $lessionID, $userID);
        if($res === true){
            $data['code'] = 0;
            $data['data'] = '修改成功';
        }else{
            $data['code'] = 236;
            $data['errmsg'] = $res;
        }
        return $data;
    }
    //改变分组的组长
    public function ajax_changeUserIsHeadman(){
        $headman = input('post.headman');
        $lessionID = input('post.lessionID');
        $userID = input('post.userID');
        $data = [];
        if(is_null($headman) || empty($lessionID) || empty($userID)){
            $data['code'] = 1238;
            $data['errmsg'] = '缺少必要参数';
            return $data;
        }
        if((Lession::getInstance()->checkIfUserInLession(session('userID'), $lessionID)) !== true){
            $data['code'] = 213;
            $data['errmsg'] = '您不在此课程中';
            return $data;
        }
    
        $res = Lession::getInstance()->changeLession_UserModelIsHeadman($headman, $lessionID, $userID);
        if($res === true){
            $data['code'] = 0;
            $data['data'] = '修改成功';
        }else{
            $data['code'] = 236;
            $data['errmsg'] = $res;
        }
        return $data;
    }
    
    //这里是在人员列表处删除成员！
    public function ajax_deleteLession_user(){
        $userID = input('post.userID');
        $array = array();
        $lessionID = input('post.lessionID');
        $array['id']=$lessionID;
        $res=Lession::getInstance()->searchFromLessionModel($array);
        $master=$res[0]['masterID'];
        $data = [];
        if(session('userID') === $master){
            $res = Lession::getInstance()->deleteFromLessionModel($lessionID, $userID);
            if(!empty($res)){
                $data['code'] = 0;
                return $data;
            }else{
                $data['code'] = 1;
                $data['errmsg'] = '什么也没干！';
                return $data;
            }
        }
        else{
            $data['code'] = 3;
            $data['errmsg'] = '您无权力进行此操作！';
            return $data;
        }
    }
    
    //删除共享资源
    public function ajax_deleteShareFile(){
        $sharefileID = input('post.shareid');
        $lessionID = input('post.lessionID');
        $userID = session('userID');
        
        $data = [];
        
        if(empty($sharefileID) || empty($lessionID) || empty($userID)){
            $data['code'] = 1738;
            $data['errmsg'] = '缺少必要参数';
        }
        
        if((Lession::getInstance()->checkIfUserInLession(session('userID'), $lessionID)) !== true){
            $data['code'] = 213;
            $data['errmsg'] = '您不在此课程中';
            return $data;
        }
        
        $res = Sharefile::getInstance()->deleteOneShareFile($sharefileID, $lessionID);
        if($res === 1){
            $data['code'] = 0;
            $data['data'] = '删除成功';
            return $data;
        }else if($res === 0){
            $data['code'] = 1;
            $data['errmsg'] = '该文件已被删除';
            return $data;
        }else{
            $data['code'] = 231;
            $data['errmsg'] = $res;
            return $data;
        }
    }
    
    //删除作业
    public function ajax_deleteHomework(){
        $homeworkID = input('post.homeworkID');
        $lessionID = input('post.lessionID');
        $userID = session('userID');
    
        $data = [];
    
        if(empty($homeworkID) || empty($lessionID) || empty($userID)){
            $data['code'] = 1738;
            $data['errmsg'] = '缺少必要参数';
        }
    
        if((Lession::getInstance()->checkIfUserInLession($userID, $lessionID)) !== true){
            $data['code'] = 213;
            $data['errmsg'] = '您不在此课程中';
            return $data;
        }
    
        $res = Homework::getInstance()->deleteOneHomework($homeworkID, $lessionID);
        if($res === 1){
            $data['code'] = 0;
            $data['data'] = '删除成功';
            return $data;
        }else if($res === 0){
            $data['code'] = 1;
            $data['errmsg'] = '该文件已被删除';
            return $data;
        }else{
            $data['code'] = 231;
            $data['errmsg'] = $res;
            return $data;
        }
    }
    //查重readfiles
    public  function  readfiles($filename){
        if(is_file($filename))
        {
            $content = file_get_contents($filename);
        }
        $value = array();
        foreach( self::$words as $word)
        {
            $list =substr_count($content,$word);
            $value[$word] = $list;
        }
        $value['int'] = $value['int']-$value['printf'];
        return $value;
    }
    //查重比较
    //拆分字符串       
    function split_str($str) {          
    preg_match_all("/./u", $str, $arr);    
    return $arr[0];       }   
    //相似度检测        
    function similar_text_cn($str1, $str2) {  
     $arr_1 = array_unique($this->split_str($str1));
     $arr_2 = array_unique($this->split_str($str2));         
     $similarity = count($arr_2) - count(array_diff($arr_2, $arr_1));  
     return $similarity;       
    }
    public function compare($filename1,$filename2)
    {
        if(is_file($filename1)&&is_file($filename2))
        {
            $text1 = file_get_contents($filename1);
            //$text1 = preg_replace('/([\x80-\xff]*)/i','',$text1);
            $text1 = str_replace( "   ", " ",$text1);
            $text1 = str_replace("\n","",$text1);
            
            $text2 = file_get_contents($filename2);
            //$text2 = preg_replace('/([\x80-\xff]*)/i','',$text2);
            $text2 = str_replace( "   ", " ",$text2);
            $text2 = str_replace("\n","",$text2);

            $line1 = count(file($filename1));
            $line2 = count(file($filename2));
            $val1 = $this->readfiles($filename1);
            $val2 = $this->readfiles($filename2);
            
      
            if($line1!=0&&$line2!=0)
            {
                
                similar_text($text1,$text2,$s);
                
                return $s;
            }

            $num = count($val1);
            $vala = array();
            $up = 0;
            $one = 0;
            $second = 0;
            for ($i = 0; $i < $num; $i++) {
                $one = $one + $val1[self::$words[$i]] * $val1[self::$words[$i]];
                $second = $second + $val2[self::$words[$i]] * $val2[self::$words[$i]];
                $dou = $val1[self::$words[$i]] - $val2[self::$words[$i]];
                $new = $dou * $dou;
                $up = $up + $new;
            }
            if ($one != 0 && $second != 0) {
                $s = sqrt($up / ($one * $second));
                return $s;
            }
            
             
        }
        else{
            if(is_file($filename1)==false)
            {
                $s=2;
                return $s;
            }
            if(is_file($filename2)==false)
            {
                $s=3;
                return $s;
            }
        }
      }
     
          
          /*返回串一和串二的最长公共子序列
           */
          function getLCS($str1, $str2, $len1 = 0, $len2 = 0) {
              $this->str1 = $str1;
              $this->str2 = $str2;
              if ($len1 == 0) $len1 = strlen($str1);
              if ($len2 == 0) $len2 = strlen($str2);
              $this->initC($len1, $len2);
              return $this->printLCS($this->c, $len1 - 1, $len2 - 1);
          }
          /*返回两个串的相似度
           */
          function getSimilar($str1, $str2) {
              $len1 = strlen($str1);
              $len2 = strlen($str2);
              $len = strlen($this->getLCS($str1, $str2, $len1, $len2));
              return $len * 2 / ($len1 + $len2);
          }
          function initC($len1, $len2) {
              for ($i = 0; $i < $len1; $i++) $this->c[$i][0] = 0;
              for ($j = 0; $j < $len2; $j++) $this->c[0][$j] = 0;
              for ($i = 1; $i < $len1; $i++) {
                  for ($j = 1; $j < $len2; $j++) {
                      if ($this->str1[$i] == $this->str2[$j]) {
                          $this->c[$i][$j] = $this->c[$i - 1][$j - 1] + 1;
                      } else if ($this->c[$i - 1][$j] >= $this->c[$i][$j - 1]) {
                          $this->c[$i][$j] = $this->c[$i - 1][$j];
                      } else {
                          $this->c[$i][$j] = $this->c[$i][$j - 1];
                      }
                  }
              }
          }
          function printLCS($c, $i, $j) {
              if ($i == 0 || $j == 0) {
                  if ($this->str1[$i] == $this->str2[$j]) return $this->str2[$j];
                  else return "";
              }
              if ($this->str1[$i] == $this->str2[$j]) {
                  return $this->printLCS($this->c, $i - 1, $j - 1).$this->str2[$j];
              } else if ($this->c[$i - 1][$j] >= $this->c[$i][$j - 1]) {
                  return $this->printLCS($this->c, $i - 1, $j);
              } else {
                  return $this->printLCS($this->c, $i, $j - 1);
              }
          }
      /*
      var $str1;
      var $str2;
      var $c = array();
      $lcs = new LCS();
      //返回最长公共子序列
      $lcs->getLCS("hello word","hello china");
      //返回相似度
      echo $lcs->getSimilar("吉林禽业公司火灾已致112人遇难","吉林宝源丰禽业公司火灾已致112人遇难");*/

    public function ajax_getHomeworkSubmitContent() {
        $return = array();
        $id = input('get.id');
        $homeworkID = input('get.homeworkID');
        $file_MD5 = Homework::getInstance()->getFileMD5ById($id);
        $test_cases = Homework::getInstance()->getTestCases($homeworkID);
        // 获取文件类型
        $file_name = Homework::getInstance()->getFileNameById($id);
        $temp = explode('.', $file_name);
        $length = count($temp);
        if($temp[$length - 1] == 'c' || $temp[$length - 1] == 'cpp') {
            $return['language'] = 7;
        } else if($temp[$length - 1] == 'java') {
            $return['language'] = 23;
        } else {
            $return['language'] = -1;
        }
        
        $file_address = Homework::getInstance()->getFileAddress($file_MD5);
        $file_all_address = ROOT_PATH.'public'.DS.'uploads'.DS.$file_address;
        $myfile = fopen($file_all_address   , "r") or die("Unable to open file!");
        $content = fread($myfile,filesize($file_all_address));
        fclose($myfile);
        $return['code'] = base64_encode($content);
        $return['cases'] = $test_cases;
        return json_encode($return);
    }
      
}
