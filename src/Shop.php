<?php
// +---------------------------------------------------------------------+
// | OneBase    | [ WE CAN DO IT JUST THINK ]                            |
// +---------------------------------------------------------------------+
// | Licensed   | http://www.apache.org/licenses/LICENSE-2.0 )           |
// +---------------------------------------------------------------------+
// | Author     | Wxs <>                                                 |
// +---------------------------------------------------------------------+
// | Repository |                                                        |
// +---------------------------------------------------------------------+

namespace app\api\controller;

use app\common\controller\ControllerBase;
use think\Db;
use think\Request;

/**
 * 测试接口控制器
 */



/**
 * 功能函数
 */
/**
 * xml转array
 * @param  string $xml
 * @return array  $arr 
 */
function xml_to_array($xml){
    $reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
    if(preg_match_all($reg, $xml, $matches)){
        $count = count($matches[0]);
        for($i = 0; $i < $count; $i++){
        $subxml= $matches[2][$i];
        $key = $matches[1][$i];
            if(preg_match( $reg, $subxml )){
                $arr[$key] = xml_to_array( $subxml );
            }else{
                $arr[$key] = $subxml;
            }
        }
    }
    return $arr;
}
/**
 * curl模拟请求
 * @param string $curlPost    	请求参数
 * @param string $url 			请求链接
 * @return 页面执行结果 
 */
function http($curlPost,$url){
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_NOBODY, true);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
	$return_str = curl_exec($curl);
	curl_close($curl);
	return $return_str;
}
/**
 * 添加请求记录
 * @param   array   $requset
 * @return  int     $return
 */
function addRequest($request) {
    if(!empty($_SERVER['SERVER_ADDR']))
                $request['server_ip'] = $_SERVER['SERVER_ADDR'];
        $result = shell_exec("/sbin/ifconfig");
        if(preg_match_all("/addr:(\d+\.\d+\.\d+\.\d+)/", $result, $match) !== 0){
                foreach($match[0] as $k=>$v){
                        if($match[1][$k] != "127.0.0.1")
                                $request['server_ip'] = $match[1][$k];
                }
        }
    $result = Db::name('request_log')->insert($request);
    if ($result) {
        $return = 1;
    } else {
        $return = -1;
    }
    return $return;
}

class Shop extends ControllerBase
{

