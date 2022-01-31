<?php
namespace app\index\controller;

use think\Controller;

class EmptyClass extends Controller
{
    public function _initialize(){

        parent::_initialize();

    }

    private $file_type = [];
    
    public function getFile_type(){
        if(empty($this->file_type)){
            $filetype = config('file_type');
            $ans = [];
            foreach($filetype as $k => $v){
                foreach ($v as $val){
                    $ans[$val] = $k;
                }
            }
            $this->file_type = $ans;
        }
        return $this->file_type;
    }
    
}