<?php
/*
----------注释标准(Annotation standards)----------
普通注释，在每行末尾。(Normal annotation,at the end of each line.)：
$variableInitial=2;//初始数值(Initial value)
if(variableInitial<2){//条件注释(conditional comments)
    return false;
}

较长注释，在语句之前。(Long comments before statements)：
/*1.解码...
 *2.转化...
 *3.发送...
 * /

函数注释，在函数声明之前。(Function annotation, before function declaration.)
/**测试函数(test)
 * @param $id | int
 * @return int
 * /
function test($id){
    return$id;
}
----------注释标准(Annotation standards)----------
*/

/**
<php-config>
 **/
error_reporting(E_ALL);//设置报错等级
date_default_timezone_set('Asia/Hong_Kong');//设置时区
ini_set('extension', 'pdo_mysql');//启用PDO
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
require 'Workerman/Autoloader.php';
//require 'PHPMailer/src/Exception.php';
//require 'PHPMailer/src/PHPMailer.php';
//require 'PHPMailer/src/SMTP.php';
require 'config/Server_config.php';//服务器配置
require 'api/Public_getPublickey.php';//获取公钥与私钥的API
require 'api/Other_RsaTranslate.php';//解密和加密功能
require 'api/Public_LoginServer.php';//登录验证功能
require 'api/Public_GetUserData.php';//查询用户数据
require 'api/Public_GetMapData.php';//查询地图数据
require 'api/Other_createMysqlDataBase.php';//创建数据库
require 'class/QualityInspectionRoom.php';//通用数据检测工具
require 'class/JsonDisposalTool.php';//json工具
require 'class/MapDataBaseEdit.php';//地图数据库编辑工具
require 'class/FileOperation.php';//文件处理类
require 'class/instruct.php';//指令集合
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
$instruct=new instruct(true,true);
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
    'userDataStructure'=>[
        'userEmail'=>null,
        'userQq'=>null,
        'userName'=>null,
        'headColor'=>null,
    ],
    'globalId'=>0,
    'time'=>date('m-j'),
    'logFile'=>null,
    'typeList'=>[
        'get_serverImg','get_serverConfig','broadcast','get_publickey','login','publickey',
        'loginStatus','get_userData','send_userData','get_mapData','send_mapData','get_presence',
        'send_presence','get_activeData','send_activeData','send_error','send_correct','get_mapLayer',
        'send_mapLayer'
    ],
];
if (!file_exists('log')) {
    mkdir('log', 0777, true);
    echo "文件夹 'log' 创建成功;";
}
$theConfig['logFile']=fopen('./log/'.$theConfig['time'].'.txt','a+');
/**
</data>
 **/

/**
<internal-procedures>
 **/
/**初始化设置
 * @return bool
 */
function startSetting(){
    global $theConfig;
    if(__ANONYMOUS_LOGIN__===true){//允许匿名登录指令
        array_push($theConfig['typeList'],'anonymousLogin');
    }
    return true;
}
/**与客户端连接
 * @param $connection
 * @return void
 */
