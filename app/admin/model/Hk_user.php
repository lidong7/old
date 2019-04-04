<?php
// +----------------------------------------------------------------------
// | Tplay [ WE ONLY DO WHAT IS NECESSARY ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://tplay.pengyichen.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// |  
// +----------------------------------------------------------------------


namespace app\admin\model;

use \think\Model;
class Hk_user extends Model
{
    public function contacts()
    {
    	//关联联系人表
		return $this->hasOne('Contact', "uid", "id")->field('contact_name,contact_phone');
    }


}
