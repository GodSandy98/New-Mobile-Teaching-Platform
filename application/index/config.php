<?php

use app\index\controller\Index;
return [
    'student_menu' => [
        [
            'name' => '我的课程',
            'icon' => 'fa-bar-chart-o',
            'two_level' => 'my_lessions',
            'two_level_url' => __URL__.'/index/lession/ajax_get_index_lession',
            'href' => __URL__.'/index/index/index'
        ],
        [
            'name' => '我的问题',
            'icon' => 'fa-edit',
            'two_level' => false,
            'href' => __URL__.'/index/MyQuestion/myQuestion'
        ],
        [
            'name' => '我的回答',
            'icon' => 'fa-table',
            'two_level' => false,
            'href' => __URL__.'/index/MyAnswer/myAnswer'
        ],
        [
            'name' => '个人信息',
            'icon' => 'fa-files-o',
            'two_level' => false,
            'href' => __URL__.'/index/user/userInfo'
        ]
    ],
    'teacher_menu' => [
        [
            'name' => '我的课程',
            'icon' => 'fa-bar-chart-o',
            'two_level' => 'my_lessions',
            'two_level_url' => __URL__.'/index/lession/ajax_get_index_lession',
            'href' => __URL__.'/index/index/index'
        ],
        [
            'name' => '我的问题',
            'icon' => 'fa-edit',
            'two_level' => false,
            'href' => __URL__.'/index/MyQuestion/myQuestion'
        ],
        [
            'name' => '我的回答',
            'icon' => 'fa-table',
            'two_level' => false,
            'href' => __URL__.'/index/MyAnswer/myAnswer'
        ],
        [
            'name' => '个人信息',
            'icon' => 'fa-files-o',
            'two_level' => false,
            'href' => __URL__.'/index/user/userInfo'
        ]
    ],
    'file_save_path' => 'file',
    'file_type'=>[
        'video'=>[
            'ogg',
            'mp4',
            'webm'
        ],
        'pdf'=>[
            'pdf'
        ],
        'office'=>[
            'doc',
            'docx',
            'xlsx',
            'xls',
            'ppt',
            'pptx'
        ]
    ],
];