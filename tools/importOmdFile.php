<?php
/**
 * This script is used to store OMD data into your mysql or sqlite database
 *  precondition:
 * 1. There needs to be an OMD file with a version not lower than 1.0. Additionally, you need to rename this file to 'data.omd' and place it in the OMD folder
 * 2. You need to enter the database root password(sqlite skip)
 **/
/**
 * 所需变量
 */
$Mysqli=null;
$sqlite=null;
$RootPassword=null;
$dbName='';
$DatabaseName='map';
$MapSerial='0';
$sqlite_db = './SQLite/data.sqlite'; // SQLite 数据库文件路径
echo "请输入数据库名称(sqlite or mysql)：\n";
$dbName = trim(fgets(STDIN));
if($dbName==='mysql'){
    echo "请输入mysql root密码：\n";
    $RootPassword = trim(fgets(STDIN));
    echo "正在执行\n";
}elseif($dbName==='sqlite'){
    echo "sqlite数据库是否已经创建且未在运行OMS服务？(y/n)\n";
    $sqliteStatus = trim(fgets(STDIN));
    if($sqliteStatus==="y" || $sqliteStatus==="Y"){
        echo "正在执行\n";
    }else{
        exit("已取消执行\n");
    }
}else{
    exit("不支持的数据库\n");
}
/**
 * 初始化
 */
startSetting();
/**
 * 1读取OMD文件夹内的omd文件
 */
$currentDirectory=dirname(__FILE__);//获取当前文件目录
$omdFilePath=$currentDirectory.'/OMD/data.omd';//拼接OMD文件路径
$omdFileContent=file_get_contents($omdFilePath);//读取OMD文件内容
$omdData=json_decode($omdFileContent,true);//将JSON解码为PHP数组
$layerLength=count($omdData['layerData']);
$pointLength=count($omdData['mapData']['points']);
$lineLength=count($omdData['mapData']['lines']);
$areaLength=count($omdData['mapData']['areas']);
$curveLength=count($omdData['mapData']['curves']);
/**
 * 2.添加地图数据-逐条
 */
for($i=0;$i<$pointLength;$i++){
    if($dbName==='sqlite'){
        insertElementSqlite($omdData['mapData']['points'][$i]);
    }else{
        insertElement($omdData['mapData']['points'][$i]);
    }
}
for($j=0;$j<$lineLength;$j++){
    if($dbName==='sqlite'){
        insertElementSqlite($omdData['mapData']['lines'][$j]);
    }else{
        insertElement($omdData['mapData']['lines'][$j]);
    }
}
for($k=0;$k<$areaLength;$k++){
    if($dbName==='sqlite'){
        insertElementSqlite($omdData['mapData']['areas'][$k]);
    }else{
        insertElement($omdData['mapData']['areas'][$k]);
    }
}
for($m=0;$m<$curveLength;$m++){
    if($dbName==='sqlite'){
        insertElementSqlite($omdData['mapData']['curves'][$m]);
    }else{
        insertElement($omdData['mapData']['curves'][$m]);
    }
}
/**
 * 3.添加图层数据-逐条
 */
for($l=0;$l<$layerLength;$l++){
    if($dbName==='sqlite'){
        insertLayerSqlite($omdData['layerData'][$l]);
    }else{
        insertLayer($omdData['layerData'][$l]);
    }
}
echo "执行成功\n";
/**
 * 所需函数
 */

function linkDatabaseSqlite(){
    global $sqlite_db,$sqlite;
    try {
        $sqlite = new PDO("sqlite:$sqlite_db");
        $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }catch (PDOException $e){
        echo "数据库操作失败: " . $e->getMessage();
        exit("");
    }
}

