<?php
namespace app\index\controller;

use app\index\Model\ResourceModel;

class File extends Checkuser{

    private static $_instance;

    public static function getInstance()
    {
        if(!(self::$_instance instanceof self)){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    private $catalog_tmp = '~tmp';
    private $catalog;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->catalog = config('file_save_path');
    }
    
    //分块上传
    public function uploadFile($file,$filename,$total,$index,$username){
        
        // 移动到框架应用根目录/public/uploads/ 目录下
        try{
            $info = $file->validate([])->move(ROOT_PATH.'public'.DS.'uploads'.DS.$this->catalog_tmp,$filename.'_'.$username.'_'.$index);
        }catch(\Exception $e){
            return array('code'=>2,'msg'=>$e->getMessage());
        }
        if($info){
            unset($info);
            $res = $this->FileMerge($filename,$total,$index,$username);
            if(is_numeric($res)){
                return array('code'=>0,'data'=>$res);
            }else{
                return array('code'=>1,'msg'=>$res);
            }
        }else{
            // 上传失败获取错误信息
            return array('code'=>2,'msg'=>$file->getError());
        }
    }

    public function checkFile($filename,$username,$total){
        $str = $filename.'_'.$username;
        $cate = ROOT_PATH.'public' . DS .'uploads'.DS.$this->catalog_tmp.DS;
    
        for($j = 1;$j <= $total; $j++){
            if(file_exists($cate.$str.'+'.$j)){
                break;     //如果查到临时文件夹中有此文件，就退出循环
            }
        }
    
        $ans = 0;
        if($j < $total){
            $ans = $j;
        }else if($j == $total){
            unlink($cate.$str.'+'.$j);
        }
    
        return $ans;
    }
    
    private function FileMerge($filename,$total,$index,$username){
        $str = $filename.'_'.$username;
        
        $cate = ROOT_PATH.'public' . DS .'uploads'.DS.$this->catalog_tmp.DS;
        
        if(file_exists($cate.$str)){
            unlink($cate.$str);
        }
        
        if(1 == $index){
            $ind = $this->checkFile($filename, $username,$total);
            
            if($ind === 0){
                try{
                    rename($cate.$str.'_1',$cate.$str.'+1');
                }catch(\Exception $e){
                    return $e->getMessage();
                }
            }
        }
        
        for($now_index = 1;$now_index<=$total;$now_index++){
            if(file_exists($cate.$str.'+'.$now_index)){
                break;
            }
        }
        
        if($now_index > $total) return 0;
        
        for($i=$now_index+1;$i<=$total;$i++){
            if(!file_exists($cate.$str.'_'.$i)){
                break;
            }
            file_put_contents($cate.$str.'+'.$now_index,file_get_contents($cate.$str.'_'.$i),FILE_APPEND);
            unlink($cate.$str.'_'.$i);
        }
        
        if($i > $total){
            $dir = ROOT_PATH.'public'.DS.'uploads'.DS.$this->catalog;
            if(!is_dir($dir)){
                mkdir($dir);
            }
            rename($cate.$str.'+'.$now_index,ROOT_PATH.'public'.DS.'uploads'.DS.$this->catalog.DS.$filename);
        }else{
            rename($cate.$str.'+'.$now_index, $cate.$str.'+'.($i-1));
        }
        return $i-1;
    }
    

    public function watchVideo($address,$name,$md5){
        $filename = ROOT_PATH.'public'.DS.'uploads'.DS.$address;
        if(file_exists($filename)){
            header("Pragma: public");
            header("Expires: 0");
            header("Content-Length: ".filesize($filename));
            header('Content-type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . $name);
        
            ob_clean();
            flush();
            readfile( $filename );  //文件路径$filePath
        }else{
            echo '源文件不存在';
            ResourceModel::getInstance()->deleteByKey($md5);
        }
        exit;
    }
    
    
    public function dowloadFile($address,$name,$md5){
        $filename = ROOT_PATH.'public'.DS.'uploads'.DS.$address;  
        if(file_exists($filename)){
             header('Accept-Ranges: bytes');
             header('Accept-Length: ' . filesize($filename));
             header('Content-Transfer-Encoding: binary');
             header('Content-type: application/octet-stream');
             header('Content-Disposition: attachment; filename=' . $name);
             header('Content-Type: application/octet-stream; name=' . $name);

            if(is_file($filename) && is_readable($filename)){
                //打开文件
				
                $fp = fopen($filename, "rb");
                //设置指针位置
                fseek($fp,0);
                //虚幻输出
                while (!feof($fp)) {
                    //设置文件最长执行时间
                    set_time_limit(0);
                    print (fread($fp, 1024 * 8)); //输出文件
                    flush(); //输出缓冲
                    ob_flush();
                }
                fclose($fp);
             }
        }else{
            echo '源文件不存在';
            ResourceModel::getInstance()->deleteByKey($md5);
        }
        exit;
    }
    
    public function checkIfFileExist($md5){
        $con1 = [];
        $con1['MD5'] = $md5;
        return ResourceModel::getInstance()->getListByCon($con1);
    }
    
    public function SaveFileAndData($condition,$file,$total,$index,$md5,$username,$controller,$function){
        $data = [];
        
        try{
            $res = $this->uploadFile($file, $md5, $total, $index, $username);
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
                        $res2 = $controller->$function($condition);
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
    
}