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

use \think\Cache;
use \think\Controller;
use think\Loader;
use think\Db;
use \think\Session;
use app\api\model\Hk_user;
use app\api\model\Hk_user_contact;
use app\api\model\Hk_user_school;
use ChuanglanSmsHelper\ChuanglanSmsApi;
class User extends Controller
{
    // 
    public function _initialize(){
        //开启session
          session_start();
    } 
    /**
     *用户注册及用户登录
      author:李东
     */
    public function registered(){
      // session_start();
        if($this->request->isPOST()) {
            $user          = new Hk_user;
            $phone         = $_POST['phone'];//手机号
            $phone_captcha = $_POST['phone_captcha'];//输入的验证码
            $password      = password($_POST['password']);//密码
            $repassword    = password($_POST['repassword']);//确认密码

            $re = $user->where('phone',$phone)->find(); 
            if(!$re){
                if(!preg_match("/^1[3456789]{1}\d{9}$/",$phone)) {
                    $result = array('msg' => '手机号码格式不正确!', 'code'=>500);
                    echo json_encode($result);
                    exit;
                }
                if(empty($phone_captcha)){
                     $arr =array('msg'=>'验证码不能为空!', 'code'=>500);
                            echo  json_encode($arr);
                            exit;
                }
                // print_r($_SESSION);exit;
                if(isset($_SESSION['num'])) {
                        $code = $_SESSION['num'];//获取的验证码
                            if($code!=$phone_captcha || empty($code)){
                                $arr =array('msg'=>'请输入正确的验证码!', 'code'=>500);
                                echo  json_encode($arr);
                                exit;
                        }
                }else{
                    $arr =array('msg'=>'请输入正确的验证码!', 'code'=>500);
                                echo  json_encode($arr);
                                exit;
                }

            


                if(isset($_SESSION['time'])){
                    //判断时间是否大于60秒
                    if((strtotime($_SESSION['time'])+60)<time()) {//将获取的缓存时间转换成时间戳加上60秒后与当前时间比较，小于当前时间即为过期
                        //                    session_destroy();
                        $_SESSION['time'] = null;
                        $_SESSION['num']  = null;
                        $arr =array('msg'=>'验证码已过期,请重新获取!', 'code'=>500);
                        echo  json_encode($arr);
                        exit;
                    }
                }

                if(empty($_POST['password']) || empty($_POST['repassword']) ){
                    $arr =array('msg'=>'密码不能为空!', 'code'=>500);
                    echo  json_encode($arr);
                    exit;
                }

                if($password!=$repassword){
                    $arr =array('msg'=>'输入的两次密码不匹配!', 'code'=>500);
                    echo  json_encode($arr);
                    exit;
                }

                //转换成数组
                $user->data([
                    'phone'     =>$phone,
                    'password'  =>$password,
                    'addtime'   =>time()
                ]);

                $result=$user->save();
                if($result){
                    $result = array('msg'=>'注册成功!', 'code'=>200);
                    echo json_encode($result);exit;
                }else{
                    $result = array('msg'=>'注册失败!', 'code'=>500);
                    echo json_encode($result);exit;
                }

            }else{
                $result = array('msg'=>'该手机号已经注册!', 'code'=>500);
                echo json_encode($result);exit;
            }
        }
    }