    // Made by wxs in 2018-10-24 17:00
    // 注册和修改密码接口
    public function register()
    {
        $mobile   = input('post.mobile');
        $get_code = input('post.code');
        $password = input('post.password');
        $name     = input('post.name');

        $result = array(
            'status' => '0',
            'msg'    => '请输入用户名',
            'data'   => array(),
            'made'   => 'register'
        );

        $map_code['mobile'] = $mobile;
        $map_code['expire'] = array('gt',time());
        $map_code['type'] = 1;
        $codeinfo = Db::name('mobile_code')->where($map_code)->order('id desc')->limit(1)->select();

        if($codeinfo){
            $code = $codeinfo['0']['code'];
        }else{
            $code = null;
        }
                
        if($code == $get_code)
        {
            Db::name('mobile_code')->where($map_code)->setField('type',-1);
            $data['mobile'] = $mobile;
            $data['name'] = $name;
            $data['membertype'] = 222;
            $data['status'] = 1;
            $encrypt = rand(100000,999999);
            $data['password'] = data_md5_key($password,$encrypt);
            $data['encrypt'] = $encrypt;
            $data['regtime'] = date('Y-m-d H:i:s',time());
            $data['regip'] = $this->request->ip();
            $ret = Db::name('member')->insert($data);
            if($ret){
                $result['status'] = 1;
                $result['msg'] = '用户注册成功';
                $result['data'] = $data;
            }
            else{
                $result['status'] = -1;
                $result['msg'] = '用户注册失败，请稍后再试';
            }
        }
        else{
            $result['status'] = -10;
            $result['msg'] = '验证码错误或已过期，请重新申请短信验证码';
        }

        $request['model'] = 'register';
        $request['data'] = json_encode(input());
        $request['return_string'] = json_encode($result);
        addRequest($request);

        return json($result);
    }// register end
    // 修改个人信息
    public function update_Ident()
    {
        $result = array(
            'status' => '0',
            'msg'    => '请输入用户名和密码',
            'data'   => array(),
            'made'   => 'update_Ident'
        );

        $mobile = input('post.mobile');
        $password = input('post.password');
        $name = input('post.name');

        $map['mobile'] = $mobile;
        $map['status'] = 1;
        $userinfo = Db::name('member')->where($map)->find();
        if(!$userinfo){
            $result['msg'] = '用户不存在，请前往注册';
        }
        else{
            $encrypt = rand(100000,999999);
            $data['password'] = data_md5_key($password,$encrypt);
            $data['encrypt'] = $encrypt;
            $data['name'] = $name;
            $ret = Db::name('member')->where($map)->update($data);
            if($ret){
                $result['status'] = 1;
                $result['msg'] = '修改成功';
                $result['data'] = $data;
            }
            else{
                $result['status'] = -1;
                $result['msg'] = '修改失败，请稍后重试';
            }
        }

        $request['model'] = 'update_Ident';
        $request['data'] = json_encode(input());
        $request['return_string'] = json_encode($result);
        addRequest($request);

        return json($result);
    }
    // 登录
    public function login()
    {
        $mobile   = input('post.mobile');
        $password = input('post.password');
        $get_code = input('post.code');
        $token    = input('post.token');

        $result = array(
            'status' => 0,
            'msg'    =>'请输入用户名和密码',
            'data'   =>array(),
            'made'   => 'login'
        );
        $data_login['date']     = time();
        $data_login['currtime'] = date('Y-m-d H:i:s',$data_login['date']);

        // 密码不为空，用密码登录
        if($password){
            $pattern = "/^1\d{10}$/";
            if (preg_match($pattern, $mobile) || strlen($username) == 14 || strlen($username) == 15)
            {
                $userinfo = Db::name('member')->where("mobile = $mobile")->find();
                $encrypt = $userinfo['encrypt'];
                if($userinfo['password'] == data_md5_key($password,$encrypt))
                {// 密码正确
                    $loginip = $this->request->ip();
                    $logintime = $data_login['currtime'];
                    Db::name('member')->where('mobile',$mobile)->setField(array('lastloginip'=>$loginip,'lastlogintime'=>$logintime));
                    
                    $login['token'] = md5($mobile.$data_login['currtime']);
                    $login['ip'] = $this->request->ip();
                    $login['userid'] = $mobile;
                    $login['createtime'] = $data_login['currtime'];
                    $login['isvalid'] = 1;
                    Db::name('token')->insert($login);
                    $ret_token = $login['token'];
                    if($ret_token)
                    {
                        $result['status'] = 1;
                        $result['msg'] = '登录成功';
                        $result['data']['token'] = $ret_token;
                        $result['data']['mobile'] = $mobile;
                        $user = Db::name('member')->where('mobile',$mobile)->field('name,id,membertype')->find();
                        $result['data']['name'] = $user['name'];
                        $result['data']['ownerid'] = $user['id'];
                        $result['data']['membertype'] = $user['membertype'];
                        $ownerid = $user['id'];
                        $shop = Db::name('shop_copy')->where("ownerid = $ownerid and status = 1")->select();
                        if($shop){
                            for($i = 0; $i < count($shop); $i++)
                            {
                                $result['data']['shopinfo']["$i"]['shopid'] = $shop["$i"]['id'];
                                $result['data']['shopinfo']["$i"]['shopname'] = $shop["$i"]['name'];
                            }
                        }
                    }
                    else{
                        $result['status'] = -6;
                        $result['msg'] = 'token生成失败，请稍后重试';
                    }
                }//密码正确
                else{
                    $result['status'] = -5;
                    $result['msg'] = '密码错误请重试';
                }
            }
            else{
                $result['status'] = -9;
                $result['msg'] = '请输入正确的手机号';
            }
        }//密码登录结束
        else if($token)
        {// token免密码登录
            $pattern = "/^1\d{10}$/";
            if (preg_match($pattern, $mobile) || strlen($username) == 14 || strlen($username) == 15)
            {
                $map['userid'] = $mobile;
                $map['isvalid'] = 1;
                $token_info = Db::name('token')->where($map)->find();
                Db::name('token')->where($map)->setField('isvalid',0);
                // 少了登录超时
                if($token == $token_info['token'])
                {
                    $loginip = $this->request->ip();
                    $logintime = $data_login['currtime'];
                    Db::name('member')->where('mobile',$mobile)->setField(array('lastloginip'=>$loginip,'lastlogintime'=>$logintime));
                    $login['token'] = md5($mobile.$data_login['currtime']);
                    $login['ip'] = $this->request->ip();
                    $login['userid'] = $mobile;
                    $login['createtime'] = $data_login['currtime'];
                    $login['isvalid'] = 1;
                    Db::name('token')->insert($login);
                    $ret_token = $login['token'];
                    if($ret_token)
                    {
                        $result['status'] = 1;
                        $result['msg'] = '登录成功';
                        $result['data']['token'] = $ret_token;
                        $result['data']['mobile'] = $mobile;
                        $user = Db::name('member')->where('mobile',$mobile)->field('name,id,membertype')->find();
                        $result['data']['name'] = $user['name'];
                        $result['data']['ownerid'] = $user['id'];
                        $result['data']['membertype'] = $user['membertype'];
                        $ownerid = $user['id'];
                        $shop = Db::name('shop_copy')->where("ownerid = $ownerid and status = 1")->select();
                        if($shop){
                            for($i = 0; $i < count($shop); $i++)
                            {
                                $result['data']['shopinfo']["$i"]['shopid'] = $shop["$i"]['id'];
                                $result['data']['shopinfo']["$i"]['shopname'] = $shop["$i"]['name'];
                            }
                        }
                    }
                    else{
                        $result['status'] = -6;
                        $result['msg'] = 'token生成失败，请稍后重试';
                    }
                }
                else{
                    $result['status'] = -7;
                    $result['msg'] = '无效登录，请重新登录';
                }
            }
            else{
                $result['status'] = -9;
                $result['msg'] = '请输入正确的手机号';
            }
        }//token登录结束
        else if($get_code)
        {//验证码登录
            $map_code['mobile'] = $mobile;
            $map_code['expire'] = array('gt',time());
            $map_code['type'] = 2;
            $codeinfo = Db::name('mobile_code')->where($map_code)->order('id desc')->limit(1)->select();
            if($codeinfo){
                $code = $codeinfo['0']['code'];
            }else{
                $code = null;
            }
            if($code == $get_code)
            {
                $loginip = $this->request->ip();
                $logintime = $data_login['currtime'];
                Db::name('member')->where('mobile',$mobile)->setField(array('lastloginip'=>$loginip,'lastlogintime'=>$logintime));
                Db::name('mobile_code')->where($map_code)->setField('type',-2);
                $login['token'] = md5($mobile.$data_login['currtime']);
                $login['ip'] = $this->request->ip();
                $login['userid'] = $mobile;
                $login['createtime'] = $data_login['currtime'];
                $login['isvalid'] = 1;
                Db::name('token')->insert($login);
                $ret_token = $login['token'];
                if($ret_token)
                {
                    $result['status'] = 1;
                    $result['msg'] = '登录成功';
                    $result['data']['token'] = $ret_token;
                    $result['data']['mobile'] = $mobile;
                    $user = Db::name('member')->where('mobile',$mobile)->field('name,id,membertype')->find();
                    $result['data']['name'] = $user['name'];
                    $result['data']['ownerid'] = $user['id'];
                    $result['data']['membertype'] = $user['membertype'];
                    $ownerid = $user['id'];
                    $shop = Db::name('shop_copy')->where("ownerid = $ownerid and status = 1")->select();
                    if($shop){
                        for($i = 0; $i < count($shop); $i++)
                        {
                            $result['data']['shopinfo']["$i"]['shopid'] = $shop["$i"]['id'];
                            $result['data']['shopinfo']["$i"]['shopname'] = $shop["$i"]['name'];
                        }
                    }
                }
                else{
                    $result['status'] = -6;
                    $result['msg'] = 'token生成失败，请稍后重试';
                }
            }
            else{
                $result['status'] = -10;
                $result['msg'] = '验证码错误或已过期，请重新申请短信验证码';
            }
        }//验证码登录结束
        else{
            $result = array(
                'status'=>0,
                'msg'=>'无效登录请求，请确认您输入的账号密码',
                'data'=>array()
            );
        }
        $request['model'] = 'login';
        $request['data'] = json_encode(input());
        $request['return_string'] = json_encode($result);
        addRequest($request);
        return json($result);
    }// login结束
    // made by wxs at 2018-10-25 14:00
    // 登出
    public function logout()
    {
        $token = input('post.token');
        $map['token'] = $token;
        $map['isvalid'] = 1;
        Db::name('token')->where($map)->setField('isvalid',0);
        //要返回的数据
        $result = array(
            'status' => 1,
            'msg'    => '退出登录成功',
            'made'   => 'logout'
        );
        return json($result);
    }
    //手机验证码 验证码类型：1注册 2登录//手机验证码功能
    public function mobile_code()
    {
        $return = array(
            'status' => 0,
            'msg'    => '参数非法',
            'data'   => array(),
            'made'   => 'mobile_code'
        );
        $type   = input('post.type');
        $mobile = input('post.mobile');
        $pattern = "/^1\d{10}$/";
        if(preg_match($pattern,$mobile))
        {
            if($type == 1)
            {//注册
                $map['mobile'] = $mobile;
                $map['status'] = 1;
                $member = Db::name('member')->where($map)->find();
                // $market = M('market')->where($map)->find();
                if ($member){
                    $return['msg'] = '用户已存在，请前往登录';
                }
                else{
                    $mobile_code = rand(1000,9999);
                    $post_data = "account=cf_smsdianji&password=dianji8386&mobile=".$mobile."&content=".rawurlencode("您的验证码是：".$mobile_code."。请不要把验证码泄露给其他人。");
                    $target = "http://106.ihuyi.cn/webservice/sms.php?method=Submit";
                    $gets =  xml_to_array(http($post_data, $target));
                    if ($gets['SubmitResult']['code'] == 2)
                    {
                        $data = array(
                            'mobile' => $mobile,
                            'code'   => $mobile_code,
                            'expire' => time() + 300,
                            'type'   => $type
                        );
                        Db::name('mobile_code')->insert($data);
                        $return['status'] = 1;
                        $return['msg'] = "验证码已发送，请在5分钟内输入";
                    }
                    else{
                        $return['status'] = -1;
                        $return['msg'] = "验证码短信系统繁忙，请稍后再试";
                    }
                }
            }
            else if($type == 2){// 登录
                $map['mobile'] = $mobile;
                $map['status'] = 1;
                $member = Db::name('member')->where($map)->find();
                if ($member)
                {
                    $mobile_code = rand(1000,9999);
                    $post_data = "account=cf_smsdianji&password=dianji8386&mobile=".$mobile."&content=".rawurlencode("您的验证码是：".$mobile_code."。请不要把验证码泄露给其他人。");
                    $target = "http://106.ihuyi.cn/webservice/sms.php?method=Submit";
                    $gets =  xml_to_array(http($post_data,$target));
                    if ($gets['SubmitResult']['code'] == 2)
                    {
                        $data = array(
                            'mobile' => $mobile,
                            'code'   => $mobile_code,
                            'expire' => time() + 300,
                            'type'   => $type
                        );
                        Db::name('mobile_code')->insert($data);
                        $return['status'] = 1;
                        $return['msg'] = "验证码已发送，请在5分钟内输入";
                    }
                    else{
                        $return['status'] = -1;
                        $return['msg'] = "验证码短信系统繁忙，请稍后再试";
                    }
                }
                else{
                    $return['msg'] = '用户不存在，请先注册！';
                }
            }
        }
        else{
            $return['msg'] = '请输入正确的手机号';
        }
        $request['model'] = 'mobile_code';
        $request['data'] = json_encode(input());
        $request['return_string'] = json_encode($return);
        addRequest($request);
        return json($return);
    }

