<?php
/**
<php-config>
 **/
error_reporting(E_ALL);
//启用PDO
ini_set('extension', 'pdo_mysql');
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
use Workerman\Connection\AsyncTcpConnection;
require_once 'Workerman/Autoloader.php';
require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';
//服务器配置
require './config/Server_config.php';
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
//创建数据库
require './api/Other_createMysqlDataBase.php';
//通用数据检测工具
require './class/QualityInspectionRoom.php';
//json工具
require './class/JsonDisposalTool.php';
//地图数据库编辑工具
require './class/MapDataBaseEdit.php';
/**
</external-program>
 **/

/**
<class>
 **/
$newQIR=new QualityInspectionRoom(false);
$newJDT=new JsonDisposalTool();
//若要操作其余地图请在这里更改地图数据表的名字
$newMDBE=new MapDataBaseEdit('map_0_data');
/**
</class>
 **/

/**
<data>
 **/
const HEARTBEAT_TIME=120;
$theData=[
    'globalUid'=>0,
];
$theConfig=[
    'globalId'=>0,
    'time'=>date('m-j'),
    'logFile'=>null,
    //             广播数据     服务端接收    客户端发送  服务端发送   服务端发送       客户端发送      服务端发送
    'typeList'=>['broadcast','get_publickey','login','publickey','loginStatus','get_userData','send_userData','get_mapData','send_mapData']
];
$dir = 'log';
if (!file_exists($dir)) {mkdir($dir, 0777, true);echo "文件夹 $dir 创建成功;";}
$theConfig['logFile']=fopen('./log/'.$theConfig['time'].'.txt','a+');
/**
</data>
 **/

/**
<internal-procedures>
 **/
