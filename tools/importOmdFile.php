<?php
/**
 * This script is used to store OMD data into your database
 *  precondition:
 * 1. There needs to be an OMD file with a version not lower than 1.0. Additionally, you need to rename this file to 'data.omd' and place it in the OMD folder
 * 2. You need to enter the database root password
 **/
/**
 * 所需变量
 */
$Mysqli=null;
echo "请输入mysql root密码：";
$RootPassword = trim(fgets(STDIN));
$DatabaseName='map';
$MapSerial='0';
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
    insertElement($omdData['mapData']['points'][$i]);
}
for($j=0;$j<$lineLength;$j++){
    insertElement($omdData['mapData']['lines'][$j]);
}
for($k=0;$k<$areaLength;$k++){
    insertElement($omdData['mapData']['areas'][$k]);
}
for($m=0;$m<$curveLength;$m++){
    insertElement($omdData['mapData']['curves'][$m]);
}
/**
 * 3.添加图层数据-逐条
 */
for($l=0;$l<$layerLength;$l++){
    insertLayer($omdData['layerData'][$l]);
}

/**
 * 所需函数
 */
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
    linkDatabase();
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