    public function get_type()
    {
        $shoptype = Db::name('shoptype')->where('status = 1')->field('id,name')->select();
        $result['status'] = 1;
        $result['msg'] = '获取成功';
        $result['data'] = $shoptype;
        $result['made'] = 'get_type';
        return json($result);
    }
    // made by wxs at 2018-10-25
    //商户开店
    public function openShop()
    {
        $mobile     = input('post.mobile');
        $shopname   = input('post.name');
        $shoptypeid = input('post.type');
        $pcbid      = input('post.pcbid');
        $result = array(
            'status' => 0,
            'msg'    => '参数非法',
            'data'   => array(),
            'made'   => 'openShop'
        );
        if($mobile == null || $pcbid == null || $shoptypeid == null)
        {
            $result['status'] = -1;
            $result['msg'] = '参数错误';
            return json($result);
        }
        $pcb = Db::name('device_copy')->where("pcbid = '$pcbid' and status = 1")->find();
        if(empty($pcb))
        {
            $result['status'] = -5;
            $result['msg'] = '未找到该秤';
            return json($result);
        }
        
        $member = Db::name('member')->where("mobile = $mobile and status = 1")->find();
        $shopid = $pcb['shopid'];
        $shop = Db::name('shop_copy')->where("id = $shopid and status = 1")->find();
        // 该秤激活时创建的店铺没有主人
        if(!$shop['ownerid'])
        {
            $map['name'] = $shopname;
            $map['ownerid'] = $member['id'];
            $map['typeid'] = $shoptypeid;
            $ret = Db::name('shop_copy')->where("id = $shopid and status = 1")->update($map);
            $shop['name'] = $shopname;
            $shop['ownerid'] = $member['id'];
            if($ret)
            {
                $plu = 1;
                $goods = Db::name('goods_stdlist')->where("shoptypeid = $shoptypeid")->select();
                for($i = 0; $i < count($goods); $i++)
                {
                    $data["$i"]['stdname'] = $goods["$i"]['name'];
                    $data["$i"]['shopid']  = $shopid;
                    $data["$i"]['price']   = $goods["$i"]['price'];
                    $data["$i"]['plu']     = $plu;
                    $data["$i"]['status']  = 1;
                    $data["$i"]['measingmethod'] = 1;
                    $plu++;
                }
                Db::name('shopgoods_copy')->insertAll($data);
                $result['status'] = 1;
                $result['msg'] = '开店成功';
                $result['data'] = $shop;
            }
            else{
                $result['status'] = -1;
                $result['msg'] = '开店失败，请稍后重试';
            }
        }
        else{// 该秤创建的店铺已有主人
            $result['status'] = -5;
            $result['msg'] = '该秤已绑定店铺';
        }
        $request['model'] = 'openShop';
        $request['data'] = json_encode(input());
        $request['return_string'] = json_encode($result);
        addRequest($request);
        return json($result);
    }

