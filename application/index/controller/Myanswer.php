<?php
namespace app\index\controller;
use app\index\model\Lession_userModel;
use app\index\model\LessionModel;
use app\index\model\Bbs_questionModel;
use app\index\model\Bbs_answerModel;
use app\index\model\UserModel;

class Myanswer extends Checkuser{

    private static $_instance;

    public static function getInstance()
    {
        if(!(self::$_instance instanceof self)){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    public function myAnswer()
    {
        //$userID = session('userID');
        //$this->assign('userID',$userID);
        return $this->fetch('myAnswer');
    }
    
}