   /*
        发送短信
        author:李东
    */
    public function phone()
    {

        $clapi  = new ChuanglanSmsApi();
       
        // session_start();
        if ($this->request->isPOST()){
            //获取传过来的手机号
            $phone = $_POST['phone'];
            $state = 1;//1为注册验证码  2为找回密码验证码
            //匹配手机号正则
            if(!preg_match("/^1[3456789]{1}\d{9}$/",$phone)) {
                $result = array('msg' => '手机号码格式不正确!', 'code'=>500);
                echo json_encode($result);
                exit;
            }
             $code = mt_rand(100000,999999);
            //发送验证码
             // $smsto = send_sms($phone,$state,['code'=>$code]);//阿里云短信接口
            switch ($state) {
                case '1':
                    $msg = '【老年大学】您好，您注册账号的验证码'.$code.'';
                    break;
                case '2':
                    $msg = '【老年大学】您好，您找回密码的验证码'.$code.'';
                    break;
                case '3':
                    $msg = '【老年大学】您好，您更换手机的验证码'.$code.'';
                    break;    
                default:
                    # code...
                    break;
            }
            $result = $clapi->sendSMS($phone,$msg);//253短信接口
           // print_r($result);exit;
           if(!is_null(json_decode($result))){
                    $output=json_decode($result,true);

                    if(isset($output['code'])  && $output['code']=='0'){
                        if (isset($_SESSION['time']))//判断缓存时间
                            {
                                $_SESSION['time'] = date("Y-m-d H:i:s");
                                $_SESSION['num'] = $code;
                            }else{
                                $_SESSION['time'] = date("Y-m-d H:i:s");
                                $_SESSION['num'] = $code;
                            }
                            $arr =array(
                                'msg'=>'短信发送成功!',
                                'code'=>200
                            );
                            echo  json_encode($arr);
                            exit;

                    }else{
                           $arr =array(
                        'msg'=>'短信发送失败!',
                        'code'=>500
                    );
                    echo  json_encode($arr);
                    exit;
                        // echo $output['errorMsg'];
                    }
                }else{
                         $arr =array(
                        'msg'=>'短信发送失败!',
                        'code'=>500
                    );
                    echo  json_encode($arr);
                    exit;
                }

            // if(empty($smsto)) {
            //         $arr =array(
            //             'msg'=>'短信发送失败!',
            //             'code'=>500
            //         );
            //         echo  json_encode($arr);
            //         exit;
            // } else {
            //     if (isset($_SESSION['time']))//判断缓存时间
            //     {
            //         $_SESSION['time'] = date("Y-m-d H:i:s");
            //         $_SESSION['num'] = $code;
            //     }else{
            //         $_SESSION['time'] = date("Y-m-d H:i:s");
            //         $_SESSION['num'] = $code;
            //     }
            //     $arr =array(
            //         'msg'=>'短信发送成功!',
            //         'code'=>200
            //     );
            //     echo  json_encode($arr);
            //     exit;
            // }
        }
    }

    //
    /*  登录接口
        author:李东
    */
    public function login(){
        if ($this->request->isPOST()){
            $phone    = $_POST['phone'];//手机号
            $password = $_POST['password'];//密码
//            $phone    = 17671443199;//手机号
//            $password = 123456;//密码
            $user = new Hk_user;
            $re = $user ->where('phone',$phone)->find();
            if(empty($phone) ||empty($password)){
                $result = array('msg'=>'手机号或者密码不能为空!', 'code'=>500);
                echo json_encode($result);exit;
            }
            if(!preg_match("/^1[3456789]{1}\d{9}$/",$phone)){
                $result = array('msg'=>'手机号码格式不正确!', 'code'=>500);
                echo json_encode($result);exit;
            }

            if($re){
                //登录账号
                $data= [
                    'phone'     => $phone,
                    'password'  =>password($password)
                ];
                $arr=$user ->where($data)->find();
                if(password($password)!=$arr['password']){
                    $result = array('msg'=>'密码不正确!', 'code'=>500);
                    echo json_encode($result);exit;
                }else{
                    //登录成功
                    $_SESSION['token']=password($password);
                    $_SESSION['unid']=$arr['id'];
                    $result = array(
                        'msg'=>'登录成功!',
                        'code'=>200
                    );
                    echo json_encode($result);exit;
                }

            }else{
                $result = array('msg'=>'账号不存在!','code'=>500);
                echo json_encode($result);exit;
            }

        }

    }

    /*退出登录
     删除session
     author:李东
     * */
    public function logout(){
        $_SESSION['token']=null;
        $_SESSION['unid']=null;
    }


