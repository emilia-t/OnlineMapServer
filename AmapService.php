<?php
/**
<php-config>
**/
error_reporting(0);
/**
</php-config>
**/

/**
<external-program>
**/
use Workerman\Worker;
use Workerman\Timer;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once 'Workerman/Autoloader.php';
require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';
//数据库账号
require './config/Mysql_OZ4pTiFHZf.php';
//SMTP账号
require './config/SMTP_U4tqjLYxNw.php';
//获取公钥与私钥的API
require './api/Public_getPublickey.php';
//解密和加密功能
require './api/Other_RsaTranslate.php';
//登录验证功能
require './api/Public_LoginServer.php';
//查询用户数据
require './api/Public_GetUserData.php';
//查询地图数据
require './api/Public_GetMapData.php';
//通用数据检测工具
require './class/QualityInspectionRoom.php';
//json工具
require './class/JsonDisposalTool.php';
//创建数据库
require './api/Other_createMysqlDataBase.php';
/**
</external-program>
 **/

/**
<class>
**/
$newQIR=new QualityInspectionRoom(false);
$newJDT=new JsonDisposalTool();
/**
</class>
**/

/**
<data>
**/
define('HEARTBEAT_TIME', 120);
$theData=[
    "globalUid"=>0,
];
$theConfig=[
    "time"=>date('m-j'),
    "logFile"=>null,
    //             广播数据     服务端接收    客户端发送  服务端发送   服务端发送       客户端发送      服务端发送
    "typeList"=>['broadcast','get_publickey','login','publickey','loginStatus','get_userData','send_userData','get_mapData','send_mapData']

];
/**
</data>
**/