    public function edit_shopname()
    {
        $result = array(
            'status' => 0,
            'msg'    => '参数非法',
            'made'   => 'edit_shopname'
        );
        $shopid = input('post.shopid');
        $shopname = input('post.shopname');
        $ret = Db::name('shop_copy')->where("id = '$shopid'")->setField('name',$shopname);
        if($ret){
            $result['status'] = 1;
            $result['msg'] = '修改成功';
        }else{
            $result['status'] = -1;
            $result['msg'] = '修改失败，请重试';
        }
        $request['model'] = 'edit_shopname';
        $request['data'] = json_encode(input());
        $request['return_string'] = json_encode($result);
        addRequest($request);
        return json($result);
    }
    // made by wxs at 2018-10-20
    // 关闭店铺
    public function closeShop()
    {
        $shopid = input('post.shopid');
        $result = array(
            'status' => 0,
            'msg'    => '参数非法',
            'made'   => 'closeShop'
        );
        $map1['id'] = $shopid;
        $ret = Db::name('shop_copy')->where($map1)->setField(array('status'=>0));
        if($ret){
            $result['status'] = 1;
            $result['msg'] = '删除成功';

            $map2['shopid'] = $shopid;
            $map2['status'] = 1;
            $ret2 = Db::name( 'shopgoods_copy' )->where($map2)->setField('status', 0);

            $device = Db::name('device_copy')->where($map2)->select();
            $shop = Db::name('shop_copy')->order('id desc')->limit(1)->select();
            $shopid = $shop['0']['id'];
            for($i = 0; $i < count($device); $i++)
            {
                $shopid++;
                $deviceid = $device["$i"]['id'];
                Db::name('device_copy')->where("id = $deviceid")->setField(array('shopid'=>$shopid));
                
                $saveMap["$i"]['typeid'] = -1;
                $saveMap["$i"]['status'] = 1;
            }
            Db::name('shop_copy')->insertAll($saveMap);

        }else{
            $result['status'] = -1;
            $result['msg'] = '删除失败，请重试';
        }
        $request['model'] = 'closeShop';
        $request['data'] = json_encode(input());
        $request['return_string'] = json_encode($result);
        addRequest($request);
        return json($result);
    }