    /*
     * 找回密码
     author:李东
     * */
    public function Retrieve_password(){
        if ($this->request->isPOST()){
            $phone         = $_POST['phone'];//手机号
            $phone_captcha = $_POST['phone_captcha'];//输入的验证码
            $password      = password($_POST['password']);//新密码
            $repassword    = password($_POST['repassword']);//确认新密码
            $user          = new Hk_user;

            $re = $user->where('phone',$phone)->find();
            if($re){
                if(!preg_match("/^1[3456789]{1}\d{9}$/",$phone)){
                    $result = array('msg'=>'手机号码格式不正确!','code'=>500);
                    echo json_encode($result);exit;
                }


                if(empty($phone_captcha)){
                    $arr =array('msg'=>'手机验证码不能为空!','code'=>500);
                    echo  json_encode($arr);
                    exit;
                }

                if(isset($_SESSION['num'])) {
                    $code = $_SESSION['num'];//获取的验证码
                }

                if($code!=$phone_captcha || empty($code)){
                    $arr =array('msg'=>'请输入正确的验证码!','code'=>500);
                    echo  json_encode($arr);
                    exit;
                }


                if(isset($_SESSION['time'])){
                    //判断时间是否大于60秒
                    if((strtotime($_SESSION['time'])+60)<time()) {//将获取的缓存时间转换成时间戳加上60秒后与当前时间比较，小于当前时间即为过期
                        //                    session_destroy();
                        $_SESSION['time'] = null;
                        $_SESSION['num'] = null;
//                        session('time',null);
//                        session('num',null);
                        $arr =array('msg'=>'验证码已过期,请重新获取!','code'=>500);
                        echo  json_encode($arr);
                        exit;
                    }
                }

                if(empty($_POST['password']) || empty($_POST['repassword']) ){
                    $arr =array('msg'=>'密码不能为空!','code'=>500);
                    echo  json_encode($arr);
                    exit;
                }

                if($password!=$repassword){
                    $arr =array('msg'=>'输入的两次密码不匹配!','code'=>500);
                    echo  json_encode($arr);
                    exit;
                }

                //转换成数组

                $result=$user->where('phone',$phone)
                             ->update(['password' => $password]);
                if($result){
                    $result = array('msg'=>'修改成功,请输入新密码登录!','code'=>200);
                    echo json_encode($result);exit;
                }else{
                    $result = array('msg'=>'修改失败,请重新输入!','code'=>500);
                    echo json_encode($result);exit;
                }

            }else{
                $result = array('msg'=>'该手机号不存在!','code'=>500);
                echo json_encode($result);exit;
            }

        }
    }