/**
<internal-procedures>
**/
//检测数据库
testDataBaseLink();
$theConfig['logFile']=fopen('./log/'.$theConfig['time'].'.txt','a+');
//与客户端连接
function handle_connection($connection){
    global $theConfig;
    $id=$connection->id;
    $log=<<<ETX

Id为:{$id};匿名用户连接

ETX;
    echo $log;
    fwrite($theConfig['logFile'],$log);
}
//收到客户端消息
function handle_message($connection,$data){
    global $theData,$theConfig,$socket_worker,$newQIR,$newJDT;
    //1.校验json格式是否正确
    $jsonData=checkJsonData($data);
    //2.检测是否为数组类型
    if(gettype($jsonData)==='array'){
    //3.检测是否存在必要属性'type'
    if(array_key_exists('type',$jsonData)){
    //4.检测type类型是否合规
    if(in_array($jsonData['type'],$theConfig['typeList'])){
    //5.处理数据
        $nowType=$jsonData['type'];
        switch ($nowType){
            case 'get_publickey':{//客户端尝试获取公钥
                //发送公钥
                $sendArr=['type'=>'publickey','data'=>getPublickey()];
                $sendJson=json_encode($sendArr);
                $connection->send($sendJson);
                break;
            }
            case 'login':{//客户端尝试登录
                //1.解密
                $Email=$jsonData['data']['email'];
                $Password=$jsonData['data']['password'];
                $RealPws=RsaTranslate($Password,'decode');
                //2.数据库进行查询
                if(LoginServer($Email,$RealPws)){
                    //直接给该连接加入新属性
                    $connection->email=$Email;
                    //返回数据
                    $sendArr=['type'=>'loginStatus','data'=>true];
                    $sendJson=json_encode($sendArr);
                    $connection->send($sendJson);
                    $log=<<<ETX

Id为:{$connection->id};Email为:{$Email};用户登录

ETX;
                    echo $log;
                    fwrite($theConfig['logFile'],$log);
                }else{
                    //查询为假
                    //返回数据
                    $sendArr=['type'=>'loginStatus','data'=>false];
                    $sendJson=json_encode($sendArr);
                    $connection->send($sendJson);
                }
                break;
            }
            case 'broadcast':{//广播数据
                if(property_exists($connection,'email')) {//必须是非匿名会话才能使用
                    //0.class检测
                    if(array_key_exists('class',$jsonData)){
                        //1.提取类型
                        $nowClass=$jsonData['class'];
                        switch ($nowClass){
//广播数据类型
                            case 'A1':{
                                //1.邮箱
                                $theUserEmail = $connection->email;
                                //2.提取数据
                                if(array_key_exists('x',$jsonData['data'])){
                                    $x=$jsonData['data']['x'];
                                }else{
                                    $x=0;
                                }
                                if(array_key_exists('y',$jsonData['data'])){
                                    $y=$jsonData['data']['y'];
                                }else{
                                    $y=0;
                                }
                                if(array_key_exists('color',$jsonData['data'])){
                                    $co=$jsonData['data']['color'];
                                }else{
                                    $co='#fff';
                                }
                                if(array_key_exists('name',$jsonData['data'])){
                                    $na=$jsonData['data']['name'];
                                }else{
                                    $na='无名';
                                }
                                //2.集合
                                $sdJson=["type"=>"broadcast","class"=>"A1","data"=>["x"=>$x,"y"=>$y,"color"=>$co,"name"=>$na,"email"=>$theUserEmail]];
                                foreach ($socket_worker->connections as $con) {
                                    //避免发送给匿名用户
                                    if(property_exists($con,'email')){
                                    if($con->email != ''){
                                    if($con->email != $theUserEmail){//避免发给广播者自身
                                        //返回数据
                                        $sendJson=json_encode($sdJson);
                                        $con->send($sendJson);
                                    }
                                    }
                                    }
                                }
                                break;
                            }
                            case 'point':{
                                //0.构建默认
                                $basicStructure=[
                                    'id'=>'temp',
                                    'type'=>'point',
                                    'points'=>[],
                                    'point'=>null,
                                    'color'=>'',
                                    'length'=>null,
                                    'width'=>2,
                                    'size'=>null,
                                    'childRelations'=>[],
                                    'fatherRelation'=>'',
                                    'childNodes'=>[],
                                    'fatherNode'=>'',
                                    'details'=>[]
                                ];
                                //1.检查是否包含data键名
                                if(!$newQIR->arrayPropertiesCheck('data',$jsonData)){break;}
                                //2.检查data是否为数组
                                if(!$newQIR->getDataType($jsonData['data'])=='array'){break;}
                                //$pwd jsonData['data']
                                //3.检查是否包含point属性
                                if(!$newQIR->arrayPropertiesCheck('point',$jsonData['data'])){break;}
                                //$pwd jsonData['data']['point']
                                //4.检查point是否包含xy属性
                                if(!$newQIR->arrayPropertiesCheck('x',$jsonData['data']['point']) OR !$newQIR->arrayPropertiesCheck('y',$jsonData['data']['point'])){break;}
                                //$pwd jsonData['data']['point']['x']/['y']
                                //5.检查xy值是否为数字
                                if(!$newQIR->digitalCheck($jsonData['data']['point']['x']) OR !$newQIR->digitalCheck($jsonData['data']['point']['y'])){break;}
                                //$pwd jsonData['data']
                                //6.检查color是否存在，存在则检查，不存在则设置默认值
                                if($newQIR->arrayPropertiesCheck('color',$jsonData['data'])){
                                    //6.1检查颜色格式是否正确
                                    if($newQIR->color16Check($jsonData['data']['color'])){
                                        print_r("正确的格式");
                                        $basicStructure['color']=$jsonData['data']['color'];
                                    }else{
                                        print_r("错误的格式");
                                        $basicStructure['color']='ffffff';
                                    }
                                }else{
                                    print_r("没有的格式");
                                    $basicStructure['color']='ffffff';
                                }
                                print_r($basicStructure['color']);
                                print_r("yes");
                                break;
                                ////2023-2-16 继续写检查数据，然后写上传数据接口
                            }
                        }
                    }
                }
                break;
            }
            case 'get_userData':{//用户获取数据(除开密码)
                if(property_exists($connection,'email')) {//必须是非匿名会话才能使用
                    $theUserEmail = $connection->email;
                    $ref = GetUserData($theUserEmail);
                    //返回数据
                    $sendArr = ['type' => 'send_userData', 'data' => $ref];
                    //这里会将汉字之类的转化为base64
                    $sendJson = json_encode($sendArr);
                    $connection->send($sendJson);
                }
                break;
            }
            case 'get_mapData':{//用户获取地图数据
                if(property_exists($connection,'email')){//必须是非匿名会话才能使用
                    $ref=GetMapData();
                    //返回数据
                    $sendArr=['type'=>'send_mapData','data'=>$ref];
                    $sendJson=json_encode($sendArr);
                    $connection->send($sendJson);
                }
                break;
            }
        }
    }
    }
    }
}
//客户端断开连接
function handle_close($connection){
    global $theData,$theConfig;
    //在已登录用户中查询该id并删除掉
    //1.获取当前发送消息者的会话id
    $nowConId=$connection->id;
    //2.获取用户名
    @$userEmail=$connection->emial;
    if($userEmail==''){$userEmail='未知';}
    $log=<<<ETX

Id为:{$nowConId};Email为:{$userEmail};断开连接

ETX;
    echo $log;
    fwrite($theConfig['logFile'],$log);
}
//校验json格式
function checkJsonData($value) {
    $res = json_decode($value, true);
    $error = json_last_error();
    if (!empty($error)) {
        return false;
    }else{
        return $res;
    }
}
//账号注册，参数：用户名，密码，QQ
//向用户发送注册用验证码，参数：用户qq；成功返回值:验证码；失败返回值:false
function sendVerificationCode($qqNumber){
  try {
    global $licenseCodeIMAP;
    $code=creatVerificationCode();
    $PHPMailerObj = new PHPMailer(true);
    $PHPMailerObj->CharSet='UTF-8';
    $PHPMailerObj->SMTPDebug=0;
    $PHPMailerObj->isSMTP();
    $PHPMailerObj->Host='smtp.qq.com';
    $PHPMailerObj->SMTPAuth=true;
    $PHPMailerObj->Username='emilia-t@qq.com';
    $PHPMailerObj->Password=$licenseCodeIMAP;
    $PHPMailerObj->SMTPSecure='ssl';
    $PHPMailerObj->Port=465;
    $PHPMailerObj->setFrom('emilia-t@qq.com', 'emilia-t');
    $PHPMailerObj->addAddress("$qqNumber@qq.com", "$qqNumber@qq.com");//收件人
    $PHPMailerObj->addReplyTo('emilia-t@qq.com','emilia-t');
    $PHPMailerObj->isHTML(true);
    $PHPMailerObj->Subject='欢迎您注册ATSW外棋账号！';
    $PHPMailerObj->Body='<h1>【ATSW】您正在注册ATSW外棋账号，验证码：<span style="color: #323232;font-weight: 800">'.$code.'</span>，请勿向任何人泄露，若非本人操作，请忽略此邮件。</h1>'.date('Y-m-d H:i:s');
    $PHPMailerObj->AltBody='【ATSW】您正在注册ATSW外棋账号，验证码：'.$code.'，请勿向任何人泄露，若非本人操作，请忽略此邮件。'.date('Y-m-d H:i:s');
    $PHPMailerObj->send();
    echo '验证码邮件发送成功，注册者QQ：'.$qqNumber;
    return $code;
  } catch (Exception $e) {
    echo '验证码邮件发送失败，注册者QQ: '.$qqNumber, $PHPMailerObj->ErrorInfo;
    return false;
  }
}
//注册验证码
function creatVerificationCode(){
  $str=['0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
  $token='';
  for($i=0;$i<6;$i++){
    $ran=rand(0,61);
    $token.=$str[$ran];
  }
  return $token;
}
//生成当前时间
function creatDate(){
  $date=getdate();
  $mon=sprintf('%02d',$date['mon']);
  $day=sprintf('%02d',$date['mday']);
  $hours=sprintf('%02d',$date['hours']);
  $minutes=sprintf('%02d',$date['minutes']);
  $seconds=sprintf('%02d',$date['seconds']);
  return "{$date['year']}-{$mon}-{$day} {$hours}:{$minutes}:{$seconds}";
}

/**
</internal-procedures>
**/

/**
<worker-setting>
**/
// 证书最好是申请的证书
$context = array(
    'ssl' => array(
// 使用绝对路径
        'local_cert' => 'C://SSL/1_atsw.top_bundle.crt', // 也可以是crt文件
        'local_pk' => 'C://SSL/2_atsw.top.key',
        'verify_peer' => false,
    )
);
// 这里设置的是websocket协议
//$socket_worker = new Worker('websocket://0.0.0.0:9998', $context);
// 设置transport开启ssl，websocket+ssl即wss
//$socket_worker->transport = 'ssl';
// 这里设置的是websocket协议
$socket_worker = new Worker('websocket://0.0.0.0:9998');
// 设置transport开启ssl，websocket+ssl即wss
//$socket_worker->transport = 'ssl';
// 启动100个进程
$socket_worker->count = 100;
$socket_worker->onConnect = 'handle_connection';
$socket_worker->onMessage = 'handle_message';
$socket_worker->onClose = 'handle_close';
//对长时间未登录的会话进行断开连接
$socket_worker->onWorkerStart = function ($socket_worker){
    Timer::add(10,
        function () use($socket_worker){
            $time_now = time();
            foreach($socket_worker->connections as $connection) {
                // 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
                if (empty($connection->lastMessageTime)) {
                    $connection->lastMessageTime = $time_now;
                    continue;
                }
                // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
                if(!property_exists($connection,'email')) {//必须是匿名会话才能使用
                    if ($time_now - $connection->lastMessageTime > HEARTBEAT_TIME) {
                        $connection->close();
                    }
                }
            }
        }
    );
};
//用户主动登出后，仍然能收到其他人的广播数据bug（等待一段时间后后台会自动断开此连接）
Worker::runAll();
/**
</worker-setting>
**/