    // 返回店铺中的所有秤
    public function get_devices()
    {
        $result = array(
            'status' => 0,
            'msg'    => '初始化',
            'data'   => array(),
            'made'   => 'get_devices'
        );
        $shopid = input('post.shopid');
        $device = Db::name('device_copy')->where("shopid = $shopid and status = 1")->field('id,pcbid')->select();
        if($device){
            $result['status'] = 1;
            $result['msg'] = '查询成功';
            $result['data'] = $device;
        }
        else if($device == null){
            $result['status'] = 0;
            $result['msg'] = '未找到数据';
        }
        else{
            $result['status'] = -1;
            $result['msg'] = '查询出错';
        }
        $request['model'] = 'get_devices';
        $request['data'] = json_encode(input());
        $request['return_string'] = json_encode($result);
        addRequest($request);
        return json($result);
    }
    // made by wxs at 2018-10-20
    // 秤绑定店铺
    public function bindShop()
    {
        $result = array(
            'status' => 0,
            'msg'    => '初始化',
            'made'   => 'bindShop'
        );
        $shopid = input('post.shopid');
        $pcbid = input('post.pcbid');
        $device = Db::name('device_copy')->where("pcbid = '$pcbid' and status = 1")->find();
        if(!$device){
            $result['status'] = -10;
            $result['msg'] = '该秤不存在，请重试';
            return json($result);
        }
        $map1['id'] = $device['shopid'];
        $map1['status'] = 1;
        $shop = Db::name('shop_copy')->where($map1)->find();
        if($shop['ownerid'] == null)
        {
            $ret = Db::name('device_copy')->where("pcbid = '$pcbid' and status = 1")->setField('shopid',$shopid);
            if($ret){
                $result['status'] = 1;
                $result['msg']    = '绑定成功';
            }
            else{
                $result['status'] = -1;
                $result['msg']    = '绑定失败，请重试';
            }
        }
        else{
            $result['status'] = -5;
            $result['msg'] = '该秤已绑定店铺';
        }
        $request['model'] = 'bindShop';
        $request['data'] = json_encode(input());
        $request['return_string'] = json_encode($result);
        addRequest($request);
        return json($result);
    }

    // made by wxs at 2018-10-19
    // 解除秤的绑定
    public function deviceDel()
    {
        $result = array(
            'status' => 0,
            'msg'    => '初始化',
            'made'   => 'deviceDel'
        );
        $deviceid = input('post.deviceid');
        
        $shop = Db::name('shop_copy')->order('id desc')->limit(1)->select();
        $shopid = $shop['0']['id'] + 1;
        $ret = Db::name('device_copy')->where("id = $deviceid")->setField(array('shopid'=>$shopid));

        if($ret){
            $result['status'] = 1;
            $result['msg']    = '解除绑定成功';
            $saveMap['typeid'] = -1;
            $saveMap['status'] = 1;
            Db::name('shop_copy')->insert($saveMap);
        }
        else{
            $result['status'] = -1;
            $result['msg']    = '解除绑定失败，请重试';
        }
        $request['model'] = 'deviceDel';
        $request['data'] = json_encode(input());
        $request['return_string'] = json_encode($result);
        addRequest($request);
        return json($result);
    }
    // 添加货物
    public function add_goods()
    {
        $result = array(
            'status' => 0,
            'msg'    => '初始化',
            'data'   => array(),
            'made'   => 'add_goods'
        );
        $stdname = input('post.name');
        $shopid = input('post.shopid');
        $price = input('post.price');
        $map['shopid']          = $shopid;
        $map['stdname']         = $stdname;
        $map['status']          = 1;
        $ret = Db::name('shopgoods_copy')->where($map)->field('id,stdname,shopid,price,plu')->find();
        if($ret){
            $result['status'] = -5;
            $result['msg'] = $stdname . '已存在';
            $result['data'] = $ret;
            return json($result);
        }
        $map['price']           = $price;
        //设置plu
        $data = Db::name('shopgoods_copy')->where("shopid = $shopid and status = 1")->order('plu desc')->find();
        if($data == null){
            $plu = 1;
        }
        else{
            $plu = $data['plu']+1;
        }
        $map['plu'] = $plu;
        $map['id'] = Db::name('shopgoods_copy')->insertGetId($map);
        if($map['id'] != null)
        {
            $goods['id'] = $map['id'];
            $goods['stdname'] = $map['stdname'];
            $goods['price'] = $map['price'];
            $goods['plu'] = $map['plu'];
            $result['status'] = 1;
            $result['msg'] = $stdname . '添加完毕';
            $result['data'] = $goods;
        }
        else{
            $result['status'] = -1;
            $result['msg'] = $stdname . '添加失败，请稍后重试';
        }
        $request['model'] = 'add_goods';
        $request['data'] = json_encode(input());
        $request['return_string'] = json_encode($result);
        addRequest($request);
        return json($result);
    }
    // 返回货物信息
    public function get_goods()
    {
        $result = array(
            'status' => 0,
            'msg'    => '初始化',
            'data'   => array(),
            'made'   => 'get_goods'
        );
        $shopid = input('post.shopid');
        $map['shopid'] = $shopid;
        $map['status'] = 1;
        $goods = Db::name('shopgoods_copy')->where($map)->field('id,stdname,price,plu')->select();
        if($goods){
            $result['status'] = 1;
            $result['msg'] = '货物获取成功';
            $result['data'] = $goods;
        }
        else if($goods == null){
            $result['status'] = -5;
            $result['msg'] = '该店铺货物为空';
        }
        else{
            $result['status'] = -1;
            $result['msg'] = '货物获取失败';
        }
        $request['model'] = 'get_goods';
        $request['data'] = json_encode(input());
        $request['return_string'] = json_encode($result);
        addRequest($request);
        return json($result);
    }

