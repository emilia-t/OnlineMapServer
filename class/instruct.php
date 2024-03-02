<?php
/**
 * Class instruct（指令）
 * 简介：
 * 指令是用于OMR、OME、OMS之间传递信息的统一通讯方式。
 * 功能：
 * 该指令类包含了对指令的检查功能以及创建指令的功能
 * 通过此类创建的指令在通过安全检查后将会返回指令的JSON字符或数组
 * 否则将返回false
 * 参数详解：
 * $packed 是否需要打包为json数据  (关闭打包后指令将返回源格式，后续需要自行打包)
 *  default : true
 * $checked 是否需要安全检查 （关闭安全检查后将指令不再返回false，可能会在出错后会导致程序崩溃）
 *  default : true
 */
class instruct
{
    private $packed=true;
    private $checked=true;
    public function __construct($packed,$checked){
        if(is_bool($packed)){
            $this->packed=$packed;
        }
        if(is_bool($checked)){
            $this->checked=$checked;
        }
    }
    private function turn($Array){
        if($this->packed===true){
            return json_encode($Array);
        }else{
            return $Array;
        }
    }
    /**检查函数ck**/
    //登录名称和密码验证(非匿名)
    function ckLogonAccount($email,$password){
        if(!is_string($email) || !is_string($password)){
            return false;
        }
        $pattern ="/[^a-zA-Z0-9_@.+\/=-]/";
        if (preg_match($pattern, $email.$password)) {
            return false;
        } else {
            return true;
        }
    }
    //匿名登录名称验证
    function ckAnonymousLogonAccount($accountName){
        if(!is_string($accountName)){
            return false;
        }
        $pattern = "/[^_A-Za-z0-9\x{4e00}-\x{9fa5}]/u";
        if (preg_match($pattern, $accountName)) {
            return false;
        } else {
            return true;
        }
    }
    /**非广播指令**/
    //匿名登录状态指令
    function anonymousLoginStatus($status){
        if($this->checked){
            if(!is_bool($status)){
                return false;
            }
        }
        return $this->turn([
            'type'=>'anonymousLoginStatus',
            'data'=>$status
        ]);
    }
    //登录状态指令
    function loginStatus($status){
        if($this->checked){
            if(!is_bool($status)){
                return false;
            }
        }
        return $this->turn([
            'type'=>'loginStatus',
            'data'=>$status
        ]);
    }
    //发送用户数据
    function send_userData($userData){
        if($this->checked){
            if(!is_array($userData)){return false;}
            if(!array_key_exists('user_email',$userData)){return false;}
            if(!is_string($userData['user_email'])){return false;}
            if(!array_key_exists('user_name',$userData)){return false;}
            if(!is_string($userData['user_name'])){return false;}
            if(!array_key_exists('head_color',$userData)){return false;}
            if(!is_string($userData['head_color'])){return false;}
            if(!array_key_exists('user_qq',$userData)){return false;}
            if(!is_string($userData['user_qq'])){return false;}
        }
        return $this->turn([
            'type'=>'send_userData',
            'data'=>$userData
        ]);
    }
    //传递在线用户指令
    function send_presence($arrayData){
        if($this->checked){
            if(!is_array($arrayData)){return false;}
            if(count($arrayData)===0){return false;}
        }
        $DATA=[];
        foreach ($arrayData as $item) {
            if(!is_array($item)){continue;}
            if(!array_key_exists('userEmail',$item)){continue;}
            if(!is_string($item['userEmail'])){continue;}
            if(!array_key_exists('userName',$item)){continue;}
            if(!is_string($item['userName'])){continue;}
            if(!array_key_exists('headColor',$item)){continue;}
            if(!is_string($item['headColor'])){continue;}
            if(!array_key_exists('userQq',$item)){continue;}
            if(!is_string($item['userQq'])){continue;}
            $data=[
                'userEmail'=>$item['userEmail'],
                'userName'=>$item['userName'],
                'headColor'=>$item['headColor'],
                'userQq'=>$item['userQq'],
            ];
            array_push($DATA,$data);
        }
        if($this->checked){
            if(count($DATA)===0){
                return false;
            }
        }
        return $this->turn([
            'type'=>'send_presence',
            'data'=>$DATA
        ]);
    }
    /**广播指令**/
    //广播指令-用户登录
    function broadcast_logIn($logUserData){
        if($this->checked){
            if(!is_array($logUserData)){return false;}
            if(!array_key_exists('user_email',$logUserData)){return false;}
            if(!is_string($logUserData['user_email'])){return false;}
            if(!array_key_exists('user_name',$logUserData)){return false;}
            if(!is_string($logUserData['user_name'])){return false;}
            if(!array_key_exists('head_color',$logUserData)){return false;}
            if(!is_string($logUserData['head_color'])){return false;}
            if(!array_key_exists('user_qq',$logUserData)){return false;}
            if(!is_string($logUserData['user_qq'])){return false;}
        }
        $userData=[
            'userEmail'=>$logUserData['user_email'],
            'userName'=>$logUserData['user_name'],
            'headColor'=>$logUserData['head_color'],
            'userQq'=>$logUserData['user_qq'],
        ];
        return $this->turn([
            'type'=>'broadcast',
            'class'=>'logIn',
            'data'=>$userData
        ]);
    }
}