//初始化设置
function startSetting(){
    //检测数据库
    testDataBaseLink();
}
startSetting();
//与客户端连接
function handle_connection($connection){
    global $theConfig;
    $logData=['connectionId'=>$connection->id];
    createLog('connect',$logData);
}
//收到客户端消息
function handle_message($connection,$data){
    global $theData,$theConfig,$socket_worker,$newQIR,$newJDT,$newMDBE;
    //1.校验并解析json格式
    $jsonData=$newJDT->checkJsonData($data);
    //2.检测是否为数组类型
    if(gettype($jsonData)==='array'){
        //3.检测是否存在必要属性'type'
        if(array_key_exists('type',$jsonData)){
            //4.检测type类型是否合规
            if(in_array($jsonData['type'],$theConfig['typeList'])){
                //5.处理数据
                $nowType=$jsonData['type'];
                switch ($nowType){
                    //获取公钥数据
                    case 'get_publickey':{
                        //发送公钥
                        $sendArr=['type'=>'publickey','data'=>getPublickey()];
                        $sendJson=json_encode($sendArr);
                        $connection->send($sendJson);
                        break;
                    }
                    //登录数据
                    case 'login':{
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
                            $logData=['connectionId'=>$connection->id,'userEmail'=>$Email];
                            createLog('login',$logData);
                        }else{
                            //查询为假
                            //返回数据
                            $sendArr=['type'=>'loginStatus','data'=>false];
                            $sendJson=json_encode($sendArr);
                            $connection->send($sendJson);
                        }
                        break;
                    }
                    //广播数据
                    case 'broadcast':{
                        if(property_exists($connection,'email')) {//必须是非匿名会话才能使用
                            //0.class检测
                            if(array_key_exists('class',$jsonData)){
                                //1.提取类型
                                $nowClass=$jsonData['class'];
                                switch ($nowClass){
                                    //广播A1位置
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
                                        $sdJson=['type'=>'broadcast','class'=>'A1','data'=>['x'=>$x,'y'=>$y,'color'=>$co,'name'=>$na,'email'=>$theUserEmail]];
                                        //返回数据
                                        $sendJson=json_encode($sdJson);
                                        foreach ($socket_worker->connections as $con) {
                                            //避免发送给匿名用户
                                            if(property_exists($con,'email')){
                                                if($con->email != ''){
                                                    if($con->email != $theUserEmail){//避免发给广播者自身
                                                        $con->send($sendJson);
                                                    }
                                                }
                                            }
                                        }
                                        break;
                                    }
                                    //广播新增点
                                    case 'point':{
                                        //0.构建默认数据内容
                                        $basicStructure=['id'=>'temp','type'=>'point','points'=>[],'point'=>null,'color'=>'','length'=>null,'width'=>2,'size'=>null,'child_relations'=>[],'father_relation'=>'','child_nodes'=>[],'father_node'=>'','details'=>[]];
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
                                                $basicStructure['color']=$jsonData['data']['color'];
                                            }else{
                                                $basicStructure['color']='ffffff';
                                            }
                                        }else{
                                            $basicStructure['color']='ffffff';
                                        }
                                        //7.检查是否存在width，并检查是否为数字
                                        if($newQIR->arrayPropertiesCheck('width',$jsonData['data'])){
                                            //7.1检查是否是数字，不是数字则改写为默认值
                                            if($newQIR->digitalCheck($jsonData['data']['width'])){
                                                $basicStructure['width']=$jsonData['data']['width'];
                                            }else{
                                                $basicStructure['width']=2;
                                            }
                                        }else{
                                            $basicStructure['width']=2;
                                        }
                                        //8.details检查啊
                                        //8.1检查details是否存在
                                        if($newQIR->arrayPropertiesCheck('details',$jsonData['data'])){
                                            //8.2检查details数据结构是否为数组
                                            if($newQIR->getDataType($jsonData['data']['details'])=='array'){
                                                foreach ($jsonData['data']['details'] as $value){
                                                    //8.3检查该项键值对是否存在
                                                    if ($newQIR->arrayPropertiesCheck('key',$value) AND $newQIR->arrayPropertiesCheck('value',$value)){
                                                        //8.4检查键名和键值是否正常
                                                        if($newQIR->commonKeyNameCheck($value['key']) AND $newQIR->illegalCharacterCheck($value['value'])){

                                                        }else{
                                                            break;
                                                        }
                                                    }else{
                                                        break;
                                                    }
                                                }
                                            }else{
                                                $basicStructure['details']='';
                                            }
                                        }else{
                                            $basicStructure['details']='';
                                        }
                                        //归档加密
                                        $basicStructure['point']=$newJDT->btoa($newJDT->jsonPack($jsonData['data']['point']));
                                        $basicStructure['points']=$newJDT->btoa($newJDT->jsonPack([$jsonData['data']['point']]));
                                        $basicStructure['details']=$newJDT->btoa($newJDT->jsonPack($jsonData['data']['details']));
                                        //全部检查完毕
                                        //上传至数据库
                                        $updateStatus=$newMDBE->uploadPointData($basicStructure);
                                        //写入日志文件和广播给其他用户
                                        if($updateStatus===true){
                                            //广播至所有人
                                            //查询刚才加入的数据的id
                                            $newId=$newMDBE->selectNewId();
                                            //更改basic id
                                            $basicStructure['id']=$newId;
                                            //发送广播的emilia
                                            $broadcastEmail=$connection->email;
                                            //时间
                                            $dateTime=creatDate();
                                            //组合
                                            $sdJson=['type'=>'broadcast','class'=>'point','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$basicStructure];
                                            //返回数据
                                            $sendJson=json_encode($sdJson);
                                            foreach ($socket_worker->connections as $con) {
                                                //避免发送给匿名用户
                                                if(property_exists($con,'email')){
                                                    if($con->email != ''){
                                                        $con->send($sendJson);
                                                    }
                                                }
                                            }
                                            $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$broadcastEmail,'addId'=>$newId];
                                            createLog('userAddPoint',$logData);
                                        }
                                        break;
                                    }
                                    //删除元素
                                    case 'deleteElement':{
                                        try{
                                            $conveyor=$connection->email;
                                            $ID=$jsonData['data']['id'];
                                            $Time=creatDate();
                                            $sendArr=['type'=>'broadcast','class'=>'deleteElement','conveyor'=>$conveyor,'time'=>$Time,'data'=>['id'=>$ID]];
                                            $sendJson=json_encode($sendArr);
                                            //更改数据库
                                            if($newMDBE->deleteElementData($ID)){
                                                //更改成功则广播所有人
                                                foreach ($socket_worker->connections as $con) {
                                                    //避免发送给匿名用户
                                                    if(property_exists($con,'email')){
                                                        if($con->email != ''){
                                                            $con->send($sendJson);
                                                        }
                                                    }
                                                }
                                            }
                                            //写入日志
                                            $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$conveyor,'id'=>$ID];
                                            createLog('deleteElement',$logData);
                                        }catch (Exception $E){
                                            print_r('未知错误：deleteElement收到不明信息:');
                                            print_r($jsonData);
                                        }
                                        break;
                                    }
                                    //普通文本
                                    case 'textMessage':{
                                        //时间
                                        $dateTime=creatDate();
                                        $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$connection->email,'text'=>$jsonData['data']['message']];
                                        $sendArr = ['type'=>'broadcast','class'=>'textMessage','conveyor'=>$connection->email,'time'=>$dateTime,'data'=>$jsonData['data']];
                                        $sendJson = json_encode($sendArr);
                                        createLog('textMessage',$logData);
                                        foreach ($socket_worker->connections as $con) {
                                            //避免发送给匿名用户
                                            if(property_exists($con,'email')){
                                                //普通文本消息不会上传服务器
                                                //这里会将汉字之类的转化为base64
                                                $con->send($sendJson);
                                            }
                                        }
                                        break;
                                    }
                                    //更新元素
                                    case 'updateElement':{
                                        //0.1details
                                        $details=[];
                                        //0.2构建默认数据内容
                                        $basicStructure=[];
                                        //1.检查是否包含data键名
                                        if(!$newQIR->arrayPropertiesCheck('data',$jsonData)){break;}
                                        //2.检查data是否为数组
                                        if(!$newQIR->getDataType($jsonData['data'])=='array'){break;}
                                        //$pwd jsonData['data']
                                        //2.5检查id是否存在
                                        if(!$newQIR->arrayPropertiesCheck('id',$jsonData['data'])){break;}
                                        //2.6检查id是否为数字，是则加入，否则退出case
                                        if($newQIR->digitalCheck($jsonData['data']['id'])){
                                            $basicStructure['id']=$jsonData['data']['id'];
                                        }else{
                                            echo '所更新的id包含非数字字符';
                                            break;
                                        }
                                        //3.检查changes是否存在
                                        if(!$newQIR->arrayPropertiesCheck('changes',$jsonData['data'])){break;}
                                        //4.检查changes是否为数组
                                        if(!$newQIR->getDataType($jsonData['data']['changes'])=='array'){break;}
                                        //$pwd jsonData['data']['changes']
                                        //5.检查color是否存在，存在则检查
                                        if($newQIR->arrayPropertiesCheck('color',$jsonData['data']['changes'])){
                                            //5.1检查颜色格式是否正确
                                            if($newQIR->color16Check($jsonData['data']['changes']['color'])){
                                                $basicStructure['color']=$jsonData['data']['changes']['color'];
                                            }
                                        }
                                        //6.检查是否存在width，并检查是否为数字
                                        if($newQIR->arrayPropertiesCheck('width',$jsonData['data']['changes'])){
                                            //6.1检查是否是数字,，存在则检查
                                            if($newQIR->digitalCheck($jsonData['data']['changes']['width'])){
                                                $basicStructure['width']=$jsonData['data']['changes']['width'];
                                            }
                                        }
                                        //7.details检查
                                        //7.1检查details是否存在
                                        if($newQIR->arrayPropertiesCheck('details',$jsonData['data']['changes'])){
                                            //7.2检查details数据结构是否为数组
                                            if($newQIR->getDataType($jsonData['data']['changes']['details'])=='array'){
                                                foreach ($jsonData['data']['changes']['details'] as $value){
                                                    //7.3检查该项键值对是否存在
                                                    if ($newQIR->arrayPropertiesCheck('key',$value) AND $newQIR->arrayPropertiesCheck('value',$value)){
                                                        //8.4检查键名和键值是否正常，如果不正常则跳出当前循环case
                                                        if($newQIR->commonKeyNameCheck($value['key']) AND $newQIR->illegalCharacterCheck($value['value'])){
                                                            array_push($details,$value);
                                                            continue;
                                                        }else{
                                                            continue;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        //全部检查完毕
                                        //打包$details
                                        if(count($details)!=0){
                                            $basicStructure['details']=$newJDT->btoa($newJDT->jsonPack($details));
                                        }
                                        //上传数据库
                                        if($newMDBE->updateElementData($basicStructure)){
                                            //广播给登录用户
                                            //发送广播的emilia
                                            $broadcastEmail=$connection->email;
                                            //时间
                                            $dateTime=creatDate();
                                            //组合
                                            $sdJson=['type'=>'broadcast','class'=>'updateElement','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$basicStructure];
                                            //返回数据
                                            $sendJson=json_encode($sdJson);
                                            foreach ($socket_worker->connections as $con){
                                                //避免发送给匿名用户
                                                if(property_exists($con,'email')){
                                                    if($con->email!=''){
                                                        $con->send($sendJson);
                                                    }
                                                }
                                            }
                                            //写入日志文件
                                            $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$broadcastEmail,'id'=>$basicStructure['id']];
                                            createLog('updateElement',$logData);
                                        }
                                        break;
                                    }
                                }
                            }
                        }
                        break;
                    }
                    //获取用户数据
                    case 'get_userData':{
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
                    //获取地图数据
                    case 'get_mapData':{
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
    $logData=['connectionId'=>$nowConId,'userEmail'=>$userEmail];
    createLog('disconnect',$logData);
}
//账号注册，参数：用户名，密码，QQ
//向用户发送注册用验证码，参数：用户qq；成功返回值:验证码；失败返回值:false
function sendVerificationCode($qqNumber){
    try {
        $code=creatVerificationCode();
        $PHPMailerObj = new PHPMailer(true);
        $PHPMailerObj->CharSet='UTF-8';
        $PHPMailerObj->SMTPDebug=0;
        $PHPMailerObj->isSMTP();
        $PHPMailerObj->Host='smtp.qq.com';
        $PHPMailerObj->SMTPAuth=true;
        $PHPMailerObj->Username='emilia-t@qq.com';
        $PHPMailerObj->Password=LicenseCodeIMAP;
        $PHPMailerObj->SMTPSecure='ssl';
        $PHPMailerObj->Port=465;
        $PHPMailerObj->setFrom('emilia-t@qq.com', 'emilia-t');
        $PHPMailerObj->addAddress('$qqNumber@qq.com', '$qqNumber@qq.com');//收件人
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
//生成log对应的log
function createLog($logType,$logData){
    global $theConfig;
    $time=creatDate();
    switch ($logType){
        case 'connect':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};匿名用户连接

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
        case 'disconnect':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Email为:{$logData['userEmail']};断开连接

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
        case 'login':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Email为:{$logData['userEmail']};用户登录

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
        case 'userAddPoint':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Email为:{$logData['broadcastEmail']};新增一个点:{$logData['addId']}

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
        case 'textMessage':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Email为:{$logData['broadcastEmail']};发送一条消息:{$logData['text']}

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
        case 'deleteElement':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Email为:{$logData['broadcastEmail']};删除一个元素:{$logData['id']}

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
        case 'updateElement':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Email为:{$logData['broadcastEmail']};更新一个元素:{$logData['id']}

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
        default:{}
    }
}
/**
</internal-procedures>
 **/

/**
<worker-setting>
 **/
if(__SERVER_SSL_STA__){
    $context=array('ssl'=>array('local_cert'=>__SERVER_SSL_CRT__,'local_pk'=>__SERVER_SSL_KEY__,'verify_peer'=>true));
    $socket_worker=new Worker('websocket://'.__SERVER_IP_PORT__,$context);
    $socket_worker->transport='ssl';
}else{
    $socket_worker=new Worker('websocket://'.__SERVER_IP_PORT__);
}
$socket_worker->count=1;
$socket_worker->onConnect='handle_connection';
$socket_worker->onMessage='handle_message';
$socket_worker->onClose='handle_close';
//对长时间未登录的会话进行断开连接
$socket_worker->onWorkerStart=function ($socket_worker){
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
Worker::runAll();
/**
</worker-setting>
 **/