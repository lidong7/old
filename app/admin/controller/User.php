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

namespace app\admin\controller;

use \think\Cache;
use \think\Controller;
use think\Loader;
use think\Db;
use \think\Session;
use app\admin\model\Hk_user;
use app\admin\model\Contact;
use app\admin\model\UserSchool;
class User extends Permissions
{
    //显示用户首页
        public function index(){
                $model = new Hk_user;
                $user_school = new UserSchool;
                // $con = new Contact;
                $xid= 1;   
                $post = $this->request->param();
                if (isset($post['keywords']) and !empty($post['keywords'])) {
                    $where['nickname'] = ['like', '%' . $post['keywords'] . '%'];
                }
                $where['is_del']=0;
                //通过登录的学校id找到对应的用户
                $re_one =$user_school->where('xid',$xid)->select();
                // print_r($re_one);exit;
                foreach ($re_one as $key => $value) {
                    # code...
                    $data[]=$value['uid'];
                }
                if(is_array($data)){
                    $uid = implode(',', $data);

                }
                // where('id','not in','1,5,8');
                if(isset($post['create_time']) and !empty($post['create_time'])) {
                    $min_time = strtotime($post['create_time']);
                    $max_time = $min_time + 24 * 60 * 60;
                    $where['create_time'] = [['>=',$min_time],['<=',$max_time]];
                }
                
                $user = empty($where) ? $model->where('id','in',$uid)->order('create_time desc')->paginate(20) : $model->where($where)->where('id','in',$uid)->order('create_time desc')->paginate(20,false,['query'=>$this->request->param()]);
                $this->assign('user',$user);
                return $this->fetch();
          }


        //删除用户
        public function delete(){
                $model   =  new Hk_user;
                $Contact =  new Contact;
                $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0; 
                // print_r($id);exit;
                if($this->request->isAjax()) {
                    if($id>0){
                        // $re = $model->where('id',$id)->delete();//真实删除
                        $re = $model ->where('id',$id)->update(['is_del' =>'1']);//软删除
                        if($re){
                            return $this->success('删除成功','admin/user/index');
                        }else{
                            $this->error('删除失败!');
                        }
                    }
                }
          }

        //审核用户通过
        public function checkstatus(){
             $model   =  new Hk_user;
             $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0; 
             if($this->request->isAjax()) {
                    if($id>0){
                            // echo 'check!';exit;
                        $re = $model ->where('id',$id)->update(['status' =>'1']);//把状态更新为1
                        if($re){
                            return $this->success('审核成功','admin/user/index');
                        }else{
                            $this->error('审核失败!');
                        }    
                    }
                }
        }

        //审核用户不通过
        public function audit(){
             $model   =  new Hk_user;
             $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0; 
             if($this->request->isAjax()) {
                    if($id>0){
                            // echo 'check!';exit;
                        $re = $model ->where('id',$id)->update(['status' =>'0']);//把状态更新为1
                        if($re){
                            return $this->success('审核成功','admin/user/index');
                        }else{
                            $this->error('审核失败!');
                        }    
                    }
                }
        }

}
