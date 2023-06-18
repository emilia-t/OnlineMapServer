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
use Workerman\Protocols\Http\Response;
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
//文件处理类
require './class/FileOperation.php';
/**
</external-program>
 **/

/**
<class>
 **/
$newQIR=new QualityInspectionRoom(false);
$newJDT=new JsonDisposalTool();
$newMDBE=new MapDataBaseEdit($mysql_public_sheet_name);
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
    'ip_connect_times'=>[]
];
$theConfig=[
    'globalId'=>0,
    'time'=>date('m-j'),
    'logFile'=>null,
    'typeList'=>['get_serverImg','get_serverConfig','broadcast','get_publickey','login','publickey','loginStatus','get_userData','send_userData','get_mapData','send_mapData','get_presence','send_presence']
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
//收到客户端消息
function handle_message($connection, $data){
    global $theData,$theConfig,$socket_worker,$newQIR,$newJDT,$newMDBE,$newFO;
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
                    //广播数据
                    case 'broadcast':{
                        if(property_exists($connection,'email')) {//必须是非匿名会话才能使用
                            //0.class检测
                            if(array_key_exists('class',$jsonData)){
                                //1.提取类型
                                $nowClass=$jsonData['class'];
                                switch ($nowClass){
                                    //广播A1位置(此类型需要后续删除)
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
                                    //新增区域
                                    case 'area':{
                                        //0.构建默认数据内容
                                        $basicStructure=['id'=>'temp','type'=>'area','points'=>[],'point'=>null,'color'=>'','length'=>null,'width'=>2,'size'=>null,'child_relations'=>[],'father_relation'=>'','child_nodes'=>[],'father_node'=>'','details'=>null];
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
                                            //发送广播的emilia
                                            $broadcastEmail=$connection->email;
                                            //时间
                                            $dateTime=creatDate();
                                            //组合
                                            $sdJson=['type'=>'broadcast','class'=>'area','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$basicStructure];
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
                                            createLog('userAddArea',$logData);
                                        }
                                        break;
                                    }
                                    //新增线段
                                    case 'line':{
                                        //0.构建默认数据内容
                                        $basicStructure=['id'=>'temp','type'=>'line','points'=>[],'point'=>null,'color'=>'','length'=>null,'width'=>2,'size'=>null,'child_relations'=>[],'father_relation'=>'','child_nodes'=>[],'father_node'=>'','details'=>null];
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
                                            //发送广播的emilia
                                            $broadcastEmail=$connection->email;
                                            //时间
                                            $dateTime=creatDate();
                                            //组合
                                            $sdJson=['type'=>'broadcast','class'=>'line','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$basicStructure];
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
                                            createLog('userAddLine',$logData);
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
                                        if($newMDBE->updateElementData($nodeObject)){
                                            if($newQIR->arrayPropertiesCheck('type',$jsonData['data'])){
                                                $nodeObject['type']=$jsonData['data']['type'];
                                            }
                                            //广播给登录用户
                                            //发送广播的emilia
                                            $broadcastEmail=$connection->email;
                                            //时间
                                            $dateTime=creatDate();
                                            //组合
                                            $sdJson=['type'=>'broadcast','class'=>'updateElementNode','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$nodeObject];
                                            //返回数据
                                            $sendJson=json_encode($sdJson);
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
                            'max_zoom'=>__SERVER_CONFIG__MAX_ZOOM__,
                            'min_zoom'=>__SERVER_CONFIG__MIN_ZOOM__,
                            'default_zoom'=>__SERVER_CONFIG__DEFAULT_ZOOM__,
                            'base_map_url'=>__SERVER_CONFIG__BASE_MAP_URL__
                        ]];
                        $sendJson=json_encode($sendArr);
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
    global $socket_worker,$newQIR;
    $nowConId=$connection->id;
    if(property_exists($connection,'userData')) {//非匿名用户
        $userEmail=$connection->userData['userEmail'];
        //广播
        foreach ($socket_worker->connections as $con) {
            if(property_exists($con,'email')){
                if($con->email===$userEmail){continue;}
                $sendArr=['type'=>'broadcast','class'=>'logOut','data'=>['email'=>$userEmail]];
                $sendJson=json_encode($sendArr);
                $con->send($sendJson);
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