    /*
     * 添加个人资料
       author:李东
     * */
    public function add(){
        if ($this->request->isPOST()){
            $unid = 11;//session获取
            $xid = 1;//session获取
            // print_r($unid);exit;
            $user         =  new Hk_user;//实例化用户表
            $user_contact =  new Hk_user_contact;//实例化表
            $user_school  =  new Hk_user_school;//实例化表
                // $post = $this->request->post();
                //模拟post传值数据进行测试

                $post=[
                        'username'           =>'李东',
                        'cardnum'            =>'420922200001286055',
                        'provinces'          =>'湖北',
                        'cities'             =>'武汉',
                        'areas'              =>'武昌',
                        'address'            =>'街道口',
                        'political'          =>'团员',
                        'educational'        =>'大学',
                        'Retirement_status'  =>'退休',
                        'Student_type'       =>'社区',
                        'employer'           =>'长江传媒大厦',
                        'position'           =>'php',
                        'contact_name'       =>'李东',
                        'contact_phone'      =>'17671443199',
                        'contact_nexus'      =>'朋友',
                        'contact_name1'      =>'李东1',
                        'contact_phone1'     =>'17671443197',
                        'contact_nexus1'     =>'朋友1',
                        'xid'                => $xid,
                        'uid'                =>$unid
                ];  

                $data=[
                     [ 'uid' => $unid,'contact_name'=>$post['contact_name'], 'contact_phone'=>$post['contact_phone'],'contact_nexus'=>$post['contact_nexus']],
                     [ 'uid' => $unid,'contact_name'=>$post['contact_name1'], 'contact_phone'=>$post['contact_phone1'],'contact_nexus'=>$post['contact_nexus1']]
                ];
                // print_r($data);exit;
                //验证  唯一规则： 表名，字段名，排除主键值，主键名
                $validate = new \think\Validate([
                    ['username', 'require', '姓名不能为空'],
                    ['cardnum', 'number|length:18', '身份证格式请输入正确'],
                    ['provinces', 'require', '省份名称不能为空'],
                    ['cities', 'require', '城市不能为空'],
                    ['areas', 'require', '区名不能为空'],
                    ['address', 'require', '详细地址不能为空'],
                    ['political', 'require', '政治面貌不能为空'],
                    ['educational', 'require', '文化程度不能为空'],
                    ['Retirement_status', 'require', '退休状态不能为空'],
                    ['Student_type', 'require', '学生分类不能为空'],
                    ['employer', 'require', '工作单位不能为空'],
                    ['position', 'require', '职位不能为空'],
                    ['contact_name', 'require', '联系人不能为空'],
                    ['contact_phone', 'number|length:11', '联系人电话请输入正确'],
                    ['contact_nexus', 'require', '联系人关系不能为空'],
                    ['contact_name1', 'require', '联系人不能为空'],
                    ['contact_phone1', 'number|length:11', '联系人电话请输入正确'],
                    ['contact_nexus1', 'require', '联系人关系不能为空'],
                ]);

                    //验证部分数据合法性
                    if (!$validate->check($post)) {
                        $result = array('msg'=> $validate->getError(),'code'=>500);
                         echo json_encode($result);exit;
                    }

                    //过滤数组中非字段属性的值  插入用户表
                    $result=$user->allowField(true)->save($post,['id' => $unid]);
                    //过滤数组中非字段属性的值  插入用户联系人表
                    // $result_contact=$user_contact->allowField(true)->saveAll($data);
                    $contact = $user_contact->where('uid',$unid)->find();
                    //如果有这个学校 就无须添加,没有就添加
                    if(!$contact){
                        $result_contact=$user_contact->allowField(true)->saveAll($data);
                    }else{
                        $result_contact=1;
                    }

                    //过滤数组中非字段属性的值  插入用户学校表
                    $school = $user_school->where('xid',$xid)->find();
                    //如果有这个学校 就无须添加,没有就更新
                    if(!$school){
                        $result_school=$user_school->allowField(true)->save($post);
                    }else{
                        $result_school=1;
                    }

                    if($result && $result_contact && $result_school){
                            $result = array('msg'=>'修改成功,请重新输入!','code'=>200);
                            echo json_encode($result);exit;
                    }else{
                            $result = array('msg'=>'修改失败,请重新输入!','code'=>500);
                            echo json_encode($result);exit;
                    }

        }
    }

        /*
            修改密码
            */
        public function  change_password(){
                $unid =11;//SESSION获取
            if($this->request->isPOST()){
                $Opassword  = password($_POST['Opassword']);//原始密码
                $password   = password($_POST['password']);//密码
                $repassword = password($_POST['repassword']);//确认密码
                $user       = new Hk_user;//实例化用户表
                $data=[
                    'id'=>$unid,
                    'password'=>$Opassword
                ];
                // print_r($data);exit;
                $record = $user->where($data)->find();
                if(!$record){
                    $result = array('msg'=>'原始密码错误!','code'=>500);
                    echo json_encode($result);exit;
                }elseif(empty($_POST['password']) || empty($_POST['password']) ){
                    $arr =array('msg'=>'密码不能为空!','code'=>500);
                    echo  json_encode($arr);
                    exit;
                }elseif($password!=$repassword){
                    $arr =array('msg'=>'输入的两次密码不匹配!','code'=>500);
                    echo  json_encode($arr);
                    exit;
                }

                $result=$user->where('id',$unid)
                             ->update(['password' => $password]);

                if($result){
                        $arr =array('msg'=>'修改密码成功,下次请用新密码进行登录!','code'=>200);
                        echo  json_encode($arr);
                        exit;
                }else{
                    $arr =array('msg'=>'密码修改失败,请重试!','code'=>500);
                        echo  json_encode($arr);
                        exit;
                }

               





            }
        }


