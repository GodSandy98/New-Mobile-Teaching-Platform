<?php
namespace app\index\controller;

class Index extends Checkuser
{   
    private static $_instance;
    
    public static function getInstance()
    {
        if(!(self::$_instance instanceof self)){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function index()
    {
        $this->assign('usertype',session('usertype'));
        return $this->fetch('index');   //从控制器的index跳到视图的index中,在这个时候已经把usertype传到了index.html,
        //所以在index.html界面就可以直接使用usertype的值。
    }
    
    public function ajax_getmenu(){
        $ans = [];
        $ans['code'] = 0;
        if(session('usertype') == 'student'){
            $ans['data'] = config('student_menu');     //这是调用config.php文件中的'student_menu'
        }else if(session('usertype') == 'teacher'){
            $ans['data'] = config('teacher_menu');
        }else{
            $ans['code'] = 1;
            $ans['errmsg'] = '没有对应用户';
        }
        return $ans;
    }
    
    public function getFileType($filename){
        $file_type = $this->getFile_type();
        $ext = substr(strrchr($filename, '.'), 1);
        $ext = strtolower($ext);
        $ans = empty($file_type[$ext])?'other':$file_type[$ext];
        return $ans;
    }
}