function insertLayerSqlite($layer){
    global $sqlite;
    $id = intval($layer['id']);
    $type = $layer['type'];
    $phase = intval($layer['phase']);
    if($type === 'group'){
        $members = jsonStringify($layer['members']);
        $structure = jsonStringify($layer['structure']);
        $sql = "REPLACE INTO map_0_layer(id, type, members, structure, phase)VALUES(:value1, :value2, :value3, :value4, :value5)";
        $stmt = $sqlite->prepare($sql);// 预处理 SQL 语句
        $stmt->bindParam(':value1', $id);// 绑定参数
        $stmt->bindParam(':value2', $type);
        $stmt->bindParam(':value3', $members);
        $stmt->bindParam(':value4', $structure);
        $stmt->bindParam(':value5', $phase);
        $stmt->execute();// 执行查询
    } elseif ($type === 'order') {
        $members = jsonStringify($layer['members']);
        $sql = "REPLACE INTO map_0_layer(id, type, members, structure, phase)VALUES(:value1, :value2, :value3, '', :value5)";
        $stmt = $sqlite->prepare($sql);// 预处理 SQL 语句
        $stmt->bindParam(':value1', $id);// 绑定参数
        $stmt->bindParam(':value2', $type);
        $stmt->bindParam(':value3', $members);
        $stmt->bindParam(':value5', $phase);
        $stmt->execute();// 执行查询
    }
}

function insertElementSqlite($element){
    global $sqlite;
    $id = intval($element['id']);
    $type = $element['type'];
    $point = json_encode($element['point']);
    $points = json_encode($element['points']);
    $color = $element['color'];
    $phase = intval($element['phase']);
    $width = intval($element['width']);
    $details = json_encode($element['details']);
    $custom = json_encode($element['custom']);
    $sql="REPLACE INTO map_0_data(id, type, point, points, color, phase, width, details, custom)VALUES(:value1, :value2, :value3, :value4, :value5, :value6, :value7, :value8, :value9)";
    $stmt = $sqlite->prepare($sql);// 预处理 SQL 语句
    $stmt->bindParam(':value1', $id);// 绑定参数
    $stmt->bindParam(':value2', $type);
    $stmt->bindParam(':value3', $point);
    $stmt->bindParam(':value4', $points);
    $stmt->bindParam(':value5', $color);
    $stmt->bindParam(':value6', $phase);
    $stmt->bindParam(':value7', $width);
    $stmt->bindParam(':value8', $details);
    $stmt->bindParam(':value9', $custom);
    $stmt->execute();// 执行查询
}

function insertLayer($layer){
    global $Mysqli,$MapSerial;
    $id=intval($layer['id']);
    $type=$layer['type'];
    $phase=$layer['phase'];
    if($type==='group'){
        $members=jsonStringify($layer['members']);
        $structure=jsonStringify($layer['structure']);
        $sql="REPLACE INTO map_{$MapSerial}_layer (
          id,type,members,structure,phase) VALUES (
          $id,'$type','$members','$structure',$phase
)";
        $Mysqli->query($sql);
    }elseif ($type==='order'){
        $members=jsonStringify($layer['members']);
        $sql="REPLACE INTO map_{$MapSerial}_layer (
          id,type,members,structure,phase) VALUES (
          $id,'$type','$members','',$phase
)";
        $Mysqli->query($sql);
    }

}
function insertElement($element){
    global $Mysqli,$MapSerial;
    $id=intval($element['id']);
    $type=$element['type'];
    $point=jsonStringify($element['point']);
    $points=jsonStringify($element['points']);
    $color=$element['color'];
    $phase=$element['phase'];
    $width=$element['width'];
    $details=jsonStringify($element['details']);
    $custom=jsonStringify($element['custom']);
    $sql="REPLACE INTO map_{$MapSerial}_data (
          id,type,point,points,color,phase,width,details,custom) VALUES (
          $id,'$type','$point','$points','$color',$phase,$width,'$details','$custom'
)";
    $Mysqli->query($sql);
}
function startSetting(){
    global $dbName;
    if($dbName==='sqlite'){
        linkDatabaseSqlite();
    }else{
        linkDatabase();
    }
}
function jsonStringify($value=[]){
    try {
        return json_encode($value,JSON_UNESCAPED_UNICODE);
    }catch (Exception $e){
        return false;
    }
}
function btoa($str=''){
    try {
        return base64_encode($str);
    }catch (Exception $e){
        return false;
    }
}
function linkDatabase(){
    global $Mysqli,$RootPassword,$DatabaseName;
    $Mysqli=new mysqli('localhost','root',$RootPassword,$DatabaseName);
    if($Mysqli->connect_error){
        die("连接数据库失败".$Mysqli->connect_error);
    }
}