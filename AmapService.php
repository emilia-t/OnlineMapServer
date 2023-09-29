<?php
/**
<php-config>
 **/
error_reporting(E_ALL);
date_default_timezone_set('Asia/Hong_Kong');
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
//use PHPMailer\PHPMailer\PHPMailer;
//use PHPMailer\PHPMailer\Exception;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Protocols\Http\Response;
require_once 'Workerman/Autoloader.php';
//require 'PHPMailer/src/Exception.php';
//require 'PHPMailer/src/PHPMailer.php';
//require 'PHPMailer/src/SMTP.php';
//服务器配置
require 'config/Server_config.php';
//获取公钥与私钥的API
require 'api/Public_getPublickey.php';
//解密和加密功能
require 'api/Other_RsaTranslate.php';
//登录验证功能
require 'api/Public_LoginServer.php';
//查询用户数据
require 'api/Public_GetUserData.php';
//查询地图数据
require 'api/Public_GetMapData.php';
//创建数据库
require 'api/Other_createMysqlDataBase.php';
//通用数据检测工具
require 'class/QualityInspectionRoom.php';
//json工具
require 'class/JsonDisposalTool.php';
//地图数据库编辑工具
require 'class/MapDataBaseEdit.php';
//文件处理类
require 'class/FileOperation.php';
/**
</external-program>
 **/

/**
<class>
 **/
$newQIR=new QualityInspectionRoom(false);
$newJDT=new JsonDisposalTool();
$newMDBE=new MapDataBaseEdit($mysql_public_sheet_name,$mysql_public_layer_name);
$newFO=new FileOperation();
/**
</class>
 **/

/**
<data>
 **/