    // 删除货物
    public function del_goods()
    {
        $result = array(
            'status' => 0,
            'msg'    => '初始化',
            'made'   => 'del_goods'
        );
        $name = input('post.data');
        $shopid = input('post.shopid');
        $name = explode(",",$name);
        $map['shopid'] = $shopid;
        $map['status'] = 1;
        foreach($name as $key => $value)
        {
            $map['stdname'] = $value;
            $ret = Db::name('shopgoods_copy')->where($map)->setField('status',0);
            if ($ret) {
                $result['status'] = 1;
                $result['msg']    = '删除成功';
            } else {
                    $result['status'] = -2;
                    $result['msg']    = '数据写入失败，请稍后重试';
            }
        }
        $request['model'] = 'del_goods';
        $request['data'] = json_encode(input());
        $request['return_string'] = json_encode($result);
        addRequest($request);
        return json($result);
    }

    // 修改货物信息
    public function update_goods()
    {
        $result = array(
            'status' => 0,
            'msg'    => '初始化',
            'made'   => 'update_goods'
        );
        $shopid  = input('post.shopid');
        $goodsid = input('post.id');
        $stdname = input('post.name');
        $price   = input('post.price');
        $type    = input('post.type',1);
        $map['shopid'] = $shopid;
        $map['id'] = $goodsid;
        $map['status'] = 1;
        if($type == 2)
        {
            $ret = Db::name('shopgoods_copy')->where("shopid = $shopid and stdname = '$stdname' and status = 1")->find();
            if($ret)
            {
                $result['status'] = -5;
                $result['msg'] = $stdname . '已存在';
                $request['model'] = 'update_goods';
                $request['data'] = json_encode(input());
                $request['return_string'] = json_encode($result);
                addRequest($request);
                return json($result);
            }
            $saveMap['stdname'] = $stdname;
        }
        $saveMap['price'] = $price;
        $ret = Db::name('shopgoods_copy')->where($map)->update($saveMap);
        if($ret){
            $result['status'] = 1;
            $result['msg'] = '修改成功';
            $result['data'] = Db::name('shopgoods_copy')->where($map)->find();
        }
        else{
            $result['status'] = -1;
            $result['msg'] = '修改失败';
        }
        $request['model'] = 'update_goods';
        $request['data'] = json_encode(input());
        $request['return_string'] = json_encode($result);
        addRequest($request);
        return json($result);
    }
    // 设置PLU
    public function setPlu()
    {
        $result = array(
            'status' => 0,
            'msg'    => '初始化',
            'made'   => 'setPlu'
        );
        $shopid = input('post.shopid');
        $plu = input('post.plu');
        $stdname = input('post.name');

        $map['shopid'] = $shopid;
        $map['stdname'] = $stdname;
        $map['status'] = 1;
        $data = Db::name('shopgoods_copy')->where("shopid = $shopid and plu = $plu and status = 1")->find();
        if($data){
            $result['status'] = -5;
            $result['msg'] = '该PLU已绑定' . $data['stdname'];
            return json($result);
        }
        $ret = Db::name('shopgoods_copy')->where($map)->setField('plu',$plu);
        if($ret){
            $result['status'] = 1;
            $result['msg'] = '修改成功';
        }
        else{
            $result['status'] = -1;
            $result['msg'] = '修改失败';
        }
        $request['model'] = 'setPlu';
        $request['data'] = json_encode(input());
        $request['return_string'] = json_encode($result);
        addRequest($request);
        return json($result);
    }
    // plu解除绑定
    public function del_plu()
    {
        $result = array(
            'status' => 0,
            'msg'    => '初始化',
            'made'   => 'del_plu'
        );
        $shopid = input('post.shopid');
        $plu = input('post.plu');
        $stdname = input('post.name');

        $map['shopid'] = $shopid;
        $map['stdname'] = $stdname;
        $map['status'] = 1;
        $ret = Db::name('shopgoods_copy')->where($map)->setField('plu',-1);
        if($ret){
            $result['status'] = 1;
            $result['msg'] = '解绑成功';
        }
        else{
            $result['status'] = -1;
            $result['msg'] = '解绑失败';
        }
        $request['model'] = 'del_plu';
        $request['data'] = json_encode(input());
        $request['return_string'] = json_encode($result);
        addRequest($request);
        return json($result);
    }
    // 返回未绑定plu的货物的信息
    public function noplu_goods()
    {
        $result = array(
            'status' => 0,
            'msg'    => '初始化',
            'data'   => array(),
            'made'   => 'noplu_goods'
        );
        $shopid = input('post.shopid');
        $map['shopid'] = $shopid;
        $map['status'] = 1;
        $map['plu'] = -1;
        $goods = Db::name('shopgoods_copy')->where($map)->field('id,stdname,price,plu')->select();
        if($goods)
        {
            $result['status'] = 1;
            $result['msg'] = '货物获取成功';
            $result['data'] = $goods;
        }
        else if($goods == null)
        {
            $result['status'] = -5;
            $result['msg'] = '该店铺货物plu均以绑定或店铺货物为空';
        }
        else
        {
            $result['status'] = -1;
            $result['msg'] = '货物获取失败';
        }
        $req['shopid'] = $shopid;
        $request['model'] = 'noplu_goods';
        $request['data'] = json_encode($req);
        $request['return_string'] = json_encode($result);
        addRequest($request);
        return json($result);
    }
    // 重置plu
    public function ResetPlu()
    {
        $result = array(
            'status' => 0,
            'msg'    => '初始化',
            'made'   => 'ResetPlu'
        );
        $shopid = input('post.shopid');
        $map['shopid'] = $shopid;
        $map['status'] = 1;
        $ret = Db::name('shopgoods_copy')->where($map)->setField('plu',-1);
        if($ret){
            $result['status'] = 1;
            $result['msg'] = 'PLU重置成功';
        }
        else{
            $result['status'] = -1;
            $result['msg'] = 'PLU重置失败';
        }
        $request['model'] = 'ResetPlu';
        $request['data'] = json_encode(input());
        $request['return_string'] = json_encode($result);
        addRequest($request);
        return json($result);
    }
    // 自动排序
    public function sort()
    {
        $result = array(
            'status' => 0,
            'msg'    => '初始化',
            'made'   => 'sort'
        );
        $shopid = input('post.shopid');
        $data = Db::name('shopgoods_copy')->where("shopid = $shopid and status = 1")->select();
        if($data == null){
            $result['status'] = -5;
            $result['msg'] = '该店铺货物为空';
        }
        else if($data == false){
            $result['status'] = -1;
            $result['msg'] = '查询数据库出错，请稍后重试';
        }
        else{
            $plu = 1;
            for($i = 0; $i < count($data); $i++)
            {
                $id = $data["$i"]['id'];
                $map['plu'] = $plu;
                $plu++;
                $ret["$i"] = Db::name('shopgoods_copy')->where("id = $id and status = 1")->update($map);
            }
            if(count($ret) == count($data)){
                $result['status'] = 1;
                $result['msg'] = 'PLU设置成功';
            }
            else{
                $result['status'] = -1;
                $result['msg'] = 'PLU设置失败';
            }
        }
        $request['model'] = 'plu_sort';
        $request['data'] = json_encode(input());
        $request['return_string'] = json_encode($result);
        addRequest($request);

        return json($result);
    }
    //一段时间内店铺的销售统计——订单详情
    public function vue_getShopSales()
    {
        $res = array(
            'status' => 0,
            'msg'    => '初始化',
            'data'   => null,
            'made'   => 'VUE_getShopSales'
        );
        //任意时间内的销售统计数据情况
        $daytype = input('post.daytype',0);
        $startday = input('post.sday');
        $endday = input('post.eday');

        if ($startday && $endday)
        {
            $d1 = strtotime($startday);
            $d2 = strtotime($endday);
            if ( $d2 < $d1 )
            {
                $tmp = $d2;
                $d2 = $d1;
                $d1 = $tmp;
            }
            if(round(($d2-$d1)/3600/24)>=30)
            {
                $d2 = $d1 + 30*24*3600;
            }
            $startday = date('Y-m-d',$d1);
            $begin = $startday . " 00:00:00";
            $endday   = date('Y-m-d',$d2);
            $end = $endday . " 23:59:59";
        } 
        else if ($daytype > 0)
        {
            if ( $daytype == 3 ){
                $i = 30;
            }
            else if ( $daytype == 2 ) {
                $i = 7;
            }
            else{
                $i = 1;
            }
            $startday = date('Y-m-d', (time() - $i * 24 * 3600));
            $begin = $startday . " 00:00:00";
            $endday = date('Y-m-d',time());
            $end = $endday . " 23:59:59";
        }
        else
        {
            $startday = date('Y-m-d', time());
            $begin = $startday . " 00:00:00";
            $endday = date('Y-m-d',time());
            $end = $endday . " 23:59:59";
        }
        $shopid = input('post.shopid');
        $map = [
            't_orderitem.intime'    => ['BETWEEN',[$begin,$end]],
            't_orderitem.shopid'    => $shopid,
        ];
        $result = db('orderitem')
                    ->field(['intime','goodid','name','SUM(number)'=>'weight','SUM(itemamount)'=>'income'])
                    ->where($map)
                    ->group('goodid')
                    ->order('income desc')
                    ->select();
        if($result){
            for($i = 0; $i < count($result); $i++)
            {
                $result["$i"]['weight'] = round($result["$i"]['weight'],2);
                $result["$i"]['income'] = round($result["$i"]['income'],2);
            }
            $res = [
                'status'  => 1,
                'msg'   => '操作成功',
                'data'  => $result,
            ];
        }
        else if($result == null){
            $res = [
                'status'  => -5,
                'msg'   => '暂无数据',
                'data'  => null,
            ];
        }
        else{
            $res = [
                'status'  => -1,
                'msg'   => '查找出错',
                'data'  => null,
            ];
        }
        $request['model'] = 'vue_getShopSales';
        $request['data'] = json_encode(input());
        $request['return_string'] = json_encode($res);
        addRequest($request);

        return json($res);
    }