function handle_connection($connection){
    global $theData;
    $ip=$connection->getRemoteIp();
    if(!isset($theData['ip_connect_times'][$ip])){//当客户端连接上之后需要携带至少1个参数表达意愿
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

/**与客户端通讯
 * @param $connection
 * @param $data
 * @return false
 */
function handle_message($connection, $data){//收到客户端消息
    global $theData,$theConfig,$socket_worker,$newQIR,$newJDT,$newMDBE,$newFO,$instruct;
    $jsonData=$newJDT->checkJsonData($data);//1.校验并解析json格式
    if(gettype($jsonData)==='array'){//2.检测是否为数组类型
    if(array_key_exists('type',$jsonData)){//3.检测是否存在必要属性 'type'
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
                                            foreach ($socket_worker->connections as $con) {
                                                //避免发送给匿名用户
                                                if(property_exists($con,'email')){
                                                    if($con->email != ''){
                                                        $con->send($sendJson);
                                                    }
                                                }
                                            }
                                            sendCorrect('upload',['vid'=>$jsonData['data']['id'],'rid'=>$newId],$connection);//返回成功指令
                                            $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$broadcastEmail,'addId'=>$newId];
                                            createLog('userAddArea',$logData);
                                        }else{
                                            sendError('upload',$jsonData['data'],'database upload fail',$connection);
                                        }
                                        break;
                                    }
                                    case 'line':{//新增线段
                                        $basicStructure=['id'=>'temp','type'=>'line','points'=>[],'point'=>null,'color'=>'','phase'=>1,'width'=>2,'child_relations'=>[],'father_relation'=>'','child_nodes'=>[],'father_node'=>'','details'=>null];//0.构建默认数据内容
                                        if(!$newQIR->arrayPropertiesCheck('data',$jsonData)){break;}//1.检查是否包含data键名
                                        if(!$newQIR->getDataType($jsonData['data'])=='array'){break;}//2.检查data是否为数组
                                        if(!$newQIR->arrayPropertiesCheck('point',$jsonData['data'])){break;}//3.检查是否包含point属性
                                        if(!$newQIR->arrayPropertiesCheck('x',$jsonData['data']['point']) OR !$newQIR->arrayPropertiesCheck('y',$jsonData['data']['point'])){break;}//4.检查point是否包含xy属性
                                        if(!$newQIR->digitalCheck($jsonData['data']['point']['x']) OR !$newQIR->digitalCheck($jsonData['data']['point']['y'])){break;}//5.检查xy值是否为数字
                                        if(!$newQIR->arrayPropertiesCheck('points',$jsonData['data'])){break;}//6.1检查是否存在points属性
                                        if(!$newQIR->getDataType($jsonData['data']['points'])=='array'){break;}//6.2检查points是否是一个数组
                                        $pointsLock=false;
                                        for($i=0;$i<count($jsonData['data']['points']);$i++){//6.3循环检查内部
                                            if(!$newQIR->arrayPropertiesCheck('x',$jsonData['data']['points'][$i])){$pointsLock=true;break;}//6.3.1检查是否存在xy
                                            if(!$newQIR->arrayPropertiesCheck('y',$jsonData['data']['points'][$i])){$pointsLock=true;break;}
                                            if(!$newQIR->digitalCheck($jsonData['data']['points'][$i]['x'])){$pointsLock=true;break;}//6.3.2检查xy是否为数字
                                            if(!$newQIR->digitalCheck($jsonData['data']['points'][$i]['y'])){$pointsLock=true;break;}
                                        }
                                        if($pointsLock){
                                            break;
                                        }
                                        if($newQIR->arrayPropertiesCheck('color',$jsonData['data'])){//7.检查color是否存在，存在则检查，不存在则设置默认值
                                            if($newQIR->color16Check($jsonData['data']['color'])){//7.1检查颜色格式是否正确
                                                $basicStructure['color']=$jsonData['data']['color'];
                                            }else{
                                                $basicStructure['color']='ffffff';
                                            }
                                        }else{
                                            $basicStructure['color']='ffffff';
                                        }
                                        if($newQIR->arrayPropertiesCheck('width',$jsonData['data'])){//8.检查是否存在width，并检查是否为数字
                                            if($newQIR->digitalCheck($jsonData['data']['width'])){//8.1检查是否是数字，不是数字则改写为默认值
                                                $basicStructure['width']=$jsonData['data']['width'];
                                            }else{
                                                $basicStructure['width']=2;
                                            }
                                        }else{
                                            $basicStructure['width']=2;
                                        }
                                        if($newQIR->arrayPropertiesCheck('details',$jsonData['data'])){//9.1检查details是否存在
                                            if($newQIR->getDataType($jsonData['data']['details'])=='array'){//9.2检查details数据结构是否为数组
                                                foreach ($jsonData['data']['details'] as $value){
                                                    if ($newQIR->arrayPropertiesCheck('key',$value) AND $newQIR->arrayPropertiesCheck('value',$value)){//9.3检查该项键值对是否存在
                                                        if(!$newQIR->commonKeyNameCheck($value['key']) OR !$newQIR->illegalCharacterCheck($value['value'])){//9.4检查键名和键值是否正常
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
                                        /*全部检查完毕
                                         *上传至数据库
                                         */
                                        $updateStatus=$newMDBE->uploadLineData($basicStructure);
                                        if($updateStatus===true){//更新成功后写入日志文件并广播给其他用户
                                            $newId=$newMDBE->selectNewId();//查询刚才加入的数据的id
                                            $basicStructure['id']=$newId;//更改basic id
                                            $broadcastEmail=$connection->email;//发送广播的email
                                            $dateTime=creatDate();//时间
                                            $sdJson=['type'=>'broadcast','class'=>'line','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$basicStructure];
                                            $sendJson=json_encode($sdJson);//返回数据
                                            foreach ($socket_worker->connections as $con){
                                                if(property_exists($con,'email')){//避免发送给匿名用户
                                                    if($con->email != ''){
                                                        $con->send($sendJson);
                                                    }
                                                }
                                            }
                                            sendCorrect('upload',['vid'=>$jsonData['data']['id'],'rid'=>$newId],$connection);//返回成功指令
                                            $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$broadcastEmail,'addId'=>$newId];
                                            createLog('userAddLine',$logData);
                                        }else{
                                            sendError('upload',$jsonData['data'],'database upload fail',$connection);
                                        }
                                        break;
                                    }
                                    case 'point':{//广播新增点
                                        $basicStructure=['id'=>'temp','type'=>'point','points'=>[],'point'=>null,'color'=>'','phase'=>1,'width'=>2,'child_relations'=>[],'father_relation'=>'','child_nodes'=>[],'father_node'=>'','details'=>[],'custom'=>null];//0.构建默认数据内容
                                        if(!$newQIR->arrayPropertiesCheck('data',$jsonData)){break;}//1.检查是否包含data键名
                                        if(!$newQIR->getDataType($jsonData['data'])=='array'){break;}//2.检查data是否为数组
                                        if(!$newQIR->arrayPropertiesCheck('point',$jsonData['data'])){break;}//3.检查是否包含point属性
                                        if(!$newQIR->arrayPropertiesCheck('x',$jsonData['data']['point']) OR !$newQIR->arrayPropertiesCheck('y',$jsonData['data']['point'])){break;}//4.检查point是否包含xy属性                                        //$pwd jsonData['data']['point']['x']/['y']
                                        if(!$newQIR->digitalCheck($jsonData['data']['point']['x']) OR !$newQIR->digitalCheck($jsonData['data']['point']['y'])){break;}//5.检查xy值是否为数字
                                        if($newQIR->arrayPropertiesCheck('color',$jsonData['data'])){//6.检查color是否存在，存在则检查，不存在则设置默认值
                                            if($newQIR->color16Check($jsonData['data']['color'])){//6.1检查颜色格式是否正确
                                                $basicStructure['color']=$jsonData['data']['color'];
                                            }else{
                                                $basicStructure['color']='ffffff';
                                            }
                                        }else{
                                            $basicStructure['color']='ffffff';
                                        }
                                        if($newQIR->arrayPropertiesCheck('width',$jsonData['data'])){//7.检查是否存在width，并检查是否为数字
                                            if($newQIR->digitalCheck($jsonData['data']['width'])){//7.1检查是否是数字，不是数字则改写为默认值
                                                $basicStructure['width']=$jsonData['data']['width'];
                                            }else{
                                                $basicStructure['width']=2;
                                            }
                                        }else{
                                            $basicStructure['width']=2;
                                        }
                                        if($newQIR->arrayPropertiesCheck('details',$jsonData['data'])){//8.1检查details是否存在
                                            if($newQIR->getDataType($jsonData['data']['details'])=='array'){//8.2检查details数据结构是否为数组
                                                foreach ($jsonData['data']['details'] as $value){
                                                    if ($newQIR->arrayPropertiesCheck('key',$value) AND $newQIR->arrayPropertiesCheck('value',$value)){//8.3检查该项键值对是否存在
                                                        if(!$newQIR->commonKeyNameCheck($value['key']) OR !$newQIR->illegalCharacterCheck($value['value'])){//8.4检查键名和键值是否正常
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
                                        $basicStructure['points']=$newJDT->btoa($newJDT->jsonPack([$jsonData['data']['point']]));
                                        $basicStructure['details']=$newJDT->btoa($newJDT->jsonPack($jsonData['data']['details']));
                                        $basicStructure['custom']=$newJDT->btoa($newJDT->jsonPack($jsonData['data']['custom']));
                                        /*全部检查完毕
                                         *上传至数据库
                                         */
                                        $updateStatus=$newMDBE->uploadPointData($basicStructure);
                                        if($updateStatus===true){//更新成功后写入日志文件并广播给其他用户
                                            $newId=$newMDBE->selectNewId();//查询刚才加入的数据的id
                                            $basicStructure['id']=$newId;//更改basic id
                                            $broadcastEmail=$connection->email;//发送广播的email
                                            $dateTime=creatDate();
                                            $sdJson=['type'=>'broadcast','class'=>'point','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$basicStructure];//组合
                                            $sendJson=json_encode($sdJson);//返回数据
                                            foreach ($socket_worker->connections as $con) {
                                                if(property_exists($con,'email')){//避免发送给匿名用户
                                                    if($con->email != ''){
                                                        $con->send($sendJson);
                                                    }
                                                }
                                            }
                                            sendCorrect('upload',['vid'=>$jsonData['data']['id'],'rid'=>$newId],$connection);//返回成功指令
                                            $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$broadcastEmail,'addId'=>$newId];
                                            createLog('userAddPoint',$logData);
                                        }else{
                                            sendError('upload',$jsonData['data'],'database upload fail',$connection);
                                        }
                                        break;
                                    }
                                    case 'deleteElement':{//删除元素
                                        try{
                                            $conveyor=$connection->email;
                                            $ID=$jsonData['data']['id'];
                                            $Time=creatDate();
                                            $sendArr=['type'=>'broadcast','class'=>'deleteElement','conveyor'=>$conveyor,'time'=>$Time,'data'=>['id'=>$ID]];
                                            $sendJson=json_encode($sendArr);
                                            $updateStatus=$newMDBE->updateElementPhase($ID,2);//更改数据库
                                            if($updateStatus===true){//更改成功则广播所有人
                                                sendCorrect('delete',['rid'=>$ID],$connection);
                                                foreach ($socket_worker->connections as $con) {
                                                    if(property_exists($con,'email')){//避免发送给匿名用户
                                                        if($con->email != ''){
                                                            $con->send($sendJson);
                                                        }
                                                    }
                                                }
                                            }else{
                                                sendError('delete',$jsonData['data'],'database delete fail',$connection);
                                            }
                                            $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$conveyor,'id'=>$ID];//写入日志
                                            createLog('deleteElement',$logData);
                                        }catch (Exception $E){
                                            print_r('未知错误：deleteElement收到不明信息:');
                                            print_r($jsonData);
                                        }
                                        break;
                                    }
                                    case 'textMessage':{//普通文本消息
                                        $dateTime=creatDate();
                                        $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$connection->email,'text'=>$jsonData['data']['message']];
                                        $sendArr = ['type'=>'broadcast','class'=>'textMessage','conveyor'=>$connection->email,'time'=>$dateTime,'data'=>$jsonData['data']];
                                        $sendJson = json_encode($sendArr);
                                        createLog('textMessage',$logData);
                                        foreach ($socket_worker->connections as $con) {
                                            if(property_exists($con,'email')){//避免发送给匿名用户
                                                $con->send($sendJson);//普通文本消息不会上传服务器,这里会将汉字之类的转化为base64
                                            }
                                        }
                                        break;
                                    }
                                    case 'updateElement':{//更新元素
                                        $details=[];//0.1details
                                        $custom=null;
                                        $basicStructure=[];//0.2构建默认数据内容
                                        if(!$newQIR->arrayPropertiesCheck('data',$jsonData)){break;}//1.检查是否包含data键名
                                        if(!$newQIR->getDataType($jsonData['data'])=='array'){break;}//2.检查data是否为数组
                                        if(!$newQIR->arrayPropertiesCheck('id',$jsonData['data'])){break;}//2.5检查id是否存在
                                        if($newQIR->digitalCheck($jsonData['data']['id'])){//2.6检查id是否为数字，是则加入，否则退出case
                                            $basicStructure['id']=$jsonData['data']['id'];
                                        }else{
                                            echo '所更新的id包含非数字字符';
                                            break;
                                        }
                                        if(!$newQIR->arrayPropertiesCheck('type',$jsonData['data'])){break;}//2.7检查type是否存在
                                        if($newQIR->elementTypeCheck($jsonData['data']['type'])){//2.7.检查type是否正确
                                            $basicStructure['type']=$jsonData['data']['type'];
                                        }else{
                                            echo '所更新的id元素类型不符标准';
                                            break;
                                        }
                                        if(!$newQIR->arrayPropertiesCheck('changes',$jsonData['data'])){break;}//3.检查changes是否存在
                                        if(!$newQIR->getDataType($jsonData['data']['changes'])=='array'){break;}//4.检查changes是否为数组
                                        if($newQIR->arrayPropertiesCheck('color',$jsonData['data']['changes'])){//5.检查color是否存在，存在则检查
                                            if($newQIR->color16Check($jsonData['data']['changes']['color'])){//5.1检查颜色格式是否正确
                                                $basicStructure['color']=$jsonData['data']['changes']['color'];
                                            }
                                        }
                                        if($newQIR->arrayPropertiesCheck('width',$jsonData['data']['changes'])){//6.检查是否存在width，并检查是否为数字
                                            if($newQIR->digitalCheck($jsonData['data']['changes']['width'])){//6.1检查是否是数字,，存在则检查
                                                $basicStructure['width']=$jsonData['data']['changes']['width'];
                                            }
                                        }
                                        if($newQIR->arrayPropertiesCheck('details',$jsonData['data']['changes'])){//7.1检查details是否存在
                                            if($newQIR->getDataType($jsonData['data']['changes']['details'])=='array'){//7.2检查details数据结构是否为数组
                                                foreach ($jsonData['data']['changes']['details'] as $value){
                                                    if ($newQIR->arrayPropertiesCheck('key',$value) AND
                                                        $newQIR->arrayPropertiesCheck('value',$value)){//7.3检查该项键值对是否存在
                                                        if($newQIR->commonKeyNameCheck($value['key']) AND
                                                            $newQIR->illegalCharacterCheck($value['value'])){//8.4检查键名和键值是否正常，如果不正常则跳出当前循环case
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
                                        /*全部检查完毕
                                         *打包$details
                                         */
                                        if(count($details)!=0){
                                            $basicStructure['details']=$newJDT->btoa($newJDT->jsonPack($details));
                                        }
                                        $basicStructure['custom']=$newJDT->btoa($newJDT->jsonPack($custom));
                                        $isSuccess=$newMDBE->updateElementData($basicStructure);//上传数据库
                                        if($isSuccess){//更新成功后广播
                                            $broadcastEmail=$connection->email;
                                            $dateTime=creatDate();
                                            $sdJson=['type'=>'broadcast','class'=>'updateElement','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$basicStructure];//组合
                                            $sendJson=json_encode($sdJson);//返回数据
                                            sendCorrect('updateElement',['vid'=>$jsonData['data']['updateId'],'rid'=>$jsonData['data']['id']],$connection);//发送成功信息
                                            foreach ($socket_worker->connections as $con){
                                                if(property_exists($con,'email')){//避免发送给匿名用户
                                                    if($con->email!=''){
                                                        $con->send($sendJson);
                                                    }
                                                }
                                            }
                                            $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$broadcastEmail,'id'=>$basicStructure['id']];//写入日志文件
                                            createLog('updateElement',$logData);
                                        }else{//更新失败
                                            sendError('updateElement',$jsonData['data'],'database error',$connection);
                                        }
                                        break;
                                    }
                                    case 'updateElementNode':{//更新元素节点
                                        $nodeObject=[];
                                        if($newQIR->getDataType($jsonData['data'])!='array'){break;}//1.检查
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
                                            $nodeObject['id']=$jsonData['data']['id'];
                                            $nodeObject['points']=$jsonData['data']['points'];
                                            if($newQIR->arrayPropertiesCheck('point',$jsonData['data'])){ //判断是否存在point
                                                if($newQIR->getDataType($jsonData['data']['point'])=='array'){//检查是否为数组
                                                    if($newQIR->arrayPropertiesCheck('x',$jsonData['data']['point']) && $newQIR->arrayPropertiesCheck('y',$jsonData['data']['point'])){//检查是否存在xy
                                                        if($newQIR->digitalCheck($jsonData['data']['point']['x']) && $newQIR->digitalCheck($jsonData['data']['point']['y'])){//检查是否为数字
                                                            $nodeObject['point']=[];
                                                            $nodeObject['point']['x']=$jsonData['data']['point']['x'];
                                                            $nodeObject['point']['y']=$jsonData['data']['point']['y'];
                                                            $nodeObject['point']=$newJDT->jsonPack($nodeObject['point']);
                                                            $nodeObject['point']=$newJDT->btoa($nodeObject['point']);//转化为base64
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        $nodeObject['points']=$newJDT->jsonPack($nodeObject['points']);//打包为json
                                        $nodeObject['points']=$newJDT->btoa($nodeObject['points']);//转化为base64
                                        $isSuccess=$newMDBE->updateElementData($nodeObject);//上传数据库
                                        if($isSuccess){
                                            if($newQIR->arrayPropertiesCheck('type',$jsonData['data'])){
                                                $nodeObject['type']=$jsonData['data']['type'];
                                            }
                                            $broadcastEmail=$connection->email;//发送广播的email
                                            $dateTime=creatDate();
                                            $sdJson=['type'=>'broadcast','class'=>'updateElementNode','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$nodeObject];//组合
                                            $sendJson=json_encode($sdJson);//返回数据
                                            sendCorrect('updateNode',['vid'=>$jsonData['data']['updateId'],'rid'=>$jsonData['data']['id']],$connection);//发送成功信息
                                            foreach ($socket_worker->connections as $con){
                                                if(property_exists($con,'email')){//避免发送给匿名用户
                                                    //if($con->email==$broadcastEmail){//如果不希望发送给广播者本人则启用
                                                    //    continue;
                                                    //}
                                                    if($con->email!=''){
                                                        $con->send($sendJson);
                                                    }
                                                }
                                            }
                                            $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$broadcastEmail,'id'=>$nodeObject['id']];//写入日志文件
                                            createLog('updateElementNode',$logData);
                                        }else{
                                            sendError('updateNode',$jsonData['data'],'update node fail',$connection);
                                        }
                                        break;
                                    }
                                    case 'selectIngElement':{//选中要素
                                        $ID=$jsonData['data'];
                                        $userName=$connection->userData['user_name'];
                                        $headColor=$connection->userData['head_color'];
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
                                    case 'selectEndElement':{//取消选中要素
                                        $ID=$jsonData['data'];
                                        $userName=$connection->userData['user_name'];
                                        $email=$connection->email;
                                        $Lock=array_key_exists('E'.$ID,$theData['activeElement']);
                                        if($Lock){//判断此要素是否有人已经右键选中了
                                            if($theData['activeElement']['E'.$ID]['select']!==null){
                                                if($theData['activeElement']['E'.$ID]['select']['email']!==$email){
                                                    return false;
                                                }else{
                                                    $theData['activeElement']['E'.$ID]['select']=null;
                                                    if($theData['activeElement']['E'.$ID]['pick']===null){
                                                        unset($theData['activeElement']['E'.$ID]);
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
                                        foreach ($socket_worker->connections as $con) {//更改成功则广播所有人
                                            if(property_exists($con,'email')){//避免发送给匿名用户
                                                if($con->email != ''){
                                                    $con->send($sendJson);
                                                }
                                            }
                                        }
                                        break;
                                    }
                                    case 'pickIngElement':{//选中要素pick
                                        $ID=$jsonData['data'];
                                        $userName=$connection->userData['user_name'];
                                        $headColor=$connection->userData['head_color'];
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
                                    case 'pickEndElement':{//取消选中要素
                                        $ID=$jsonData['data'];
                                        $userName=$connection->userData['user_name'];
                                        $email=$connection->email;
                                        $Lock=array_key_exists('E'.$ID,$theData['activeElement']);
                                        if($Lock){//判断此要素是否有人已经选中了
                                            if($theData['activeElement']['E'.$ID]['pick']!==null){
                                                if($theData['activeElement']['E'.$ID]['pick']['email']!==$email){
                                                    return false;
                                                }else{
                                                    $theData['activeElement']['E'.$ID]['pick']=null;
                                                    if($theData['activeElement']['E'.$ID]['select']===null){
                                                        unset($theData['activeElement']['E'.$ID]);
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
                                        foreach ($socket_worker->connections as $con) {//更改成功则广播所有人
                                            if(property_exists($con,'email')){//避免发送给匿名用户
                                                if($con->email != ''){
                                                    $con->send($sendJson);
                                                }
                                            }
                                        }
                                        break;
                                    }
                                    case 'restoreElement':{//恢复被删除的元素
                                        try{
                                            $ID=$jsonData['data'];
                                            $elementData=$newMDBE->getElementById($ID);
                                            if($elementData===false){
                                                return false;
                                            }
                                            $broadcastEmail=$connection->email;
                                            $Time=creatDate();
                                            $sdJson=['type'=>'broadcast','class'=>$elementData['type'],'conveyor'=>$broadcastEmail,'time'=>$Time,'data'=>$elementData];
                                            $correctJson=['type'=>'send_correct','class'=>'upload','conveyor'=>$broadcastEmail,'time'=>$Time,'data'=>['rid'=>$ID,'vid'=>'restore']];
                                            $sendJson=json_encode($sdJson);
                                            $sendCorrectJson=json_encode($correctJson);
                                            $updateStatus=$newMDBE->updateElementPhase($ID,1);//更新元素周期
                                            if($updateStatus===true){
                                                foreach ($socket_worker->connections as $con) {
                                                    if(property_exists($con,'email')){//避免发送给匿名用户
                                                        if($con->email != ''){
                                                            $con->send($sendJson);
                                                        }
                                                    }
                                                }
                                                $connection->send($sendCorrectJson);
                                            }
                                            $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$broadcastEmail,'id'=>$ID];//写入日志
                                            createLog('restoreElement',$logData);
                                        }catch (Exception $E){
                                            print_r('未知错误：restoreElement收到不明信息:');
                                            print_r($jsonData);
                                        }
                                        break;
                                    }
                                    case 'updateLayerData':{//更新图层数据
                                        $basicStructure=[];
                                        $hasStructure=false;
                                        $hasMembers=false;
                                        $hasLevel=false;
                                        if($newQIR->arrayPropertiesCheck('id',$jsonData['data'])){
                                            $basicStructure['id']=$jsonData['data']['id'];
                                        }else{
                                            break;
                                        }
                                        if($newQIR->arrayPropertiesCheck('structure',$jsonData['data'])){
                                            $basicStructure['structure']=$newJDT->btoa($newJDT->jsonPack($jsonData['data']['structure']));
                                            $newQIR->layerStructureCheck($jsonData['data']['structure']);
                                            $hasStructure=true;
                                        }
                                        if($newQIR->arrayPropertiesCheck('members',$jsonData['data'])){
                                            $basicStructure['members']=$newJDT->btoa($newJDT->jsonPack($jsonData['data']['members']));
                                            $hasMembers=true;
                                        }
                                        if($hasStructure===false && $hasMembers===false && $hasLevel===false){
                                            break;
                                        }
                                        $updateStatus=$newMDBE->updateLayerData($basicStructure);
                                        $broadcastEmail=$connection->email;
                                        $Time=creatDate();
                                        $sdJson=['type'=>'broadcast','class'=>'updateLayerData','conveyor'=>$broadcastEmail,'time'=>$Time,'data'=>$basicStructure];
                                        $sendJson=json_encode($sdJson);
                                        if($updateStatus===true){
                                            foreach ($socket_worker->connections as $con) {
                                                if(property_exists($con,'email')){//避免发送给匿名用户
                                                    if($con->email != ''){
                                                        $con->send($sendJson);
                                                    }
                                                }
                                            }
                                        }
                                        break;
                                    }
                                    case 'updateLayerOrder':{//更新图层排序
                                        $oldMembers=$newMDBE->getOrderLayerData();
                                        $oldMembers=json_decode($oldMembers['members']);
                                        if(!$newQIR->arrayPropertiesCheck('passive',$jsonData['data'])){break;}
                                        if(!$newQIR->arrayPropertiesCheck('active',$jsonData['data'])){break;}
                                        if(!$newQIR->arrayPropertiesCheck('type',$jsonData['data'])){break;}
                                        $passive=(int)$jsonData['data']['passive'];
                                        $active=(int)$jsonData['data']['active'];
                                        $nowType=$jsonData['data']['type'];
                                        if(!in_array($passive,$oldMembers)){break;}
                                        if(!in_array($active,$oldMembers)){break;}
                                        $newMember=adjustLayerOrder($oldMembers,$passive,$active,$nowType);
                                        if($newMember!=$oldMembers){
                                            $newOrderMemberStr=$newMDBE->adjustOrderLayerData($newMember);
                                            if($newOrderMemberStr!==false){
                                                $broadcastEmail=$connection->email;
                                                $Time=creatDate();
                                                $sdJson=['type'=>'broadcast','class'=>'updateLayerOrder','conveyor'=>$broadcastEmail,'time'=>$Time,'data'=>['members'=>$newOrderMemberStr]];
                                                $sendJson=json_encode($sdJson);
                                                foreach ($socket_worker->connections as $con) {
                                                    if(property_exists($con,'email')){//避免发送给匿名用户
                                                        if($con->email != ''){
                                                            $con->send($sendJson);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        break;
                                    }
                                    case 'createGroupLayer':{//新建分组图层
                                        $basicStructure=['type'=>'group'];
                                        if($newQIR->arrayPropertiesCheck('members',$jsonData['data'])){
                                            $basicStructure['members']=$newJDT->btoa($newJDT->jsonPack($jsonData['data']['members']));
                                        }else{
                                            break;
                                        }
                                        if($newQIR->arrayPropertiesCheck('structure',$jsonData['data'])){
                                            $basicStructure['structure']=$newJDT->btoa($newJDT->jsonPack($jsonData['data']['structure']));
                                            $newQIR->layerStructureCheck($jsonData['data']['structure']);
                                        }else{
                                            break;
                                        }
                                        $updateStatus=$newMDBE->createLayerData($basicStructure);
                                        if($updateStatus===true){
                                            $broadcastEmail=$connection->email;
                                            $Time=creatDate();
                                            $newLayerId=$newMDBE->selectNewLayerId()+0;
                                            $newOrderMembers=$newMDBE->updateOrderLayerData($newLayerId,'push');
                                            $sendOrderJson=['type'=>'broadcast','class'=>'updateLayerOrder','conveyor'=>$broadcastEmail,'time'=>$Time,'data'=>['members'=>$newOrderMembers]];
                                            $sendOrderJson=json_encode($sendOrderJson);
                                            $basicStructure['id']=$newLayerId;
                                            $sdJson=['type'=>'broadcast','class'=>'createGroupLayer','conveyor'=>$broadcastEmail,'time'=>$Time,'data'=>$basicStructure];
                                            $sendJson=json_encode($sdJson);
                                            foreach ($socket_worker->connections as $con) {
                                                if(property_exists($con,'email')){//避免发送给匿名用户
                                                    if($con->email != ''){
                                                        $con->send($sendJson);
                                                        $con->send($sendOrderJson);
                                                    }
                                                }
                                            }
                                        }
                                        break;
                                    }
                                    case 'deleteLayer':{//删除图层
                                        if($newQIR->arrayPropertiesCheck('data',$jsonData)){
                                        if($newQIR->arrayPropertiesCheck('id',$jsonData['data'])){
                                            $deleteId=$jsonData['data']['id']+0;
                                            $updateStatus=$newMDBE->updateLayerPhase($deleteId,2);
                                            if($updateStatus===true){
                                                $broadcastEmail=$connection->email;
                                                $Time=creatDate();
                                                $newOrderMembers=$newMDBE->updateOrderLayerData($deleteId,'remove');
                                                $sendOrderJson=['type'=>'broadcast','class'=>'updateLayerOrder','conveyor'=>$broadcastEmail,'time'=>$Time,'data'=>['members'=>$newOrderMembers]];
                                                $sendOrderJson=json_encode($sendOrderJson);
                                                $sendDeleteJson=['type'=>'broadcast','class'=>'deleteLayer','conveyor'=>$broadcastEmail,'time'=>$Time,'data'=>['id'=>$jsonData['data']['id']]];
                                                $sendDeleteJson=json_encode($sendDeleteJson);
                                                foreach ($socket_worker->connections as $con) {
                                                    if(property_exists($con,'email')){
                                                        if($con->email != ''){
                                                            $con->send($sendDeleteJson);
                                                            $con->send($sendOrderJson);
                                                        }
                                                    }
                                                }
                                                $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$connection->email,'id'=>$jsonData['data']['id']];
                                                createLog('deleteLayer',$logData);
                                            }
                                        }
                                        }
                                        break;
                                    }
                                    case 'deleteLayerAndMembers':{//删除图层
                                        if($newQIR->arrayPropertiesCheck('data',$jsonData)){
                                            if($newQIR->arrayPropertiesCheck('id',$jsonData['data'])){
                                                $deleteId=$jsonData['data']['id']+0;
                                                $updateLayerStatus=$newMDBE->updateLayerPhase($deleteId,2);
                                                if($updateLayerStatus===true){
                                                    $updateMembers=$newMDBE->updateLayerMembersPhase($deleteId,2);
                                                    if($updateMembers!==false){
                                                        $broadcastEmail=$connection->email;
                                                        $Time=creatDate();
                                                        $newOrderMembers=$newMDBE->updateOrderLayerData($deleteId,'remove');
                                                        $sendOrderJson=['type'=>'broadcast','class'=>'updateLayerOrder','conveyor'=>$broadcastEmail,'time'=>$Time,'data'=>['members'=>$newOrderMembers]];
                                                        $sendOrderJson=json_encode($sendOrderJson);
                                                        $sendDeleteJson=['type'=>'broadcast','class'=>'deleteLayerAndMembers','conveyor'=>$broadcastEmail,'time'=>$Time,'data'=>['id'=>$jsonData['data']['id'],'members'=>$updateMembers]];
                                                        $sendDeleteJson=json_encode($sendDeleteJson);
                                                        foreach ($socket_worker->connections as $con) {
                                                            if(property_exists($con,'email')){
                                                                if($con->email != ''){
                                                                    $con->send($sendDeleteJson);
                                                                    $con->send($sendOrderJson);
                                                                }
                                                            }
                                                        }
                                                        $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$connection->email,'id'=>$jsonData['data']['id']];
                                                        createLog('deleteLayerAndMembers',$logData);
                                                    }
                                                }
                                            }
                                        }
                                        break;
                                    }
                                    case 'forceUpdate':{//强制刷新客户端
                                        $Time=creatDate();
                                        $broadcastEmail=$connection->email;
                                        $sendObj=['type'=>'broadcast','class'=>'forceUpdate','conveyor'=>$broadcastEmail,'time'=>$Time];
                                        $sendJson=json_encode($sendObj);
                                        /*权限判断
                                         *需要进行账户权限判断
                                         */
                                        foreach ($socket_worker->connections as $con) {
                                            if(property_exists($con,'email')){
                                                if($con->email != ''){
                                                    $con->send($sendJson);
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
            case 'get_serverImg':{//获取服务器展示图像
                if($newQIR->arrayPropertiesCheck('data',$jsonData)){//1检查是否包含一个time的参数
                if($newQIR->arrayPropertiesCheck('time',$jsonData['data'])){
                    $lt = $newFO->getFileModifiedTime(__SERVER_CONFIG__IMG__);//获取map_img的最近修改时间
                    /*与客户端进行对比如果返回false说明相同或时间格式错误，
                    *则返回空数据，如果返回客户端落后于服务端则返回新数据
                    */
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
            case 'get_serverConfig':{//获取服务器的配置
                        $sendArr=['type'=>'send_serverConfig','data'=>[
                            /*
                             *以下为服务器属性配置信息
                             */
                            'anonymous_login'=>__ANONYMOUS_LOGIN__,
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
                            /*底图
                             *以下为关于额外底图的配置信息
                             */
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
            case 'get_activeData':{//获取元素活动的数据
                        $sendData=[
                            'type'=>'send_activeData',
                            'data'=>$theData['activeElement']
                        ];
                        $sendJson=json_encode($sendData);
                        $connection->send($sendJson);
                        break;
                    }
            case 'get_publickey':{//获取公钥数据
                        $sendArr=['type'=>'publickey','data'=>getPublickey()];
                        $sendJson=json_encode($sendArr);
                        $connection->send($sendJson);
                        break;
                    }
            case 'login':{//登录
                        $Email=$jsonData['data']['email'];
                        $Password=$jsonData['data']['password'];
                        if(!$instruct->ckLogonAccount($Email,$Password)){
                            break;
                        }
                        $RealPws=RsaTranslate($Password,'decode');//1.解密
                        $logUserData=LoginServer($Email,$RealPws);//2.数据库进行查询
                        if($logUserData!==false){
                            $connection->email=$Email;//直接给该连接加入新属性
                            $connection->userData=$logUserData;
                            $connection->send($instruct->loginStatus(true));//返回登录成功指令
                            foreach ($socket_worker->connections as $con) {//广播上线通知
                                if(property_exists($con,'email')){
                                    $con->send($instruct->broadcast_logIn($logUserData));
                                }
                            }
                            $logData=['connectionId'=>$connection->id,'userEmail'=>$Email];//日志
                            createLog('login',$logData);
                        }else{//查询为假,登录失败,返回数据
                            $connection->send($instruct->loginStatus(false));
                        }
                        break;
                    }
            case 'anonymousLogin':{
                if(!array_key_exists('data',$jsonData)){
                    break;
                }
                if(gettype($jsonData['data'])!=='array'){
                    break;
                }
                if(!array_key_exists('name',$jsonData['data'])){
                    break;
                }
                if(gettype($jsonData['data']['name'])!=='string'){
                    break;
                }
                $accountName=$jsonData['data']['name'];
                if(!$instruct->ckAnonymousLogonAccount($accountName)){
                    echo $accountName;
                    break;
                }else{
                    echo "??!";
                }
                if(findAccountName($accountName)){//true则已存在同样的名称
                    $connection->send($instruct->anonymousLoginStatus(false));
                }else{//不存在同样的名称
                    $connection->email=$accountName.'@anonymous';
                    $newUserData=$theConfig['userDataStructure'];
                    $newUserData['user_email']=$accountName.'@anonymous';
                    $newUserData['user_qq']='100006';
                    $newUserData['user_name']=$accountName;
                    $newUserData['head_color']=randomHexColor();
                    $connection->userData=$newUserData;
                    $connection->send($instruct->anonymousLoginStatus(true));
                    foreach ($socket_worker->connections as $con) {//广播上线通知
                        if(property_exists($con,'email')){
                            $con->send($instruct->broadcast_logIn($newUserData));
                        }
                    }
                    $logData=['connectionId'=>$connection->id,'userAccount'=>$accountName];//登录用户数据
                    createLog('anonymousLogin',$logData);
                }
                break;
            }
            case 'get_userData':{//获取个人用户数据
                        if(property_exists($connection,'email')) {//必须是非匿名会话才能使用
                            $theUserEmail = $connection->email;
                            if(substr($theUserEmail,-10)==='@anonymous'){//如果启用了匿名登录
                                $ref=$instruct->send_userData($connection->userData);
                                if($ref!==false){
                                    $connection->send($ref);
                                }
                            }else{
                                $userData = GetUserData($theUserEmail);
                                $ref=$instruct->send_userData($userData);
                                if($ref!==false){
                                    $connection->send($ref);
                                }
                            }
                        }
                        break;
                    }
            case 'get_mapData':{//获取地图数据
                        if(property_exists($connection,'email')){//必须是非匿名会话才能使用
                            $ref=GetMapData();
                            $sendArr=['type'=>'send_mapData','data'=>$ref];//返回数据
                            $sendJson=json_encode($sendArr);
                            $connection->send($sendJson);
                        }
                        break;
                    }
            case 'get_mapLayer':{//获取图层数据
                        if(property_exists($connection,'email')){//必须是非匿名会话才能使用
                            $ref=$newMDBE->getLayerData();
                            $sendArr=['type'=>'send_mapLayer','data'=>$ref];
                            $sendJson=json_encode($sendArr);
                            $connection->send($sendJson);
                        }
                        break;
                    }
            case 'get_presence':{//获取在线用户数据
                        if(property_exists($connection,'email')) {//必须是非匿名会话才能使用
                            $ref=array();
                            foreach ($socket_worker->connections as $con) {
                                if(property_exists($con,'email')){
                                    if(property_exists($con,'userData')){//去重步骤
                                        $lock=false;
                                        foreach ($ref as $item){
                                            if($con->userData==$item) {
                                                $lock=true;
                                                break;
                                            }
                                        }
                                        if($lock===false){
                                            $sourceData=$con->userData;
                                            $userData=[
                                                'userEmail'=>$sourceData['user_email'],
                                                'userQq'=>$sourceData['user_qq'],
                                                'userName'=>$sourceData['user_name'],
                                                'headColor'=>$sourceData['head_color'],
                                            ];
                                            array_push($ref,$userData);
                                        }
                                    }
                                }
                            }
                            $sendData=$instruct->send_presence($ref);
                            if($sendData!==false){
                                $connection->send($sendData);
                            }
                        }
                        break;
                    }
        }
    }
    }
    }
}

/**客户端断开连接
 * @param $connection
 * @return void
 */
function handle_close($connection){
    global $socket_worker,$theData;
    $nowConId=$connection->id;
    if(property_exists($connection,'userData')) {//非匿名用户
        $userEmail=$connection->userData['user_email'];
        $lock=false;//检测该用户是否还有在其他页面或设备有登录，有则跳过此下线广播
        foreach ($socket_worker->connections as $con) {
            if(property_exists($con,'email')){
                if($con->id===$connection->id){
                    continue;
                }
                if($con->userData['user_email']===$userEmail){
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
        foreach ($theData['activeElement'] as $key=>$value){//清除其选中的元素
            if($value['pick']!==null){
                if($value['pick']['email']===$userEmail){
                    $theData['activeElement'][$key]['pick']=null;
                    $ID=intval(substr($key,1));
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
                    $ID=intval(substr($key,1));
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

/**生成随机颜色
 * @return string
 */
function randomHexColor(){
    $red = dechex(rand(0, 255));
    $green = dechex(rand(0, 255));
    $blue = dechex(rand(0, 255));
    $red = strlen($red) === 1 ? '0' . $red : $red;
    $green = strlen($green) === 1 ? '0' . $green : $green;
    $blue = strlen($blue) === 1 ? '0' . $blue : $blue;
    return $red.$green.$blue;
}
/**查找用户名是否存在
 * @param $name
 * @return false
 */
function findAccountName($name){
    global $socket_worker;
    foreach ($socket_worker->connections as $con) {
        if(property_exists($con,'email')){
            if($con->userName===$name){
                return true;
            }
        }
    }
    return false;
}
/**调整图层排序
 * @param $arr
 * @param $passive
 * @param $active
 * @param $type
 * @return array
 */
function adjustLayerOrder($arr,$passive,$active,$type){
    $newArr=[];
    foreach($arr as $key=>$value){
        if($value==$passive){
            if($type=='up'){
                $newArr[]=$active;
            }
            $newArr[]=$passive;
            if($type=='down'){
                $newArr[]=$active;
            }
        }else if($value!=$active){
            $newArr[]=$value;
        }
    }
    return $newArr;
}

/**获取当前在线人数
 * @return int
 */
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

/**生成当前时间
 * @return string
 */
function creatDate(){
    $date=getdate();
    $mon=sprintf('%02d',$date['mon']);
    $day=sprintf('%02d',$date['mday']);
    $hours=sprintf('%02d',$date['hours']);
    $minutes=sprintf('%02d',$date['minutes']);
    $seconds=sprintf('%02d',$date['seconds']);
    return "{$date['year']}-{$mon}-{$day} {$hours}:{$minutes}:{$seconds}";
}

/**生成log
 * @param $logType
 * @param $logData
 * @return void
 */
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

{$time}--连接Id为:{$logData['connectionId']};Account为:{$logData['userEmail']};断开连接

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
        case 'login':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Account为:{$logData['userEmail']};用户登录

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
        case 'anonymousLogin':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Account为:{$logData['userAccount']};匿名登录

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
        case 'userAddArea':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Account为:{$logData['broadcastEmail']};新增一个区域:{$logData['addId']}

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
        case 'userAddPoint':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Account为:{$logData['broadcastEmail']};新增一个点:{$logData['addId']}

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
        case 'userAddLine':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Account为:{$logData['broadcastEmail']};新增一条线:{$logData['addId']}

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
        case 'textMessage':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Account为:{$logData['broadcastEmail']};发送一条消息:{$logData['text']}

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
        case 'deleteElement':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Account为:{$logData['broadcastEmail']};删除一个元素:{$logData['id']}

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
        case 'deleteLayer':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Account为:{$logData['broadcastEmail']};删除一个图层:{$logData['id']}

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
        case 'deleteLayerAndMembers':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Account为:{$logData['broadcastEmail']};删除一个图层及其成员:{$logData['id']}

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
        case 'restoreElement':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Account为:{$logData['broadcastEmail']};恢复一个元素:{$logData['id']}

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
        case 'updateElementNode':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Account为:{$logData['broadcastEmail']};更新一个元素节点:{$logData['id']}

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
        case 'updateElement':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Account为:{$logData['broadcastEmail']};更新一个元素:{$logData['id']}

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

/**发送错误信息
 * @param $class
 * @param $data
 * @param $message
 * @param $con
 * @return void
 */
function sendError($class,$data,$message,$con){
    $broadcastEmail=$con->email;
    $dateTime=creatDate();
    $sendJson=json_encode(['type'=>'send_error','class'=>$class,'conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>['source'=>$data,'message'=>$message]]);
    $con->send($sendJson);
}

/**发送成功信息
 * @param $class
 * @param $data
 * @param $con
 * @return void
 */
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
/*初始化设置：
 *1.对长时间未登录的会话进行断开连接
 *2.限制恶意的连接Ip
 */
$socket_worker->onWorkerStart=function ($socket_worker){
    Timer::add(10,
        function () use($socket_worker){
            global $newMDBE;
            $newMDBE->pdoHeartbeat();
            $time_now = time();
            foreach($socket_worker->connections as $connection){
                /*有可能该connection还没收到过消息，
                 *则lastMessageTime设置为当前时间
                 */
                if (empty($connection->lastMessageTime)) {
                    $connection->lastMessageTime = $time_now;
                    continue;
                }
                /*上次通讯时间间隔大于心跳间隔，
                 *则认为客户端已经下线，关闭连接
                 */
                if(!property_exists($connection,'email')){
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
            $theData['ip_connect_times']=[];//2分钟清理一次
        }
    );
};
startSetting();
Worker::runAll();
/**
</worker-setting>
 **/