            /*
                变更手机
            */
        public function Change_phone(){
            if($this->request->isPOST()){
                $unid          = 11;//session获取
                $phone         = 17671443199; 
                $phone_captcha = 9981;
                $user          = new Hk_user;//实例化用户表
                $record =$user->where('phone',$phone)->find();
                    if(!preg_match("/^1[3456789]{1}\d{9}$/",$phone)){
                        $result = array('msg'=>'手机号码格式不正确!','code'=>500);
                        echo json_encode($result);exit;
                    }
                    if(!$record){
                        if(empty($phone_captcha)){
                            $arr =array('msg'=>'手机验证码不能为空!','code'=>500);
                            echo  json_encode($arr);
                            exit;
                        }

                    if(isset($_SESSION['num'])) {
                            $code = $_SESSION['num'];//获取的验证码
                    }

                    if($code!=$phone_captcha || empty($code)){
                            $arr =array('msg'=>'请输入正确的验证码!','code'=>500);
                            echo  json_encode($arr);
                            exit;
                    }


                    if(isset($_SESSION['time'])){
                        //判断时间是否大于60秒
                        if((strtotime($_SESSION['time'])+60)<time()) {//将获取的缓存时间转换成时间戳加上60秒后与当前时间比较，小于当前时间即为过期
                            //session_destroy();
                            $_SESSION['time'] = null;
                            $_SESSION['num'] = null;
    //                        session('time',null);
    //                        session('num',null);
                            $arr =array('msg'=>'验证码已过期,请重新获取!','code'=>500);
                            echo  json_encode($arr);
                            exit;
                        }
                    }
                    $result=$user->where('id',$unid)
                                 ->update(['phone' => $phone]);
                    if($result){
                            $arr =array('msg'=>'手机号修改成功,下次请用新手机进行登录!','code'=>200);
                            echo  json_encode($arr);
                            exit;
                         }else{
                            $arr =array('msg'=>'手机号修改失败,请重试!','code'=>500);
                            echo  json_encode($arr);
                            exit;
                        }
                }else{
                     $result = array('msg'=>'手机号已存在!','code'=>500);
                    echo json_encode($result);exit;
                }
                 


        
            }
        }

    /**
     * 图片上传方法
     * @return [type] [description]
     */
    public function upload($module='user',$use='img_photo')
    {

        // print_r($this->request->file('file'));exit;
        if($this->request->file('file')){
            $file = $this->request->file('file');
        }else{
             $arr =array('msg'=>'没有上传文件!','code'=>500);
                echo  json_encode($arr);
                            exit;
        }
        $module = $this->request->has('module') ? $this->request->param('module') : $module;//模块
        $web_config = Db::name('webconfig')->where('web','web')->find();
        $info = $file->validate(['size'=>$web_config['file_size']*1024,'ext'=>$web_config['file_type']])->rule('date')->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . $module . DS . $use);
        if($info) {
            //写入到附件表
            $data = [];
            $data['module'] = $module;
            $data['filename'] = $info->getFilename();//文件名
            $data['filepath'] =DS.'tplay'. DS . 'public' . DS .'uploads' . DS . $module . DS . $use . DS . $info->getSaveName();//文件路径
            $data['fileext'] = $info->getExtension();//文件后缀
            $data['filesize'] = $info->getSize();//文件大小
            $data['create_time'] = time();//时间
            $data['uploadip'] = $this->request->ip();//IP
            $data['user_id'] =  11;//session获取
            // if($data['module'] = 'admin') {
            //     //通过后台上传的文件直接审核通过
            //     $data['status'] = 1;
            //     $data['admin_id'] = $data['user_id'];
            //     $data['audit_time'] = time();
            // }

            $data['use'] = $this->request->has('use') ? $this->request->param('use') : $use;//用处
            $res['id'] = Db::name('attachment')->insertGetId($data);

            $res['src'] = DS.'tplay'.DS . 'public' . DS . 'uploads' . DS . $module . DS . $use . DS . $info->getSaveName();

            $res['code'] = 200;
            //更新用户表头像
            $result = Db::name('Hk_user')->where('id',$data['user_id'])->update(['headimg'=>$res['src']]);
            if($result){
                    $arr =array(
                        'msg' =>'上传头像成功!',
                        'code'=>200,
                        'src' =>$res['src']
                    );
                    echo  json_encode($arr);
                    exit;
            }else{
                    $arr =array(
                        'msg' =>'上传头像失败!',
                        'code'=>500,
                    );
                    echo  json_encode($arr);
                    exit;
            }

            // addlog($res['id']);//记录日志
            // return json($res);
        } else {
            // 上传失败获取错误信息
            $arr =array('msg'=>'上传失败：'.$file->getError(),'code'=>200);
                            echo  json_encode($arr);
                            exit;
        }
    }

}