    public function marketpayinfo()
    {
        $now    = date('Y-m-d', time());
        $endday = input('post.day',$now);
        $time   = time() - 5 * 24 * 3600;
        $beginday = date('Y-m-d',$time);
        $begin  = input('post.beginDate');
        $end    = input('post.endDate');

        if(!$begin)
            $begin  = $beginday . " 00:00:00";
        if(!$end)
            $end    = $endday . " 23:59:59";

        //任意时间内的市场交易数据情况
        $days_begin = array();
        $days_end   = array();
        $days       = array();

        $startday = $begin;
        $endday   = $end;

        if($startday && $endday){
            $d1 = strtotime($startday);
            $d2 = strtotime($endday);
            if($d2 < $d1){
                $tmp = $d2;
                $d2 = $d1;
                $d1 = $tmp;
            }
            $i = round(($d2 - $d1)/3600/24);
            for($j = 0; $i >= 0; $i--){
                $temp_day = date('Y-m-d', ($d2 - $i * 24 * 3600));
                $days["$j"] = date('Y-m-d', strtotime($temp_day));
                $days_begin["$j"] = $temp_day . " 00:00:00";
                $days_end["$j"] = $temp_day . " 23:59:59";
                $j++;
            }
        }
        else{
            if($daytype==3){
                $i=365;
            }else if($daytype==2){
                $i=30;
            }else{
                $i=7;
            }
            for ($j = 0; $i >= 0; $i--) {
                $temp_day = date('Y-m-d', (time() - $i * 24 * 3600));
                $days["$j"] = date('Y-m-d', strtotime($temp_day));
                $days_begin["$j"] = $temp_day . " 00:00:00";
                $days_end["$j"] = $temp_day . " 23:59:59";
                $j++;
            }
        }
        $begin = $days_begin['0'];
        $count = count($days) - 1;
        $end   = $days_end["$count"];
        $shopid = input('post.shopid');
        $map = [
            't_order.intime'    => ['BETWEEN',[$begin,$end]],
            't_order.status'    => 1,
            'shopid'            => $shopid,
            ];
        $query_data = db('order')->field(['date','SUM(totalamount)'=>'income','SUM(itemcount)'=>'goodnum'])
                        ->where($map)
                        ->group('date')
                        ->select();
        
        $result['0']['type'] = 'line';
        $result['1']['type'] = 'line';
        $result['0']['name'] = '金额/元';
        $result['1']['name'] = '订单数/笔';
        for ($i = 0; $i < count($days); $i++)
        {
            $result['0']['data']["$i"] = 0;
            $result['1']['data']["$i"] = 0;
            for($j = 0; $j < count($query_data); $j++)
            {
                if($days["$i"] == $query_data["$j"]['date'])
                {
                    $result['0']['data']["$i"] = round($query_data["$j"]['income'],2);
                    $result['1']['data']["$i"] = round($query_data["$j"]['goodnum'],2);
                }
            }
        }
        $return['status'] = 1;
        $return['msg'] = '成功';
        $return['data']['timedata'] = $days;
        $return['data']['data'] = $result;

        $request['model'] = 'vue_marketpayinfo';
        $request['data'] = json_encode(input());
        $request['return_string'] = json_encode($return);
        addRequest($request);

        return json($return);
    }