const HEARTBEAT_TIME=120;
$theData=[
    'globalUid'=>0,
    'ip_connect_times'=>[],
    'activeElement'=>[],
];
$theConfig=[
    'globalId'=>0,
    'time'=>date('m-j'),
    'logFile'=>null,
    'typeList'=>['get_serverImg','get_serverConfig','broadcast','get_publickey','login','publickey','loginStatus','get_userData','send_userData','get_mapData','send_mapData','get_presence','send_presence','get_activeData','send_activeData','send_error','send_correct','get_mapLayer','send_mapLayer','updateLayerData']
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
    //testDataBaseLink();
}
startSetting();
//与客户端连接
function handle_connection($connection){
    global $theData;
    $ip=$connection->getRemoteIp();
    //$ip=$connection->getRemoteIp().':'.$connection->getRemotePort();
    //$dateTime=creatDate();
    //$logData=['connectionId'=>$connection->id,'broadcastEmail'=>'系统消息','text'=>'欢迎，已知的bug:1.移动地图存在底图和元素偏移的问题'];
    //$sendArr = ['type'=>'broadcast','class'=>'textMessage','conveyor'=>'系统消息','time'=>$dateTime,'data'=>$jsonData['data']];
    //$sendJson = json_encode($sendArr);
    //公告消息
    //$connection->send($sendJson);
    //当客户端连接上之后需要携带至少1个参数表达意愿
    if(!isset($theData['ip_connect_times'][$ip])) {
        $theData['ip_connect_times'][$ip] = 0;
    }else{
        $theData['ip_connect_times'][$ip]++;
    }
    if($theData['ip_connect_times'][$ip] >= 120) {
        $connection->close();
        $logData=['connectionId'=>$connection->id,'connectionIp'=>$ip];
        createLog('warn',$logData);
        return;
    }
    $logData=['connectionId'=>$connection->id,'connectionIp'=>$ip];
    createLog('connect',$logData);
}
function handle_message($connection, $data){//收到客户端消息
    global $theData,$theConfig,$socket_worker,$newQIR,$newJDT,$newMDBE,$newFO;
    $jsonData=$newJDT->checkJsonData($data);//1.校验并解析json格式
    if(gettype($jsonData)==='array'){//2.检测是否为数组类型
        if(array_key_exists('type',$jsonData)){//3.检测是否存在必要属性'type'
            if(in_array($jsonData['type'],$theConfig['typeList'])){//4.检测type类型是否合规
                $nowType=$jsonData['type'];//5.处理数据
                switch ($nowType){
                    case 'broadcast':{//广播数据
                        if(property_exists($connection,'email')) {//必须是非匿名会话才能使用
                            if(array_key_exists('class',$jsonData)){//0.class检测
                                $nowClass=$jsonData['class'];//1.提取类型
                                switch ($nowClass){
                                    case 'A1':{//广播A1位置(此类型需要后续删除)
                                        $theUserEmail = $connection->email;//1.邮箱
                                        if(array_key_exists('x',$jsonData['data'])){//2.提取数据
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
                                        $sdJson=['type'=>'broadcast','class'=>'A1','data'=>['x'=>$x,'y'=>$y,'color'=>$co,'name'=>$na,'email'=>$theUserEmail]];
                                        $sendJson=json_encode($sdJson);//返回数据
                                        foreach ($socket_worker->connections as $con) {
                                            if(property_exists($con,'email')){//避免发送给匿名用户
                                                if($con->email != ''){
                                                    if($con->email != $theUserEmail){//避免发给广播者自身
                                                        $con->send($sendJson);
                                                    }
                                                }
                                            }
                                        }
                                        break;
                                    }
                                    case 'area':{//新增区域
                                        $basicStructure=['id'=>'temp','type'=>'area','points'=>[],'point'=>null,'color'=>'','phase'=>1,'width'=>2,'child_relations'=>[],'father_relation'=>'','child_nodes'=>[],'father_node'=>'','details'=>null];//0.构建默认数据内容
                                        if(!$newQIR->arrayPropertiesCheck('data',$jsonData)){break;}//1.检查是否包含data键名
                                        if(!$newQIR->getDataType($jsonData['data'])=='array'){break;}//2.检查data是否为数组
                                        if(!$newQIR->arrayPropertiesCheck('point',$jsonData['data'])){break;}//3.检查是否包含point属性$pwd jsonData['data']
                                        if(!$newQIR->arrayPropertiesCheck('x',$jsonData['data']['point']) OR !$newQIR->arrayPropertiesCheck('y',$jsonData['data']['point'])){break;}//4.检查point是否包含xy属性$pwd jsonData['data']['point']
                                        if(!$newQIR->digitalCheck($jsonData['data']['point']['x']) OR !$newQIR->digitalCheck($jsonData['data']['point']['y'])){break;}//5.检查xy值是否为数字$pwd jsonData['data']['point']['x']/['y']
                                        if(!$newQIR->arrayPropertiesCheck('points',$jsonData['data'])){break;}//6.1检查是否存在points属性
                                        if(!$newQIR->getDataType($jsonData['data']['points'])=='array'){break;}//6.2检查points是否是一个数组
                                        $pointsLock=false;//6.3循环检查内部
                                        for($i=0;$i<count($jsonData['data']['points']);$i++){
                                            if(!$newQIR->arrayPropertiesCheck('x',$jsonData['data']['points'][$i])){$pointsLock=true;break;}
                                            if(!$newQIR->arrayPropertiesCheck('y',$jsonData['data']['points'][$i])){$pointsLock=true;break;}
                                            if(!$newQIR->digitalCheck($jsonData['data']['points'][$i]['x'])){$pointsLock=true;break;}//6.3.2检查xy是否为数字
                                            if(!$newQIR->digitalCheck($jsonData['data']['points'][$i]['y'])){$pointsLock=true;break;}
                                        }
                                        if($pointsLock){
                                            break;
                                        }
                                        if($newQIR->arrayPropertiesCheck('color',$jsonData['data'])){//7.检查color是否存在，存在则检查，不存在则设置默认值$pwd jsonData['data']
                                            if($newQIR->color16Check($jsonData['data']['color'])){//7.1检查颜色格式是否正确
                                                $basicStructure['color']=$jsonData['data']['color'];
                                            }else{
                                                $basicStructure['color']='ffffff';
                                            }
                                        }else{
                                            $basicStructure['color']='ffffff';
                                        }
                                        if($newQIR->arrayPropertiesCheck('width',$jsonData['data'])){//8.检查是否存在width，并检查是否为数字
                                            if($newQIR->digitalCheck($jsonData['data']['width'])){//7.1检查是否是数字，不是数字则改写为默认值
                                                $basicStructure['width']=$jsonData['data']['width'];
                                            }else{
                                                $basicStructure['width']=2;
                                            }
                                        }else{
                                            $basicStructure['width']=2;
                                        }
                                        if($newQIR->arrayPropertiesCheck('details',$jsonData['data'])){//9.1检查details是否存在
                                            if($newQIR->getDataType($jsonData['data']['details'])=='array'){//8.2检查details数据结构是否为数组
                                                foreach ($jsonData['data']['details'] as $value){
                                                    if ($newQIR->arrayPropertiesCheck('key',$value) AND $newQIR->arrayPropertiesCheck('value',$value)){//8.3检查该项键值对是否存在
                                                        if($newQIR->commonKeyNameCheck($value['key']) AND $newQIR->illegalCharacterCheck($value['value'])){//8.4检查键名和键值是否正常

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
                                        $basicStructure['point']=$newJDT->btoa($newJDT->jsonPack($jsonData['data']['point']));//归档加密
                                        $basicStructure['points']=$newJDT->btoa($newJDT->jsonPack($jsonData['data']['points']));
                                        $basicStructure['details']=$newJDT->btoa($newJDT->jsonPack($jsonData['data']['details']));
                                        $updateStatus=$newMDBE->uploadLineData($basicStructure);//全部检查完毕上传至数据库
                                        if($updateStatus===true){//写入日志文件和广播给其他用户
                                            $newId=$newMDBE->selectNewId();//广播至所有人查询刚才加入的数据的id
                                            $basicStructure['id']=$newId;//更改basic id
                                            $broadcastEmail=$connection->email;//发送广播的email
                                            $dateTime=creatDate();
                                            $sdJson=['type'=>'broadcast','class'=>'area','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$basicStructure];
                                            $sendJson=json_encode($sdJson);
                                            //返回成功指令
                                            sendCorrect('upload',['vid'=>$jsonData['data']['id'],'rid'=>$newId],$connection);
                                            foreach ($socket_worker->connections as $con) {
                                                //避免发送给匿名用户
                                                if(property_exists($con,'email')){
                                                    if($con->email != ''){
                                                        $con->send($sendJson);
                                                    }
                                                }
                                            }
                                            $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$broadcastEmail,'addId'=>$newId];
                                            createLog('userAddArea',$logData);
                                        }else{
                                            sendError('upload',$jsonData['data'],'database upload fail',$connection);
                                        }
                                        break;
                                    }
                                    //新增线段
                                    case 'line':{
                                        //0.构建默认数据内容
                                        $basicStructure=['id'=>'temp','type'=>'line','points'=>[],'point'=>null,'color'=>'','phase'=>1,'width'=>2,'child_relations'=>[],'father_relation'=>'','child_nodes'=>[],'father_node'=>'','details'=>null];
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
                                        //6.检查points
                                        //6.1检查是否存在points属性
                                        if(!$newQIR->arrayPropertiesCheck('points',$jsonData['data'])){break;}
                                        //6.2检查points是否是一个数组
                                        if(!$newQIR->getDataType($jsonData['data']['points'])=='array'){break;}
                                        //6.3循环检查内部
                                        $pointsLock=false;
                                        for($i=0;$i<count($jsonData['data']['points']);$i++){
                                            //6.3.1检查是否存在xy
                                            if(!$newQIR->arrayPropertiesCheck('x',$jsonData['data']['points'][$i])){$pointsLock=true;break;}
                                            if(!$newQIR->arrayPropertiesCheck('y',$jsonData['data']['points'][$i])){$pointsLock=true;break;}
                                            //6.3.2检查xy是否为数字
                                            if(!$newQIR->digitalCheck($jsonData['data']['points'][$i]['x'])){$pointsLock=true;break;}
                                            if(!$newQIR->digitalCheck($jsonData['data']['points'][$i]['y'])){$pointsLock=true;break;}
                                        }
                                        if($pointsLock){
                                            break;
                                        }
                                        //$pwd jsonData['data']
                                        //7.检查color是否存在，存在则检查，不存在则设置默认值
                                        if($newQIR->arrayPropertiesCheck('color',$jsonData['data'])){
                                            //7.1检查颜色格式是否正确
                                            if($newQIR->color16Check($jsonData['data']['color'])){
                                                $basicStructure['color']=$jsonData['data']['color'];
                                            }else{
                                                $basicStructure['color']='ffffff';
                                            }
                                        }else{
                                            $basicStructure['color']='ffffff';
                                        }
                                        //8.检查是否存在width，并检查是否为数字
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
                                        //9.details检查
                                        //9.1检查details是否存在
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
                                        $basicStructure['points']=$newJDT->btoa($newJDT->jsonPack($jsonData['data']['points']));
                                        $basicStructure['details']=$newJDT->btoa($newJDT->jsonPack($jsonData['data']['details']));
                                        //全部检查完毕
                                        //上传至数据库
                                        $updateStatus=$newMDBE->uploadLineData($basicStructure);
                                        //写入日志文件和广播给其他用户
                                        if($updateStatus===true){
                                            //广播至所有人
                                            //查询刚才加入的数据的id
                                            $newId=$newMDBE->selectNewId();
                                            //更改basic id
                                            $basicStructure['id']=$newId;
                                            //发送广播的email
                                            $broadcastEmail=$connection->email;
                                            //时间
                                            $dateTime=creatDate();
                                            //组合
                                            $sdJson=['type'=>'broadcast','class'=>'line','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$basicStructure];
                                            //返回数据
                                            $sendJson=json_encode($sdJson);
                                            //返回成功指令
                                            sendCorrect('upload',['vid'=>$jsonData['data']['id'],'rid'=>$newId],$connection);
                                            foreach ($socket_worker->connections as $con) {
                                                //避免发送给匿名用户
                                                if(property_exists($con,'email')){
                                                    if($con->email != ''){
                                                        $con->send($sendJson);
                                                    }
                                                }
                                            }
                                            $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$broadcastEmail,'addId'=>$newId];
                                            createLog('userAddLine',$logData);
                                        }else{
                                            sendError('upload',$jsonData['data'],'database upload fail',$connection);
                                        }
                                        break;
                                    }
                                    //广播新增点
                                    case 'point':{
                                        //0.构建默认数据内容
                                        $basicStructure=['id'=>'temp','type'=>'point','points'=>[],'point'=>null,'color'=>'','phase'=>1,'width'=>2,'child_relations'=>[],'father_relation'=>'','child_nodes'=>[],'father_node'=>'','details'=>[],'custom'=>null];
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
                                        //8.details检查
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
                                        $basicStructure['custom']=$newJDT->btoa($newJDT->jsonPack($jsonData['data']['custom']));
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
                                            //发送广播的email
                                            $broadcastEmail=$connection->email;
                                            //时间
                                            $dateTime=creatDate();
                                            //组合
                                            $sdJson=['type'=>'broadcast','class'=>'point','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$basicStructure];
                                            //返回数据
                                            $sendJson=json_encode($sdJson);
                                            //返回成功指令
                                            sendCorrect('upload',['vid'=>$jsonData['data']['id'],'rid'=>$newId],$connection);
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
                                        }else{
                                            sendError('upload',$jsonData['data'],'database upload fail',$connection);
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
                                            $updateStatus=$newMDBE->updateElementPhase($ID,2);
                                            if($updateStatus===true){
                                                //更改成功则广播所有人
                                                sendCorrect('delete',['rid'=>$ID],$connection);
                                                foreach ($socket_worker->connections as $con) {
                                                    //避免发送给匿名用户
                                                    if(property_exists($con,'email')){
                                                        if($con->email != ''){
                                                            $con->send($sendJson);
                                                        }
                                                    }
                                                }
                                            }else{
                                                sendError('delete',$jsonData['data'],'database delete fail',$connection);
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
                                        $custom=null;
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
                                        if($newQIR->arrayPropertiesCheck('custom',$jsonData['data']['changes'])){
                                            $custom=$jsonData['data']['changes']['custom'];
                                        }
                                        //全部检查完毕
                                        //打包$details
                                        if(count($details)!=0){
                                            $basicStructure['details']=$newJDT->btoa($newJDT->jsonPack($details));
                                        }
                                        if($custom!=null){
                                            $basicStructure['custom']=$newJDT->btoa($newJDT->jsonPack($custom));
                                        }
                                        //上传数据库
                                        $isSuccess=$newMDBE->updateElementData($basicStructure);
                                        if($isSuccess){//更新成功
                                            $broadcastEmail=$connection->email;
                                            //时间
                                            $dateTime=creatDate();
                                            //组合
                                            $sdJson=['type'=>'broadcast','class'=>'updateElement','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$basicStructure];
                                            //返回数据
                                            $sendJson=json_encode($sdJson);
                                            //发送成功信息
                                            sendCorrect('updateElement',['vid'=>$jsonData['data']['updateId'],'rid'=>$jsonData['data']['id']],$connection);
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
                                        }else{//更新失败
                                            sendError('updateElement',$jsonData['data'],'database error',$connection);
                                        }
                                        break;
                                    }
                                    //更新元素节点
                                    case 'updateElementNode':{
                                        $nodeObject=[];
                                        //1.检查
                                        if($newQIR->getDataType($jsonData['data'])!='array'){break;}
                                        if(!$newQIR->arrayPropertiesCheck('id',$jsonData['data'])){break;}
                                        if(!$newQIR->arrayPropertiesCheck('points',$jsonData['data'])){break;}
                                        if($newQIR->getDataType($jsonData['data']['points'])!='array'){break;}
                                        $lock=false;
                                        foreach($jsonData['data']['points'] as $nowCheck){
                                            if($newQIR->getDataType($nowCheck)!='array'){
                                                $lock=true;
                                                break;
                                            }
                                            if($newQIR->arrayPropertiesCheck('x',$nowCheck)==false || $newQIR->arrayPropertiesCheck('y',$nowCheck)==false){
                                                $lock=true;
                                                break;
                                            }
                                            if($newQIR->digitalCheck($nowCheck['x'])==false || $newQIR->digitalCheck($nowCheck['y'])==false){
                                                $lock=true;
                                                break;
                                            }
                                        }
                                        if($lock){//如果存在异常的节点数据则推出case不做操作
                                            break;
                                        }else{
                                            //预
                                            $nodeObject['id']=$jsonData['data']['id'];
                                            $nodeObject['points']=$jsonData['data']['points'];
                                            //接着再判断是否存在point（选择性传入的参数）
                                            if($newQIR->arrayPropertiesCheck('point',$jsonData['data'])){
                                                //检查是否为数组
                                                if($newQIR->getDataType($jsonData['data']['point'])=='array'){
                                                    //检查是否存在xy
                                                    if($newQIR->arrayPropertiesCheck('x',$jsonData['data']['point']) && $newQIR->arrayPropertiesCheck('y',$jsonData['data']['point'])){
                                                        //检查是否为数字
                                                        if($newQIR->digitalCheck($jsonData['data']['point']['x']) && $newQIR->digitalCheck($jsonData['data']['point']['y'])){
                                                            $nodeObject['point']=[];
                                                            $nodeObject['point']['x']=$jsonData['data']['point']['x'];
                                                            $nodeObject['point']['y']=$jsonData['data']['point']['y'];
                                                            //打包为json
                                                            $nodeObject['point']=$newJDT->jsonPack($nodeObject['point']);
                                                            //转化为b
                                                            $nodeObject['point']=$newJDT->btoa($nodeObject['point']);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        //打包为json
                                        $nodeObject['points']=$newJDT->jsonPack($nodeObject['points']);
                                        //转化为b
                                        $nodeObject['points']=$newJDT->btoa($nodeObject['points']);
                                        //上传数据库
                                        $isSuccess=$newMDBE->updateElementData($nodeObject);
                                        if($isSuccess){
                                            if($newQIR->arrayPropertiesCheck('type',$jsonData['data'])){
                                                $nodeObject['type']=$jsonData['data']['type'];
                                            }
                                            //广播给登录用户
                                            //发送广播的email
                                            $broadcastEmail=$connection->email;
                                            //时间
                                            $dateTime=creatDate();
                                            //组合
                                            $sdJson=['type'=>'broadcast','class'=>'updateElementNode','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$nodeObject];
                                            //返回数据
                                            $sendJson=json_encode($sdJson);
                                            //发送成功信息
                                            sendCorrect('updateNode',['vid'=>$jsonData['data']['updateId'],'rid'=>$jsonData['data']['id']],$connection);
                                            foreach ($socket_worker->connections as $con){
                                                //避免发送给匿名用户st
                                                if(property_exists($con,'email')){
                                                    //如果不希望发送给广播者则启用下方
                                                    //if($con->email==$broadcastEmail){
                                                    //    continue;
                                                    //}
                                                    if($con->email!=''){
                                                        $con->send($sendJson);
                                                    }
                                                }
                                            }
                                            //写入日志文件
                                            $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$broadcastEmail,'id'=>$nodeObject['id']];
                                            createLog('updateElementNode',$logData);
                                        }else{
                                            sendError('updateNode',$jsonData['data'],'update node fail',$connection);
                                        }
                                        break;
                                    }
                                    //选中要素
                                    case 'selectIngElement':{
                                        $ID=$jsonData['data'];
                                        $userName=$connection->userData['userName'];
                                        $headColor=$connection->userData['headColor'];
                                        $email=$connection->email;
                                        $Lock=array_key_exists('E'.$ID,$theData['activeElement']);
                                        if($Lock){//判断此要素是否有人已经右键选中了
                                            if($theData['activeElement']['E'.$ID]['select']===null){
                                                $theData['activeElement']['E'.$ID]['select']=[];
                                                $theData['activeElement']['E'.$ID]['select']['email']=$email;
                                                $theData['activeElement']['E'.$ID]['select']['color']=$headColor;
                                                $theData['activeElement']['E'.$ID]['select']['name']=$userName;
                                            }else{
                                                return false;
                                            }
                                        }else{
                                            $theData['activeElement']['E'.$ID]=['pick'=>null,'select'=>['email'=>$email,'color'=>$headColor,'name'=>$userName]];
                                        }
                                        $Time=creatDate();
                                        $sendArr=['type'=>'broadcast','class'=>'selectIngElement','conveyor'=>$userName,'time'=>$Time,'data'=>['id'=>$ID,'color'=>$headColor]];
                                        $sendJson=json_encode($sendArr);
                                        foreach ($socket_worker->connections as $con) {//更改成功则广播所有人
                                            if(property_exists($con,'email')){//避免发送给匿名用户
                                                if($con->email != ''){
                                                    $con->send($sendJson);
                                                }
                                            }
                                        }
                                        break;
                                    }
                                    //取消选中要素
                                    case 'selectEndElement':{
                                        $ID=$jsonData['data'];
                                        $userName=$connection->userData['userName'];
                                        $email=$connection->email;
                                        $Lock=array_key_exists('E'.$ID,$theData['activeElement']);
                                        if($Lock){//判断此要素是否有人已经右键选中了
                                            if($theData['activeElement']['E'.$ID]['select']!==null){
                                                if($theData['activeElement']['E'.$ID]['select']['email']!==$email){
                                                    return false;
                                                }else{
                                                    $theData['activeElement']['E'.$ID]['select']=null;
                                                    if($theData['activeElement']['E'.$ID]['pick']===null){
                                                        $index=array_search('E'.$ID,$theData['activeElement']);
                                                        array_splice($theData['activeElement'],$index,1);
                                                    }
                                                }
                                            }else{
                                                return false;
                                            }
                                        }else{
                                            return false;
                                        }
                                        $Time=creatDate();
                                        $sendArr=['type'=>'broadcast','class'=>'selectEndElement','conveyor'=>$userName,'time'=>$Time,'data'=>$ID];
                                        $sendJson=json_encode($sendArr);
                                        //更改成功则广播所有人
                                        foreach ($socket_worker->connections as $con) {
                                            //避免发送给匿名用户
                                            if(property_exists($con,'email')){
                                                if($con->email != ''){
                                                    $con->send($sendJson);
                                                }
                                            }
                                        }
                                        break;
                                    }
                                    //选中要素pick
                                    case 'pickIngElement':{
                                        $ID=$jsonData['data'];
                                        $userName=$connection->userData['userName'];
                                        $headColor=$connection->userData['headColor'];
                                        $email=$connection->email;
                                        $Lock=array_key_exists('E'.$ID,$theData['activeElement']);
                                        if($Lock){//判断此要素是否有人已经右键选中了
                                            if($theData['activeElement']['E'.$ID]['pick']===null){
                                                $theData['activeElement']['E'.$ID]['pick']=[];
                                                $theData['activeElement']['E'.$ID]['pick']['email']=$email;
                                                $theData['activeElement']['E'.$ID]['pick']['color']=$headColor;
                                                $theData['activeElement']['E'.$ID]['pick']['name']=$userName;
                                            }else{
                                                return false;
                                            }
                                        }else{
                                            $theData['activeElement']['E'.$ID]=['pick'=>['email'=>$email,'color'=>$headColor,'name'=>$userName],'select'=>null];
                                        }
                                        $Time=creatDate();
                                        $sendArr=['type'=>'broadcast','class'=>'pickIngElement','conveyor'=>$userName,'time'=>$Time,'data'=>['id'=>$ID,'color'=>$headColor]];
                                        $sendJson=json_encode($sendArr);
                                        foreach ($socket_worker->connections as $con) {//更改成功则广播所有人
                                            if(property_exists($con,'email')){//避免发送给匿名用户
                                                if($con->email != ''){
                                                    $con->send($sendJson);
                                                }
                                            }
                                        }
                                        break;
                                    }
                                    //取消选中要素
                                    case 'pickEndElement':{
                                        $ID=$jsonData['data'];
                                        $userName=$connection->userData['userName'];
                                        $email=$connection->email;
                                        $Lock=array_key_exists('E'.$ID,$theData['activeElement']);
                                        if($Lock){//判断此要素是否有人已经右键选中了
                                            if($theData['activeElement']['E'.$ID]['pick']!==null){
                                                if($theData['activeElement']['E'.$ID]['pick']['email']!==$email){
                                                    return false;
                                                }else{
                                                    $theData['activeElement']['E'.$ID]['pick']=null;
                                                    if($theData['activeElement']['E'.$ID]['select']===null){
                                                        $index=array_search('E'.$ID,$theData['activeElement']);
                                                        array_splice($theData['activeElement'],$index,1);
                                                    }
                                                }
                                            }else{
                                                return false;
                                            }
                                        }else{
                                            return false;
                                        }
                                        $Time=creatDate();
                                        $sendArr=['type'=>'broadcast','class'=>'pickEndElement','conveyor'=>$userName,'time'=>$Time,'data'=>$ID];
                                        $sendJson=json_encode($sendArr);
                                        //更改成功则广播所有人
                                        foreach ($socket_worker->connections as $con) {
                                            //避免发送给匿名用户
                                            if(property_exists($con,'email')){
                                                if($con->email != ''){
                                                    $con->send($sendJson);
                                                }
                                            }
                                        }
                                        break;
                                    }
                                    //恢复被删除的元素
                                    case 'restoreElement':{
                                        try{
                                            $ID=$jsonData['data'];
                                            $elementData=$newMDBE->getElementById($ID);
                                            if($elementData===false){
                                                return false;
                                            }
                                            $broadcastEmail=$connection->email;
                                            $Time=creatDate();
                                            $sdJson=['type'=>'broadcast','class'=>$elementData['type'],'conveyor'=>$broadcastEmail,'time'=>$Time,'data'=>$elementData];
                                            $sendJson=json_encode($sdJson);
                                            //更改元素
                                            $updateStatus=$newMDBE->updateElementPhase($ID,1);
                                            if($updateStatus===true){
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
                                            $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$broadcastEmail,'id'=>$ID];
                                            createLog('restoreElement',$logData);
                                        }catch (Exception $E){
                                            print_r('未知错误：restoreElement收到不明信息:');
                                            print_r($jsonData);
                                        }
                                        break;
                                    }
                                    //更新图层数据
                                    case 'updateLayerData':{
                                        $basicStructure=[];
                                        if($newQIR->arrayPropertiesCheck('id',$jsonData['data'])){
                                            $basicStructure['id']=$jsonData['data']['id'];
                                        }else{
                                            break;
                                        }
                                        if($newQIR->arrayPropertiesCheck('structure',$jsonData['data'])){
                                            $basicStructure['structure']=$newJDT->btoa($newJDT->jsonPack($jsonData['data']['structure']));
                                        }else{
                                            break;
                                        }
                                        if($newQIR->arrayPropertiesCheck('members',$jsonData['data'])){
                                            $basicStructure['members']=$newJDT->btoa($newJDT->jsonPack($jsonData['data']['members']));
                                        }
                                        $updateStatus=$newMDBE->updateLayerData($basicStructure);
                                        $broadcastEmail=$connection->email;
                                        $Time=creatDate();
                                        $sdJson=['type'=>'broadcast','class'=>'updateLayerData','conveyor'=>$broadcastEmail,'time'=>$Time,'data'=>$basicStructure];
                                        $sendJson=json_encode($sdJson);
                                        if($updateStatus===true){
                                            foreach ($socket_worker->connections as $con) {
                                                //避免发送给匿名用户
                                                if(property_exists($con,'email')){
                                                    if($con->email != ''){
                                                        $con->send($sendJson);
                                                    }
                                                }
                                            }
                                        }
                                        break;
                                    }
                                }
                            }
                        }
                        break;
                    }
                    //获取服务器展示图像
                    case 'get_serverImg':{
                        //1检查是否包含一个time的参数
                        if($newQIR->arrayPropertiesCheck('data',$jsonData)){
                        if($newQIR->arrayPropertiesCheck('time',$jsonData['data'])){
                            //获取map_img的最近修改时间
                            $lt = $newFO->getFileModifiedTime(__SERVER_CONFIG__IMG__);
                            //与客户端进行对比如果返回false说明相同或时间格式错误，则返回空数据，如果返回客户端落后于服务端则返回新数据
                            if($newFO->compareTime($lt,$jsonData['data']['time'])==='false'){
                                $connection->send('');
                            }else if($newFO->compareTime($lt,$jsonData['data']['time'])===$lt){
                                $pngData = $newFO->imageToBase64(__SERVER_CONFIG__IMG__);
                                $sendJson = json_encode(['type'=>'send_serverImg','data'=>['string'=>$pngData,'time'=>$lt]]);
                                $connection->send($sendJson);
                            }
                        }
                        }
                        break;
                    }
                    //获取服务器的配置
                    case 'get_serverConfig':{
                        $sendArr=['type'=>'send_serverConfig','data'=>[
                            'key'=>__SERVER_CONFIG__KEY__,
                            'url'=>__SERVER_CONFIG__URL__,
                            'name'=>__SERVER_CONFIG__NAME__,
                            'online_number'=>getOnlineNumber(),
                            'max_online'=>__SERVER_CONFIG__MAX_USER__,
                            'max_height'=>__SERVER_CONFIG__MAX_HEIGHT__,
                            'min_height'=>__SERVER_CONFIG__MIN_HEIGHT__,
                            'max_width'=>__SERVER_CONFIG__MAX_WIDTH__,
                            'min_width'=>__SERVER_CONFIG__MIN_WIDTH__,
                            'unit1_y'=>__SERVER_CONFIG__UNIT1_Y__,
                            'offset_y'=>__SERVER_CONFIG__OFFSET_Y__,
                            'unit1_x'=>__SERVER_CONFIG__UNIT1_X__,
                            'offset_x'=>__SERVER_CONFIG__OFFSET_X__,
                            'default_x'=>__SERVER_CONFIG__DEFAULT_X__,
                            'default_y'=>__SERVER_CONFIG__DEFAULT_Y__,
                            'resolution_x'=>__SERVER_CONFIG__RESOLUTION_X__,
                            'resolution_y'=>__SERVER_CONFIG__RESOLUTION_Y__,
                            'p0_x'=>__SERVER_CONFIG__P0_X__,
                            'p0_y'=>__SERVER_CONFIG__P0_Y__,
                            'max_layer'=>__SERVER_CONFIG__MAX_LAYER__,
                            'min_layer'=>__SERVER_CONFIG__MIN_LAYER__,
                            'default_layer'=>__SERVER_CONFIG__DEFAULT_LAYER__,
                            'zoom_add'=>__SERVER_CONFIG__ZOOM_ADD__,
                            //底图
                            'enable_base_map'=>__SERVER_CONFIG__ENABLE_BASE_MAP__,
                            'base_map_type'=>__SERVER_CONFIG__BASE_MAP_TYPE__,
                            'max_zoom'=>__SERVER_CONFIG__MAX_ZOOM__,
                            'min_zoom'=>__SERVER_CONFIG__MIN_ZOOM__,
                            'default_zoom'=>__SERVER_CONFIG__DEFAULT_ZOOM__,
                            'base_map_url'=>__SERVER_CONFIG__BASE_MAP_URL__
                        ]];
                        $sendJson=json_encode($sendArr);
                        $connection->send($sendJson);
                        break;
                    }
                    //获取活动的数据
                    case 'get_activeData':{
                        $sendData=[
                            'type'=>'send_activeData',
                            'data'=>$theData['activeElement']
                        ];
                        $sendJson=json_encode($sendData);
                        $connection->send($sendJson);
                        break;
                    }
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
                        $logUserData=LoginServer($Email,$RealPws);
                        if($logUserData!==false){
                            //直接给该连接加入新属性
                            $connection->email=$Email;
                            $connection->userData=$logUserData;
                            //返回数据
                            $sendArr=['type'=>'loginStatus','data'=>true];
                            $sendJson=json_encode($sendArr);
                            $connection->send($sendJson);
                            $logData=['connectionId'=>$connection->id,'userEmail'=>$Email];
                            //登录用户数据
                            //广播上线通知
                            foreach ($socket_worker->connections as $con) {
                                if(property_exists($con,'email')){
                                    $sendArrB=['type'=>'broadcast','class'=>'logIn','data'=>$logUserData];
                                    $sendJsonB=json_encode($sendArrB);
                                    $con->send($sendJsonB);
                                }
                            }
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
                    //获取个人用户数据
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
                    //获取图层数据
                    case 'get_mapLayer':{
                        if(property_exists($connection,'email')){//必须是非匿名会话才能使用
                            $ref=$newMDBE->getLayerData();
                            $sendArr=['type'=>'send_mapLayer','data'=>$ref];
                            $sendJson=json_encode($sendArr);
                            $connection->send($sendJson);
                        }
                        break;
                    }
                    //获取在线用户数据
                    case 'get_presence':{
                        if(property_exists($connection,'email')) {//必须是非匿名会话才能使用
                            $ref=array();
                            foreach ($socket_worker->connections as $con) {
                                if(property_exists($con,'email')){
                                    if(property_exists($con,'userData')){//去重
                                        $lock=false;
                                        foreach ($ref as $it){
                                            if($con->userData==$it) {
                                                $lock=true;
                                                break;
                                            }
                                        }
                                        if($lock===false){
                                            array_push($ref,$con->userData);
                                        }
                                    }
                                }
                            }
                            $sendArr=['type'=>'send_presence','data'=>$ref];//返回数据
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
    global $socket_worker,$theData;
    $nowConId=$connection->id;
    if(property_exists($connection,'userData')) {//非匿名用户
        $userEmail=$connection->userData['userEmail'];
        $lock=false;//检测该用户是否还有在其他页面或设备有登录，有则跳过此下线广播
        foreach ($socket_worker->connections as $con) {
            if(property_exists($con,'email')){
                if($con->id===$connection->id){
                    continue;
                }
                if($con->userData['userEmail']===$userEmail){
                    $lock=true;
                    break;
                }
            }
        }
        if($lock===false){
            foreach ($socket_worker->connections as $con) {//下线广播
                if(property_exists($con,'email')){
                    if($con->email===$userEmail){continue;}
                    $sendArr=['type'=>'broadcast','class'=>'logOut','data'=>['email'=>$userEmail]];
                    $sendJson=json_encode($sendArr);
                    $con->send($sendJson);
                }
            }
        }
        //清除其选中的元素
        foreach ($theData['activeElement'] as $key=>$value){
            if($value['pick']!==null){
                if($value['pick']['email']===$userEmail){
                    $theData['activeElement'][$key]['pick']=null;
                    $ID=substr($key,1);
                    $Time=creatDate();
                    $sendArr=['type'=>'broadcast','class'=>'pickEndElement','conveyor'=>$userEmail,'time'=>$Time,'data'=>$ID];
                    $sendJson=json_encode($sendArr);
                    foreach ($socket_worker->connections as $con) {//更改成功则广播所有人
                        if(property_exists($con,'email')){//避免发送给匿名用户
                            if($con->email != ''){
                                $con->send($sendJson);
                            }
                        }
                    }
                }
            }
            if($value['select']!==null){
                if($value['select']['email']===$userEmail){
                    $theData['activeElement'][$key]['select']=null;
                    $ID=substr($key,1);
                    $Time=creatDate();
                    $sendArr=['type'=>'broadcast','class'=>'selectEndElement','conveyor'=>$userEmail,'time'=>$Time,'data'=>$ID];
                    $sendJson=json_encode($sendArr);
                    foreach ($socket_worker->connections as $con) {//更改成功则广播所有人
                        if(property_exists($con,'email')){//避免发送给匿名用户
                            if($con->email != ''){
                                $con->send($sendJson);
                            }
                        }
                    }
                }
            }
            if($theData['activeElement'][$key]['pick']===null && $theData['activeElement'][$key]['select']===null){
                $index=array_search($key,$theData['activeElement']);
                array_splice($theData['activeElement'],$index,1);
            }
        }
    }else{//匿名用户
        $userEmail='匿名';
    }
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
        $PHPMailerObj->Body='<h1>【ATSW】您正在注册ATSW-Map账号，验证码：<span style="color: #323232;font-weight: 800">'.$code.'</span>，请勿向任何人泄露，若非本人操作，请忽略此邮件。</h1>'.date('Y-m-d H:i:s');
        $PHPMailerObj->AltBody='【ATSW】您正在注册ATSW-Map账号，验证码：'.$code.'，请勿向任何人泄露，若非本人操作，请忽略此邮件。'.date('Y-m-d H:i:s');
        $PHPMailerObj->send();
        echo '验证码邮件发送成功，注册者：'.$qqNumber;
        return $code;
    } catch (Exception $e) {
        echo '验证码邮件发送失败，注册者: '.$qqNumber, $PHPMailerObj->ErrorInfo;
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
//获取当前在线人数
function getOnlineNumber(){
    global $socket_worker;
    $num=0;
    foreach ($socket_worker->connections as $con){
        if(property_exists($con,'email')){
            if($con->email!=''){
                $num++;
            }
        }
    }
    return $num;
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

{$time}--连接Id为:{$logData['connectionId']};连接IP为:{$logData['connectionIp']};匿名用户连接

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
        case 'userAddArea':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Email为:{$logData['broadcastEmail']};新增一个区域:{$logData['addId']}

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
        case 'userAddLine':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Email为:{$logData['broadcastEmail']};新增一条线:{$logData['addId']}

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
        case 'restoreElement':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Email为:{$logData['broadcastEmail']};恢复一个元素:{$logData['id']}

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
        case 'updateElementNode':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Email为:{$logData['broadcastEmail']};更新一个元素节点:{$logData['id']}

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
        case 'warn':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};连接IP为:{$logData['connectionIp']};恶意连接

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
        default:{}
    }
}
//发送错误信息
function sendError($class,$data,$message,$con){
    $broadcastEmail=$con->email;
    $dateTime=creatDate();
    $sendJson=json_encode(['type'=>'send_error','class'=>$class,'conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>['source'=>$data,'message'=>$message]]);
    $con->send($sendJson);
}
//发送成功信息
function sendCorrect($class,$data,$con){
    $broadcastEmail=$con->email;
    $dateTime=creatDate();
    $sendJson=json_encode(['type'=>'send_correct','class'=>$class,'conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$data]);
    $con->send($sendJson);
}
/**
</internal-procedures>
 **/

/**
<worker-setting>
 **/
if(__SERVER_SSL_STA__){
    $context=array('ssl'=>array('local_cert'=>__SERVER_SSL_CRT__,'local_pk'=>__SERVER_SSL_KEY__,'verify_peer'=>false));
    $socket_worker=new Worker('websocket://'.__SERVER_IP_PORT__,$context);
    $socket_worker->transport='ssl';
}else{
    $socket_worker=new Worker('websocket://'.__SERVER_IP_PORT__);
}
$socket_worker->count=1;
$socket_worker->onConnect='handle_connection';
$socket_worker->onMessage='handle_message';
$socket_worker->onClose='handle_close';
//初始化设置：
//1.对长时间未登录的会话进行断开连接
//2.限制恶意的连接Ip
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
    Timer::add(2 * 60,
        function() {
            global $theData;
            $theData['ip_connect_times'] = [];  // 2分钟清理一次
        }
    );
};
Worker::runAll();
/**
</worker-setting>
 **/