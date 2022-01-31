<?php
namespace app\index\controller;

use app\index\model\statistic_dowloadModal;
use app\index\model\statistic_previewModal;

class ShareFileRecord extends Checkuser{

    private static $_instance;
    
    public static function getInstance()
    {
        if(!(self::$_instance instanceof self)){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function dowload_addOneRecord($shareFileID, $userID){
        if(empty($shareFileID) || empty($userID)){
            return '缺少必要参数';
        }
        
        return $this->addOneRecord($shareFileID, $userID, statistic_dowloadModal::getInstance());
        
    }
    

    public function preview_addOneRecord($shareFileID, $userID){
        if(empty($shareFileID) || empty($userID)){
            return '缺少必要参数';
        }
        
        return $this->addOneRecord($shareFileID, $userID, statistic_previewModal::getInstance());
    
    }
    
    private function addOneRecord($shareFileID, $userID, $function){
        if(empty($shareFileID) || empty($userID) || empty($function)){
            return '缺少必要参数';
        }
        
        $condition = [];
        $condition['userID'] = $userID;
        $condition['shareFileID'] = $shareFileID;
        $time = date('Y-m-d h:i:s');
        
        $search = $function->getListByCon($condition);
        
        if(!is_array($search)){
            return $search;
        }
        
        if(empty($search)){
            $condition['lasttime'] = $time;
            $dowloadNum = 1;
            $insert = $function->insertOneRecord($condition);
        
            if($insert === true){
                return true;
            }else{
                return $insert;
            }
        
        }else{
            $search = $search[0];
            $id = $search['id'];
            unset($search['id']);
            $search['Num'] = $search['Num'] + 1;
            $search['lasttime'] = $time;
            $update = $function->updateOneRecord($search, $id);
        
            if($update === true){
                return true;
            }else{
                return $update;
            }
        }
    }
    
    public function search_statistic_dowload($condition){
        return statistic_dowloadModal::getInstance()->getListByCon($condition);
    }
    
    public function search_statistic_preview($condition){
        return statistic_previewModal::getInstance()->getListByCon($condition);
    }
    
}