    // 返回未结账订单
    public function get_orders()
    {

        $result = array(
            'status' => 0,
            'msg'    => '初始化',
            'data'   => '',
            'made'   => 'get_orders'
        );

        $pcbid = input('post.pcbid');
        $shopid = Db::name('device')->where("pcbid = '$pcbid'")->value('shopid');

        if($pcbid=='test'){
          $orderitem[0]['orderid']=100;
          $orderitem[0]['itemcount'] = 1;
          $orderitem[0]['totalamount'] = 2.5;
          $orderitem[0]['goods'] = Db::name('orderitem')->where("id = 790")->select();

          $result['status'] = 1;
          $result['msg'] = '检索成功';
          $result['data'] = $orderitem;
          return json($result);
        }
        $begin = date('Y-m-d',time())." 00:00:00";
        $end = date('Y-m-d',time())." 23:59:59";
        $map1 = [
            'intime'    => ['BETWEEN',[$begin,$end]],
            'shopid'    => $shopid,
            'orderno'   => 0
        ];
        $order = Db::name('order')->where($map1)->select();
        if(!$order)
        {
            $result['status'] = -5;
            $result['msg'] = '未找到未结账订单';
            return json($result);
        }
        $map2 = [
            'intime'    => ['BETWEEN',[$begin,$end]],
            'shopid'    => $shopid,
        ];
        for($i = 0; $i < count($order); $i++)
        {
            $map2['orderid'] = $order["$i"]['id'];
            $orderitem["$i"]['orderid'] = $order["$i"]['id'];
            $orderitem["$i"]['itemcount'] = $order["$i"]['itemcount'];
            $orderitem["$i"]['totalamount'] = $order["$i"]['totalamount'];
            $orderitem["$i"]['goods'] = Db::name('orderitem')->where($map2)->select();
        }
        $result['status'] = 1;
        $result['msg'] = '检索成功';
        $result['data'] = $orderitem;

        $request['model'] = 'get_orders';
        $request['data'] = json_encode(input());
        $request['return_string'] = json_encode($result);
        addRequest($request);

        return json($result);
    }

    
}