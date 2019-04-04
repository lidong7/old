<?php
// +----------------------------------------------------------------------
// | Tplay [ WE ONLY DO WHAT IS NECESSARY ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://tplay.pengyichen.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author:ld < 502056142@qq.com >
// +----------------------------------------------------------------------

namespace app\api\controller;

use \think\Controller;
use think\Loader;
use think\Db;
use \think\Session;
class Base extends Controller
{
    public function _initialize(){
        if(!$_SESSION['token'] || !$_SESSION['unid']){
            //没有登录
             $result = array('msg'=>'请先去登录!','code'=>500);
                echo json_encode($result);exit;
        }
    }

}
