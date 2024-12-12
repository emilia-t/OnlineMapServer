<?php
/**
<php-config>
 **/
error_reporting(E_ALL);//设置报错等级
date_default_timezone_set('Asia/Hong_Kong');//设置时区
ini_set('extension', 'pdo_mysql');//启用PDO
/**
</php-config>
 **/
$Version = '0.7';
################Script initial execution################
require 'config/Server_config.php';
require 'Aconst.php';

/**上传缓存-sqlite
 * @return int status
 */
function uploadCacheSqlite(){
    if(__DATABASE_NAME__==='mysql'){
        return uploadCache();
    }
    $filePath='./cache/layerDataCache.json';
    $file=null;
    if(!file_exists($filePath)){//检查是否存在文件
        $file=fopen('./cache/layerDataCache.json','w+');
        fclose($file);return 1;
    }
    $content=file_get_contents($filePath);//获取文件内容
    if($content===false || strlen($content)===0){//文件内容为空
        $file=fopen('./cache/layerDataCache.json','w+');
        fclose($file);return 2;
    }
    $legacyData=json_decode($content,true);//解析文件数据
    if($legacyData===null){//解析数据失败
        $file=fopen('./cache/layerDataCache.json','w+');
        fclose($file);return 3;
    }
    if(!is_array($legacyData)){//数据类型错误
        $file=fopen('./cache/layerDataCache.json','w+');
        fclose($file);return 4;
    }
    /*
     * 上传旧数据至数据库
     */
    $sqlite_db = './tools/SQLite/data.sqlite'; // SQLite 数据库文件路径
    $sqlite = null;
    try {
        $sqlite = new PDO("sqlite:$sqlite_db");
        $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "成功连接到 SQLite 数据库！";
    } catch (PDOException $e) {
        echo "数据库连接失败: " . $e->getMessage();
        exit(__LANGUAGE__==='chinese'?"[异常退出]缓存数据无法上传至数据库，因为连接sqlite数据库失败\n":"[Exception exit] Cache data cannot be uploaded to the database because the connection to the SQLite database failed\n");
    }
    foreach($legacyData as $key=>$item){
        if($item['hasChange']===false){continue;}
        $id=$item['id'];
        $type=$item['type'];
        $members=$item['members'];
        $structure=$item['structure'];
        $phase=$item['phase'];
        if($type!=='order'){//普通图层
            $members=json_encode($members,JSON_FORCE_OBJECT);
            $structure=json_encode($structure,JSON_UNESCAPED_UNICODE);
        }else{
            $members=json_encode($members,JSON_UNESCAPED_UNICODE);
            $structure='""';
        }
        $searchSql="SELECT id FROM map_0_layer WHERE id={$id}";
        $search = $sqlite->query($searchSql);
        if($search){
            $searchRow=$search->fetchAll(PDO::FETCH_ASSOC);
            $row=count($searchRow);
            if($row!=0){//更新数据
                $updateSQL = "UPDATE map_0_layer SET members = :value1, structure = :value2 , phase = :value3 WHERE id = :id";// 更新数据的 SQL 语句
                $stmt = $sqlite->prepare($updateSQL);// 预处理 SQL 语句
                $stmt->bindParam(':value1', $value1);// 绑定参数
                $stmt->bindParam(':value2', $value2);
                $stmt->bindParam(':value3', $value3);
                $stmt->bindParam(':id', $id);
                $value1 = $members;// 设置参数值
                $value2 = $structure;
                $value3 = $phase;
                $status=$stmt->execute();// 执行更新
                if($status){
                    echo "[缓存上传]更新图层(".$type.$id.")数据成功\n\n";
                }else{
                    echo "[缓存上传]更新图层(".$type.$id.")数据失败\n\n";
                }
            }
            else{//插入数据
                $insertSQL = "INSERT INTO map_0_layer ('id', 'type', 'members', 'structure', 'phase') VALUES (:value1, :value2, :value3, :value4, :value5)";// 插入数据的 SQL 语句
                $stmt = $sqlite->prepare($insertSQL);// 预处理 SQL 语句
                $stmt->bindParam(':value1', $value1);// 绑定参数
                $stmt->bindParam(':value2', $value2);
                $stmt->bindParam(':value3', $value3);
                $stmt->bindParam(':value4', $value4);
                $stmt->bindParam(':value5', $value5);
                $value1 = $id;// 设置参数值
                $value2 = $type;
                $value3 = $members;
                $value4 = $structure;
                $value5 = $phase;
                $status=$stmt->execute();// 执行插入
                if($status){
                    echo "[缓存上传]新增图层(".$type.$id.")数据成功\n\n";
                }else{
                    echo "[缓存上传]新增图层(".$type.$id.")数据失败\n\n";
                }
            }
        }
    }
    $file=fopen('./cache/layerDataCache.json','w+');//清空缓存
    fclose($file);
    return 10;
}
/**上传缓存
 * @return int status
 */
function uploadCache(){
    if(__DATABASE_NAME__==='sqlite'){
        return uploadCacheSqlite();
    }
    function getUpdateLayerDataSql($id,$members,$structure,$phase,$mysql_public_layer_name){
        return "UPDATE " . $mysql_public_layer_name . " 
SET members=" . $members . ",
       structure=" . $structure . ",
       phase=" . $phase . "
WHERE id=" . $id;
    }
    function getInsertLayerDataSql($id,$type,$members,$structure,$phase,$mysql_public_layer_name){
        return "INSERT INTO " . $mysql_public_layer_name . "  VALUES ({$id},'{$type}',{$members},{$structure},{$phase})";
    }
    global $mysql_public_server_address,$mysql_root_password,$mysql_public_layer_name,$mysql_public_db_name;
    $filePath='./cache/layerDataCache.json';
    $file=null;
    if(!file_exists($filePath)){//检查是否存在文件
        $file=fopen('./cache/layerDataCache.json','w+');
        fclose($file);return 1;
    }
    $content=file_get_contents($filePath);//获取文件内容
    if($content===false || strlen($content)===0){//文件内容为空
        $file=fopen('./cache/layerDataCache.json','w+');
        fclose($file);return 2;
    }
    $legacyData=json_decode($content,true);//解析文件数据
    if($legacyData===null){//解析数据失败
        $file=fopen('./cache/layerDataCache.json','w+');
        fclose($file);return 3;
    }
    if(!is_array($legacyData)){//数据类型错误
        $file=fopen('./cache/layerDataCache.json','w+');
        fclose($file);return 4;
    }
    /*
     * 上传旧数据至数据库
     */
    $conn=mysqli_connect($mysql_public_server_address,'root',$mysql_root_password,$mysql_public_db_name);
    if(!$conn){
        mysqli_close($conn);
        exit(__LANGUAGE__==='chinese'?"[异常退出]缓存数据无法上传至数据库，因为root连接数据库失败\n":"[Exception exit] Cache data cannot be uploaded to the database because root connection to the database failed\n");
    }
    foreach($legacyData as $key=>$item){
        if($item['hasChange']===false){continue;}
        $id=$item['id'];
        $type=$item['type'];
        $members=$item['members'];
        $structure=$item['structure'];
        $phase=$item['phase'];
        if($type!=='order'){//普通图层
            $members="'".json_encode($members,JSON_UNESCAPED_UNICODE)."'";
            $structure="'".json_encode($structure,JSON_UNESCAPED_UNICODE)."'";
        }else{
            $members="'".json_encode($members,JSON_UNESCAPED_UNICODE)."'";
            $structure='""';
        }
        $searchSql="SELECT id FROM {$mysql_public_layer_name} WHERE id={$id}";
        $search=mysqli_query($conn,$searchSql);//用于检查是否已经存在此图层，存在则更新否则插入
        if($search){
            $row=mysqli_num_rows($search);
            if($row!=0){//更新数据
                $sql=getUpdateLayerDataSql($id,$members,$structure,$phase,$mysql_public_layer_name);//type自创建之初即不可改变
                if(mysqli_query($conn,$sql)){
                    echo "[缓存上传]更新图层(".$type.$id.")数据成功\n\n";
                }else{
                    echo "[缓存上传]更新图层(".$type.$id.")数据失败\n\n";
                }
            }
            else{//插入数据
                $sql=getInsertLayerDataSql($id,$type,$members,$structure,$phase,$mysql_public_layer_name);
                if(mysqli_query($conn,$sql)){
                    echo "[缓存上传]新增图层(".$type.$id.")数据成功\n\n";
                }else{
                    echo "[缓存上传]新增图层(".$type.$id.")数据失败\n\n";
                }
            }
        }
    }
    $file=fopen('./cache/layerDataCache.json','w+');//清空缓存
    fclose($file);
    mysqli_close($conn);
    return 10;
}
/**创建数据库
 * @return bool
 */
function createMapDatabase(){
    global $mysql_public_server_address,$mysql_root_password,$mysql_public_db_name;
    $sql=getCreateMapDatabaseSql($mysql_public_db_name);
    $conn=mysqli_connect($mysql_public_server_address,'root',$mysql_root_password);
    if(!$conn){
        mysqli_close($conn);
        return false;
    }
    if(mysqli_multi_query($conn,$sql)){//执行SQL语句
        mysqli_close($conn);
        return true;
    }else{
        mysqli_close($conn);
        return false;
    }
}
/**赋予公共账号数据库权限
 * @return bool
 */
function grantPublicAuthority(){
    global $mysql_public_db_name,$mysql_public_user,$mysql_public_server_hostname,$mysql_public_server_address,$mysql_root_password;
    $sql=getGrantPublicAccountPermissionsSql($mysql_public_db_name,$mysql_public_user,$mysql_public_server_hostname);
    $conn=mysqli_connect($mysql_public_server_address,'root',$mysql_root_password);
    if(!$conn){
        mysqli_close($conn);
        return false;
    }
    if(mysqli_multi_query($conn,$sql)){//执行SQL语句
        mysqli_close($conn);
        return true;
    }else{
        mysqli_close($conn);
        return false;
    }
}
/**创建数据库SQLite
 * @return bool
 */
function createSqliteDatabase() {
    $dbFilePath = './tools/SQLite/data.sqlite'; // 数据库文件地址
    // 1. 检查 "./tools/SQLite/data.sqlite" 文件是否存在，如果存在则返回 false
    if (file_exists($dbFilePath)) {
        echo __LANGUAGE__==='chinese'?"数据库创建失败: 因为数据库文件已经存在\n":"Database creation failed: because the database file already exists\n";
        return false;
    }
    try {
        // 2. 创建 SQLite 数据库文件并建立连接
        $pdo = new PDO('sqlite:' . $dbFilePath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // 3. 创建 account_data 表
        $createAccountDataTable = getCreateAccountDataTableSqlite();
        $pdo->exec($createAccountDataTable);
        // 4. 创建 map_0_data 表
        $createMap0DataTable = getCreateMap0DataTableSqlite();
        $pdo->exec($createMap0DataTable);
        // 5. 创建 map_0_layer 表
        $createMap0LayerTable = getCreateMap0LayerTableSqlite();
        $pdo->exec($createMap0LayerTable);
        // 6. 插入默认的账户数据到 account_data 表
        $insertDefaultAccount = getInsertDefaultAccountSql();
        $pdo->exec($insertDefaultAccount);
        // 7. 插入默认的图层数据到map_0_layer表
        $insertDefaultOrder = getInsertDefaultOrderSql();
        $pdo->exec($insertDefaultOrder);
        // 7. 插入默认的图层数据到map_0_layer表
        $insertDefaultGroup = getInsertDefaultGroupSql();
        $pdo->exec($insertDefaultGroup);
        return true;
    } catch (PDOException $e) {
        // 如果出现异常，返回 false 并输出错误信息
        echo "数据库创建失败: " . $e->getMessage();
        return false;
    }
}
/**检测数据库是否创建SQLite
 * @return bool
 */
function checkSqliteDatabase(){
    $dbFilePath = './tools/SQLite/data.sqlite';
    if (!file_exists($dbFilePath)) {// 1. 检查 "./tools/SQLite/data.sqlite" 文件是否存在，如果不存在则返回 false
        return false;
    }
    try {// 2. 尝试连接 data.sqlite 数据库文件，如果连接失败则返回 false
        $pdo = new PDO('sqlite:' . $dbFilePath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // 3. 尝试获取所有的表，并检查是否包含 account_data，map_0_data，map_0_layer
        $requiredTables = ['account_data', 'map_0_data', 'map_0_layer'];
        $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
        $existingTables = $result->fetchAll(PDO::FETCH_COLUMN);
        foreach ($requiredTables as $table) {
            if (!in_array($table, $existingTables)) {
                return false;
            }
        }
    } catch (PDOException $e) {
        return false;
    }
    return true;
}
/**检测数据库map是否创建root下进行
 * @return bool
 */
function checkMapDatabase(){
    global $mysql_public_server_address,$mysql_root_password,$mysql_public_db_name;
    $conn=mysqli_connect($mysql_public_server_address,'root',$mysql_root_password);
    if(!$conn){
        mysqli_close($conn);
        return false;
    }
    $selected_db=mysqli_select_db($conn,$mysql_public_db_name);
    if($selected_db){//检测是否存在此数据库
        mysqli_close($conn);
        return true;
    }else{
        mysqli_close($conn);
        return false;
    }
}

/**检测数据库root账号是否密码正确
 * @return bool
 */
function checkRootAccount(){
    global $mysql_public_server_address,$mysql_root_password;
    $conn=mysqli_connect($mysql_public_server_address,'root',$mysql_root_password);
    if(!$conn){//检查连接是否成功
        mysqli_close($conn);
        return false;
    }else{
        mysqli_close($conn);
        return true;
    }
}
/**检测数据库public账号是否密码正确
 * @return bool
 */
function checkPublicAccount(){
    global $mysql_public_server_address,$mysql_public_user,$mysql_public_password;
    $conn=mysqli_connect($mysql_public_server_address,$mysql_public_user,$mysql_public_password);
    if(!$conn){//检查连接是否成功
        mysqli_close($conn);
        return false;
    }else{
        mysqli_close($conn);
        return true;
    }
}
echo "\n\n";////////////////////////////////////////////////////////////////////////////////////////
echo __LANGUAGE__==='chinese'?"中文Chinese\n":"English英文\n";
echo __LANGUAGE__==='chinese'?"数据库名称：".__DATABASE_NAME__."\n":"Database name : ".__DATABASE_NAME__."\n";
echo __LANGUAGE__==='chinese'?"在线地图服务 版本 {$Version}\n":"OnlineMapServer Version {$Version}\n";
echo __LANGUAGE__==='chinese'?"(c) Minxi Wan，保留所有权利。\n":"(c) Minxi Wan All right reserved.\n";
echo __LANGUAGE__==='chinese'?"请稍等！服务将在5秒后启动。\n":"Please wait ! the service will start in 5 seconds.\n";
usleep(5000000);
echo "\n\n";////////////////////////////////////////////////////////////////////////////////////////
echo __LANGUAGE__==='chinese'?"① 将图层缓存上传到数据库..\n":"① upload layer cache to database...\n";
if(uploadCache()===10){
    echo __LANGUAGE__==='chinese'?"上传图层缓存已完成。\n":"upload layer cache done.\n";
}else{
    echo __LANGUAGE__==='chinese'?"没有任何变化(跳过)。\n":"there is no change(skip).\n";
}
echo "\n\n";////////////////////////////////////////////////////////////////////////////////////////
echo __LANGUAGE__==='chinese' ? "② PHP 拓展检查中...\n" : "② php extension checking...\n";
if (extension_loaded('pcntl')) { // PCNTL check
    echo __LANGUAGE__==='chinese' ? "PCNTL 拓展已加载。\n" : "PCNTL extension is loaded.\n";
} else {
    echo __LANGUAGE__==='chinese' ? "PCNTL 拓展未加载。\n" : "PCNTL extension is not loaded.\n";
    echo __LANGUAGE__==='chinese' ? "建议安装 PCNTL 拓展并启用它。\n" : "Suggest refactoring and enabling PCNTL.\n";
}
if (__DATABASE_NAME__ === 'sqlite') {
    if (extension_loaded('pdo_sqlite')) { // pdo_sqlite check
        echo __LANGUAGE__==='chinese' ? "PDO SQLite 扩展已启用。\n" : "PDO SQLite extension is loaded.\n";
    } else {
        echo __LANGUAGE__==='chinese' ? "PDO SQLite 扩展未启用。\n" : "PDO SQLite extension is not loaded.\n";
        exit(__LANGUAGE__==='chinese' ? "[异常退出]因为使用了SQLite作为数据库，但PHP却未启用pdo_sqlite拓展\n" : "[Exception Exit] Because SQLite was used as the database, but PHP did not enable the pdo_sqlite extension\n");
    }
}
if (__DATABASE_NAME__ === 'mysql') {
    if (extension_loaded('mysqli')) { // mysqli check
        echo __LANGUAGE__==='chinese' ? "MySQLi 扩展已启用。\n" : "MySQLi extension is loaded.\n";
    } else {
        echo __LANGUAGE__==='chinese' ? "MySQLi 扩展未启用。\n" : "MySQLi extension is not loaded.\n";
        exit(__LANGUAGE__==='chinese' ? "[异常退出]因为使用了Mysql作为数据库，但PHP却未启用mysqli拓展\n" : "[Exception Exit] Because MySQL was used as the database, but PHP did not enable the mysqli extension\n");
    }
}
echo "\n\n";////////////////////////////////////////////////////////////////////////////////////////
if (__DATABASE_NAME__ === 'mysql') {
    echo __LANGUAGE__==='chinese' ? "③ 检查你的mysql账号...\n" : "③ Confirm your mysql account...\n";
    echo __LANGUAGE__==='chinese' ? "--Mysql 密码配置--\n" : "--Mysql password config--\n";
    echo __LANGUAGE__==='chinese' ? "根密码:\n" : "root password :\n";
    echo $mysql_root_password."\n";
    echo __LANGUAGE__==='chinese' ? "公用账户名称 :\n" : "public name :\n";
    echo $mysql_public_user."\n";
    echo __LANGUAGE__==='chinese' ? "公用账户密码 :\n" : "public password :\n";
    echo $mysql_public_password."\n";
} else {
    echo __LANGUAGE__==='chinese' ? "③ 检查你的mysql账号...(SQLite 跳过)\n" : "③ Confirm your mysql account...(SQLite skip)\n";
}
echo "\n\n";////////////////////////////////////////////////////////////////////////////////////////
if (__DATABASE_NAME__ !== 'sqlite') {
    echo __LANGUAGE__==='chinese' ? "④ 检查mysql根账户中...\n" : "④ Check mysql root account...\n";
    if (checkRootAccount() === false) {
        exit(__LANGUAGE__==='chinese' ? "[异常退出]因为mysql的root帐户密码不正确！\n" : "[Abnormal exit] Because the root account password for MySQL is incorrect!\n");
    } else {
        echo __LANGUAGE__==='chinese' ? "mysql的根账户正确无误。\n" : "The mysql root account is correct.\n";
    }
} else {
    echo __LANGUAGE__==='chinese' ? "④ 检查mysql根账户中...(SQLite 跳过)\n" : "④ Check mysql root account...(SQLite skip)\n";
}
echo "\n\n";////////////////////////////////////////////////////////////////////////////////////////
if (__DATABASE_NAME__ !== 'sqlite') {
    echo __LANGUAGE__==='chinese' ? "⑤ 检查mysql公用账户中...\n" : "⑤ Check mysql public account...\n";
    if (checkPublicAccount() === false) {
        exit(__LANGUAGE__==='chinese' ? "[异常退出]因为mysql的公用账户密码不正确！\n" : "[Abnormal Exit] Because the password for MySQL's public account is incorrect!\n");
    } else {
        echo __LANGUAGE__==='chinese' ? "mysql的公用账户正确无误。\n" : "The mysql public account is correct.\n";
    }
} else {
    echo __LANGUAGE__==='chinese' ? "⑤ 检查mysql公用账户中...(SQLite 跳过)\n" : "⑤ Check mysql public account...(SQLite skip)\n";
}
echo "\n\n";////////////////////////////////////////////////////////////////////////////////////////
echo __LANGUAGE__==='chinese' ? "⑥ 检查地图数据库中...\n" : "⑥ Check if the map database has been created...\n";
if (__DATABASE_NAME__ === 'mysql') {
    if (checkMapDatabase() === false) {
        echo __LANGUAGE__==='chinese' ? "目前还未创建地图数据库。\n" : "The database has not been created yet.\n";
        if (createMapDatabase() === false) {
            exit(__LANGUAGE__==='chinese' ? "[异常退出]因为无法创建数据库！\n" : "[Exception exit] Because unable to create database!\n");
        } else {
            echo __LANGUAGE__==='chinese' ? "地图数据库初始化成功！\n" : "Database initialization successful!\n";
        }
    } else {
        echo __LANGUAGE__==='chinese' ? "地图数据库已经存在。\n" : "The database has been created.\n";
    }
} else {
    if (checkSqliteDatabase() === false) {
        echo __LANGUAGE__==='chinese' ? "目前还未创建地图数据库。\n" : "The database has not been created yet!\n";
        if (createSqliteDatabase() === false) {
            exit(__LANGUAGE__==='chinese' ? "[异常退出]因为无法创建数据库！\n" : "[Exception exit] Because unable to create database!\n");
        } else {
            echo __LANGUAGE__==='chinese' ? "地图数据库初始化成功！\n" : "Database initialization successful!\n";
        }
    } else {
        echo __LANGUAGE__==='chinese' ? "地图数据库已经存在。\n" : "The database has been created.\n";
    }
}
echo "\n\n";////////////////////////////////////////////////////////////////////////////////////////
if (__DATABASE_NAME__ === 'mysql') {
    echo __LANGUAGE__==='chinese' ? "⑦ 设置公用账户的权限...\n" : "⑦ Set public account permissions...\n";
    if (grantPublicAuthority() === false) {
        exit(__LANGUAGE__==='chinese' ? "[异常退出]因为无法授予公共帐户权限！\n" : "[Abnormal Exit] Due to inability to grant public account permissions!\n");
    } else {
        echo __LANGUAGE__==='chinese' ? "已成功为公用账户授予权限\n" : "Successfully granted public account permissions.\n";
    }
} else {
    echo __LANGUAGE__==='chinese' ? "⑦ 设置公用账户的权限...(SQLite 跳过)\n" : "⑦ Set public account permissions...(SQLite skip)\n";
}
echo "\n\n";////////////////////////////////////////////////////////////////////////////////////////
echo "...All Done\n";
echo "\n\n";
################Script initial execution################
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
<external-program>
 **/
use Workerman\Worker;
use Workerman\Timer;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Protocols\Http\Response;
require 'Workerman/Autoloader.php';

require 'api/Public_getPublickey.php';//获取公钥与私钥的API
require 'api/Other_RsaTranslate.php';//解密和加密功能
require 'class/QualityInspectionRoom.php';//通用数据检测工具
require 'class/JsonDisposalTool.php';//json工具
require 'class/MapDataEdit.php';//地图数据库编辑工具
require 'class/FileOperation.php';//文件处理类
require 'class/instruct.php';//指令集合
require 'class/LayerDataEdit.php';//指令集合
/**
</external-program>
 **/

/**
<class> 顺序不能乱
 **/
$newQIR=new QualityInspectionRoom(false);
$newJDT=new JsonDisposalTool();
$newFO=new FileOperation();
$instruct=new instruct(true,true);
$newMDE=new MapDataEdit($mysql_public_sheet_name,$mysql_public_layer_name);
$newLDE=new LayerDataEdit(__LANGUAGE__);
/**
</class>
 **/

/**
<data>
 **/
const HEARTBEAT_TIME=120;
$theData=[
    'globalUid'=>0,
    'ip_connect_times'=>[
        /*示例
         * "192.168.1.2"=>5
         **/
    ],
    'activeElement'=>[
        /*示例
         * "E57"=>[
         *                      "pick"=>[
         *                                      "email"=>"name@any.com",
         *                                      "color"=>"1122dd",
         *                                      "name"=>"user name"
         *                      ],
         *                      "select"=>[
         *                                      "email"=>"name@any.com",
         *                                      "color"=>"1122dd",
         *                                      "name"=>"user name"
         *                      ]
         *                ]
         **/
    ],
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
        'ping','pong',
        'broadcast','get_serverImg','get_serverConfig','get_publickey','login','publickey',
        'loginStatus','get_userData','send_userData','get_mapData','send_mapData','get_presence',
        'send_presence','get_activeData','send_activeData','send_error','send_correct','get_mapLayer',
        'send_mapLayer'
    ],
    'automateTime'=>60,
];
if(!file_exists('log')){
    mkdir('log', 0777, true);
    echo "\n文件夹 'log' 创建成功\n";
}
if(!file_exists('cache')){
    mkdir('cache', 0777, true);
    echo "\n文件夹 'cache' 创建成功\n";
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
    global $theConfig,$newLDE;
    if(__ANONYMOUS_LOGIN__===true)$theConfig['typeList'][]='anonymousLogin';//允许匿名登录指令
    $newLDE->buildLayerData();//构建图层数据
    $newLDE->buildTemplateLink();//构建模板与图层关系链接
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
 * @return bool
 */
function handle_message($connection,$data){//收到客户端消息
    global $newLDE,$theData,$theConfig,$socket_worker,$newQIR,$newJDT,$newMDE,$newFO,$instruct,$Version;
    $jsonData=$newJDT->checkJsonData($data);//1.校验并解析json格式
    $activated=false;
    if(property_exists($connection,'email')){$activated=true;}
    if(gettype($jsonData)==='array'){//2.检测是否为数组类型
    if(array_key_exists('type',$jsonData)){//3.检测是否存在必要属性 'type'
    if(in_array($jsonData['type'],$theConfig['typeList'])){//4.检测type类型是否合规
    $nowType=$jsonData['type'];//5.处理数据
        switch ($nowType){
            case 'ping':{
                $connection->send(json_encode(['type'=>'pong'],JSON_UNESCAPED_UNICODE));
                break;
            }
            case 'broadcast':{//广播数据
                if($activated){//必须是非匿名会话才能使用
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
                                $sendJson=json_encode($sdJson,JSON_UNESCAPED_UNICODE);//返回数据
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
                                $tmpId='unknown';
                                $basicStructure=['id'=>'temp','type'=>'area','points'=>[],'point'=>null,'color'=>'','phase'=>1,'width'=>2,'child_relations'=>[],'father_relation'=>'','child_nodes'=>[],'father_node'=>'','details'=>null,'custom'=>null];//0.构建默认数据内容
                                $mysqlStructure=['id'=>'temp','type'=>'area','points'=>[],'point'=>null,'color'=>'','phase'=>1,'width'=>2,'child_relations'=>[],'father_relation'=>'','child_nodes'=>[],'father_node'=>'','details'=>null,'custom'=>null];//数据库内元素结构
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
                                        $mysqlStructure['color']=$jsonData['data']['color'];
                                    }else{
                                        $basicStructure['color']='ffffff';
                                        $mysqlStructure['color']='ffffff';
                                    }
                                }
                                else{
                                    $basicStructure['color']='ffffff';
                                    $mysqlStructure['color']='ffffff';
                                }
                                if($newQIR->arrayPropertiesCheck('width',$jsonData['data'])){//8.检查是否存在width，并检查是否为数字
                                    if($newQIR->digitalCheck($jsonData['data']['width'])){//7.1检查是否是数字，不是数字则改写为默认值
                                        $basicStructure['width']=$jsonData['data']['width'];
                                        $mysqlStructure['width']=$jsonData['data']['width'];
                                    }else{
                                        $basicStructure['width']=2;
                                        $mysqlStructure['width']=2;
                                    }
                                }
                                else{
                                    $basicStructure['width']=2;
                                    $mysqlStructure['width']=2;
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
                                        $mysqlStructure['details']='';
                                    }
                                }
                                else{
                                    $basicStructure['details']='';
                                    $mysqlStructure['details']='';
                                }
                                if($newQIR->arrayPropertiesCheck('custom',$jsonData['data'])){//custom的模板id提取
                                    if(gettype($jsonData['data']['custom']==='array')){
                                        if($newQIR->arrayPropertiesCheck('tmpId',$jsonData['data']['custom'])){
                                            if(gettype($jsonData['data']['custom']['tmpId']==='string')){
                                                $tmpId=$jsonData['data']['custom']['tmpId'];
                                            }
                                        }
                                    }
                                }
                                /*
                                  *封装
                                  */
                                $basicStructure['point']=$jsonData['data']['point'];
                                $basicStructure['points']=$jsonData['data']['points'];
                                $basicStructure['details']=$jsonData['data']['details'];
                                $basicStructure['custom']=$jsonData['data']['custom'];
                                $mysqlStructure['point']=$newJDT->jsonPack($jsonData['data']['point']);
                                $mysqlStructure['points']=$newJDT->jsonPack($jsonData['data']['points']);
                                $mysqlStructure['details']=$newJDT->jsonPack($jsonData['data']['details']);
                                $mysqlStructure['custom']=$newJDT->jsonPack($jsonData['data']['custom']);
                                /*全部检查完毕
                                  *上传至数据库
                                  */
                                $uploadId=$newMDE->uploadElementData($mysqlStructure,'area');
                                if($uploadId!==-1){//写入日志文件和广播给其他用户
                                    $newId=$uploadId;
                                    $basicStructure['id']=$newId;//更改basic id
                                    $broadcastEmail=$connection->email;//发送广播的email
                                    $dateTime=creatDate();
                                    $appendLayerId=$newLDE->appendElement($newId,$tmpId,'area');
                                    $newLayerData=null;//['id'=>,'members'=>,'structure'=>]
                                    if($appendLayerId!==-1){
                                        $LayerData=$newLDE->getLayerMembersStructure($appendLayerId,false);
                                        if($LayerData!==false){
                                            $newLayerData=[
                                                'id'=>(int)$appendLayerId,
                                                'members'=>$LayerData['members'],
                                                'structure'=>$LayerData['structure']
                                            ];
                                        }
                                    }
                                    $sdJson1=['type'=>'broadcast','class'=>'area','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$basicStructure];//组合
                                    $sendJson1=json_encode($sdJson1,JSON_UNESCAPED_UNICODE);//返回数据
                                    $sdJson2=['type'=>'broadcast','class'=>'updateLayerData','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$newLayerData];//组合
                                    $sendJson2=json_encode($sdJson2,JSON_UNESCAPED_UNICODE);//返回数据
                                    foreach ($socket_worker->connections as $con) {
                                        if(property_exists($con,'email')){//避免发送给匿名用户
                                            if($con->email != ''){
                                                $con->send($sendJson1);//指令的发送顺序不能乱
                                                if($newLayerData!==null){
                                                    $con->send($sendJson2);
                                                }
                                            }
                                        }
                                    }
                                    sendCorrect('upload',['vid'=>$jsonData['data']['id'],'rid'=>$newId],$connection);//返回成功指令顺序在 point 后
                                    $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$broadcastEmail,'addId'=>$newId];
                                    createLog('userAddArea',$logData);
                                }else{
                                    sendError('upload',$jsonData['data'],'database upload fail',$connection);
                                }
                                break;
                            }
                            case 'line':{//新增线段
                                $tmpId='unknown';
                                $basicStructure=['id'=>'temp','type'=>'line','points'=>[],'point'=>null,'color'=>'','phase'=>1,'width'=>2,'child_relations'=>[],'father_relation'=>'','child_nodes'=>[],'father_node'=>'','details'=>null,'custom'=>null];//0.构建默认数据内容
                                $mysqlStructure=['id'=>'temp','type'=>'line','points'=>[],'point'=>null,'color'=>'','phase'=>1,'width'=>2,'child_relations'=>[],'father_relation'=>'','child_nodes'=>[],'father_node'=>'','details'=>null,'custom'=>null];//数据库内元素结构
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
                                        $mysqlStructure['color']=$jsonData['data']['color'];
                                    }else{
                                        $basicStructure['color']='ffffff';
                                        $mysqlStructure['color']='ffffff';
                                    }
                                }
                                else{
                                    $basicStructure['color']='ffffff';
                                    $mysqlStructure['color']='ffffff';
                                }
                                if($newQIR->arrayPropertiesCheck('width',$jsonData['data'])){//8.检查是否存在width，并检查是否为数字
                                    if($newQIR->digitalCheck($jsonData['data']['width'])){//8.1检查是否是数字，不是数字则改写为默认值
                                        $basicStructure['width']=$jsonData['data']['width'];
                                        $mysqlStructure['width']=$jsonData['data']['width'];
                                    }else{
                                        $basicStructure['width']=2;
                                        $mysqlStructure['width']=2;
                                    }
                                }
                                else{
                                    $basicStructure['width']=2;
                                    $mysqlStructure['width']=2;
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
                                        $mysqlStructure['details']='';
                                    }
                                }
                                else{
                                    $basicStructure['details']='';
                                    $mysqlStructure['details']='';
                                }
                                if($newQIR->arrayPropertiesCheck('custom',$jsonData['data'])){//custom的模板id提取
                                    if(gettype($jsonData['data']['custom']==='array')){
                                        if($newQIR->arrayPropertiesCheck('tmpId',$jsonData['data']['custom'])){
                                            if(gettype($jsonData['data']['custom']['tmpId']==='string')){
                                                $tmpId=$jsonData['data']['custom']['tmpId'];
                                            }
                                        }
                                    }
                                }
                                /*
                                  *封装
                                  */
                                $basicStructure['point']=$jsonData['data']['point'];
                                $basicStructure['points']=$jsonData['data']['points'];
                                $basicStructure['details']=$jsonData['data']['details'];
                                $basicStructure['custom']=$jsonData['data']['custom'];
                                $mysqlStructure['point']=$newJDT->jsonPack($jsonData['data']['point']);
                                $mysqlStructure['points']=$newJDT->jsonPack($jsonData['data']['points']);
                                $mysqlStructure['details']=$newJDT->jsonPack($jsonData['data']['details']);
                                $mysqlStructure['custom']=$newJDT->jsonPack($jsonData['data']['custom']);
                                /*全部检查完毕
                                  *上传至数据库
                                  */
                                $uploadId=$newMDE->uploadElementData($mysqlStructure,'line');
                                if($uploadId!==-1){//更新成功后写入日志文件并广播给其他用户
                                    $newId=$uploadId;
                                    $basicStructure['id']=$newId;//更改basic id
                                    $broadcastEmail=$connection->email;//发送广播的email
                                    $dateTime=creatDate();//时间
                                    $appendLayerId=$newLDE->appendElement($newId,$tmpId,'line');
                                    $newLayerData=null;//['id'=>,'members'=>,'structure'=>]
                                    if($appendLayerId!==-1){
                                        $LayerData=$newLDE->getLayerMembersStructure($appendLayerId,false);
                                        if($LayerData!==false){
                                            $newLayerData=[
                                                'id'=>(int)$appendLayerId,
                                                'members'=>$LayerData['members'],
                                                'structure'=>$LayerData['structure']
                                            ];
                                        }
                                    }
                                    $sdJson1=['type'=>'broadcast','class'=>'line','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$basicStructure];
                                    $sendJson1=json_encode($sdJson1,JSON_UNESCAPED_UNICODE);//返回数据
                                    $sdJson2=['type'=>'broadcast','class'=>'updateLayerData','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$newLayerData];//组合
                                    $sendJson2=json_encode($sdJson2,JSON_UNESCAPED_UNICODE);//返回数据
                                    foreach ($socket_worker->connections as $con){
                                        if(property_exists($con,'email')){//避免发送给匿名用户
                                            if($con->email != ''){
                                                $con->send($sendJson1);//指令的发送顺序不能乱
                                                if($newLayerData!==null){
                                                    $con->send($sendJson2);
                                                }
                                            }
                                        }
                                    }
                                    sendCorrect('upload',['vid'=>$jsonData['data']['id'],'rid'=>$newId],$connection);//返回成功指令顺序在 point 后
                                    $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$broadcastEmail,'addId'=>$newId];
                                    createLog('userAddLine',$logData);
                                }else{
                                    sendError('upload',$jsonData['data'],'database upload fail',$connection);
                                }
                                break;
                            }
                            case 'curve':{//新增曲线
                                $tmpId='unknown';
                                $basicStructure=['id'=>'temp','type'=>'curve','points'=>[],'point'=>null,'color'=>'','phase'=>1,'width'=>2,'child_relations'=>[],'father_relation'=>'','child_nodes'=>[],'father_node'=>'','details'=>null,'custom'=>null];//0.构建默认数据内容
                                $mysqlStructure=['id'=>'temp','type'=>'curve','points'=>[],'point'=>null,'color'=>'','phase'=>1,'width'=>2,'child_relations'=>[],'father_relation'=>'','child_nodes'=>[],'father_node'=>'','details'=>null,'custom'=>null];//数据库内元素结构
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
                                        $mysqlStructure['color']=$jsonData['data']['color'];
                                    }else{
                                        $basicStructure['color']='ffffff';
                                        $mysqlStructure['color']='ffffff';
                                    }
                                }
                                else{
                                    $basicStructure['color']='ffffff';
                                    $mysqlStructure['color']='ffffff';
                                }
                                if($newQIR->arrayPropertiesCheck('width',$jsonData['data'])){//8.检查是否存在width，并检查是否为数字
                                    if($newQIR->digitalCheck($jsonData['data']['width'])){//8.1检查是否是数字，不是数字则改写为默认值
                                        $basicStructure['width']=$jsonData['data']['width'];
                                        $mysqlStructure['width']=$jsonData['data']['width'];
                                    }else{
                                        $basicStructure['width']=2;
                                        $mysqlStructure['width']=2;
                                    }
                                }
                                else{
                                    $basicStructure['width']=2;
                                    $mysqlStructure['width']=2;
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
                                        $mysqlStructure['details']='';
                                    }
                                }
                                else{
                                    $basicStructure['details']='';
                                    $mysqlStructure['details']='';
                                }
                                if($newQIR->arrayPropertiesCheck('custom',$jsonData['data'])){//custom的模板id提取
                                    if(gettype($jsonData['data']['custom']==='array')){
                                        if($newQIR->arrayPropertiesCheck('tmpId',$jsonData['data']['custom'])){
                                            if(gettype($jsonData['data']['custom']['tmpId']==='string')){
                                                $tmpId=$jsonData['data']['custom']['tmpId'];
                                            }
                                        }
                                    }
                                }
                                /*
                                  *封装
                                  */
                                $basicStructure['point']=$jsonData['data']['point'];
                                $basicStructure['points']=$jsonData['data']['points'];
                                $basicStructure['details']=$jsonData['data']['details'];
                                $basicStructure['custom']=$jsonData['data']['custom'];
                                $mysqlStructure['point']=$newJDT->jsonPack($jsonData['data']['point']);
                                $mysqlStructure['points']=$newJDT->jsonPack($jsonData['data']['points']);
                                $mysqlStructure['details']=$newJDT->jsonPack($jsonData['data']['details']);
                                $mysqlStructure['custom']=$newJDT->jsonPack($jsonData['data']['custom']);
                                /*全部检查完毕
                                  *上传至数据库
                                  */
                                $uploadId=$newMDE->uploadElementData($mysqlStructure,'curve');
                                if($uploadId!==-1){//更新成功后写入日志文件并广播给其他用户
                                    $newId=$uploadId;
                                    $basicStructure['id']=$newId;//更改basic id
                                    $broadcastEmail=$connection->email;//发送广播的email
                                    $dateTime=creatDate();//时间
                                    $appendLayerId=$newLDE->appendElement($newId,$tmpId,'curve');
                                    $newLayerData=null;//['id'=>,'members'=>,'structure'=>]
                                    if($appendLayerId!==-1){
                                        $LayerData=$newLDE->getLayerMembersStructure($appendLayerId,false);
                                        if($LayerData!==false){
                                            $newLayerData=[
                                                'id'=>(int)$appendLayerId,
                                                'members'=>$LayerData['members'],
                                                'structure'=>$LayerData['structure']
                                            ];
                                        }
                                    }
                                    $sdJson1=['type'=>'broadcast','class'=>'curve','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$basicStructure];
                                    $sendJson1=json_encode($sdJson1,JSON_UNESCAPED_UNICODE);//返回数据
                                    $sdJson2=['type'=>'broadcast','class'=>'updateLayerData','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$newLayerData];//组合
                                    $sendJson2=json_encode($sdJson2,JSON_UNESCAPED_UNICODE);//返回数据
                                    foreach ($socket_worker->connections as $con){
                                        if(property_exists($con,'email')){//避免发送给匿名用户
                                            if($con->email != ''){
                                                $con->send($sendJson1);//指令的发送顺序不能乱
                                                if($newLayerData!==null){
                                                    $con->send($sendJson2);
                                                }
                                            }
                                        }
                                    }
                                    sendCorrect('upload',['vid'=>$jsonData['data']['id'],'rid'=>$newId],$connection);//返回成功指令顺序在 point 后
                                    $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$broadcastEmail,'addId'=>$newId];
                                    createLog('userAddCurve',$logData);
                                }else{
                                    sendError('upload',$jsonData['data'],'database upload fail',$connection);
                                }
                                break;
                            }
                            case 'point':{//广播新增点
                                $tmpId='unknown';
                                $basicStructure=['id'=>'temp','type'=>'point','points'=>[],'point'=>null,'color'=>'','phase'=>1,'width'=>2,'child_relations'=>[],'father_relation'=>'','child_nodes'=>[],'father_node'=>'','details'=>[],'custom'=>null];//0.构建默认数据内容
                                $mysqlStructure=['id'=>'temp','type'=>'point','points'=>[],'point'=>null,'color'=>'','phase'=>1,'width'=>2,'child_relations'=>[],'father_relation'=>'','child_nodes'=>[],'father_node'=>'','details'=>[],'custom'=>null];//数据库内元素结构
                                if(!$newQIR->arrayPropertiesCheck('data',$jsonData)){break;}//1.检查是否包含data键名
                                if(!$newQIR->getDataType($jsonData['data'])=='array'){break;}//2.检查data是否为数组
                                if(!$newQIR->arrayPropertiesCheck('point',$jsonData['data'])){break;}//3.检查是否包含point属性
                                if(!$newQIR->arrayPropertiesCheck('x',$jsonData['data']['point']) OR !$newQIR->arrayPropertiesCheck('y',$jsonData['data']['point'])){break;}//4.检查point是否包含xy属性                                        //$pwd jsonData['data']['point']['x']/['y']
                                if(!$newQIR->digitalCheck($jsonData['data']['point']['x']) OR !$newQIR->digitalCheck($jsonData['data']['point']['y'])){break;}//5.检查xy值是否为数字
                                if($newQIR->arrayPropertiesCheck('color',$jsonData['data'])){//6.检查color是否存在，存在则检查，不存在则设置默认值
                                    if($newQIR->color16Check($jsonData['data']['color'])){//6.1检查颜色格式是否正确
                                        $basicStructure['color']=$jsonData['data']['color'];
                                        $mysqlStructure['color']=$jsonData['data']['color'];
                                    }else{
                                        $basicStructure['color']='ffffff';
                                        $mysqlStructure['color']='ffffff';
                                    }
                                }
                                else{
                                    $basicStructure['color']='ffffff';
                                    $mysqlStructure['color']='ffffff';
                                }
                                if($newQIR->arrayPropertiesCheck('width',$jsonData['data'])){//7.检查是否存在width，并检查是否为数字
                                    if($newQIR->digitalCheck($jsonData['data']['width'])){//7.1检查是否是数字，不是数字则改写为默认值
                                        $basicStructure['width']=$jsonData['data']['width'];
                                        $mysqlStructure['width']=$jsonData['data']['width'];
                                    }else{
                                        $basicStructure['width']=2;
                                        $mysqlStructure['width']=2;
                                    }
                                }
                                else{
                                    $basicStructure['width']=2;
                                    $mysqlStructure['width']=2;
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
                                        $mysqlStructure['details']='';
                                    }
                                }
                                else{
                                    $basicStructure['details']='';
                                    $mysqlStructure['details']='';
                                }
                                if($newQIR->arrayPropertiesCheck('custom',$jsonData['data'])){//custom的模板id提取
                                    if(gettype($jsonData['data']['custom']==='array')){
                                        if($newQIR->arrayPropertiesCheck('tmpId',$jsonData['data']['custom'])){
                                            if(gettype($jsonData['data']['custom']['tmpId']==='string')){
                                                $tmpId=$jsonData['data']['custom']['tmpId'];
                                            }
                                        }
                                    }
                                }
                                /*
                                  *封装
                                  */
                                $basicStructure['point']=$jsonData['data']['point'];
                                $basicStructure['points']=[$jsonData['data']['point']];
                                $basicStructure['details']=$jsonData['data']['details'];
                                $basicStructure['custom']=$jsonData['data']['custom'];
                                $mysqlStructure['point']=$newJDT->jsonPack($jsonData['data']['point']);
                                $mysqlStructure['points']=$newJDT->jsonPack([$jsonData['data']['point']]);
                                $mysqlStructure['details']=$newJDT->jsonPack($jsonData['data']['details']);
                                $mysqlStructure['custom']=$newJDT->jsonPack($jsonData['data']['custom']);
                                /*全部检查完毕
                                  *上传至数据库
                                  */
                                $uploadId=$newMDE->uploadElementData($mysqlStructure,'point');
                                if($uploadId!==-1){//更新成功后写入日志文件并广播给其他用户
                                    $newId=$uploadId;
                                    $basicStructure['id']=$newId;//更改basic id
                                    $broadcastEmail=$connection->email;//发送广播的email
                                    $dateTime=creatDate();
                                    $appendLayerId=$newLDE->appendElement($newId,$tmpId,'point');
                                    $newLayerData=null;//['id'=>,'members'=>,'structure'=>]
                                    if($appendLayerId!==-1){
                                        $LayerData=$newLDE->getLayerMembersStructure($appendLayerId,false);
                                        if($LayerData!==false){
                                            $newLayerData=[
                                                'id'=>(int)$appendLayerId,
                                                'members'=>$LayerData['members'],
                                                'structure'=>$LayerData['structure']
                                            ];
                                        }
                                    }
                                    $sdJson1=['type'=>'broadcast','class'=>'point','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$basicStructure];//组合
                                    $sendJson1=json_encode($sdJson1,JSON_UNESCAPED_UNICODE);//返回数据
                                    $sdJson2=['type'=>'broadcast','class'=>'updateLayerData','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$newLayerData];//组合
                                    $sendJson2=json_encode($sdJson2,JSON_UNESCAPED_UNICODE);//返回数据
                                    foreach ($socket_worker->connections as $con){
                                        if(property_exists($con,'email')){//避免发送给匿名用户
                                            if($con->email != ''){
                                                $con->send($sendJson1);//指令的发送顺序不能乱
                                                if($newLayerData!==null){
                                                    $con->send($sendJson2);
                                                }
                                            }
                                        }
                                    }
                                    sendCorrect('upload',['vid'=>$jsonData['data']['id'],'rid'=>$newId],$connection);//返回成功指令顺序在 point 后
                                    $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$broadcastEmail,'addId'=>$newId];
                                    createLog('userAddPoint',$logData);
                                }
                                else{
                                    sendError('upload',$jsonData['data'],'database upload fail',$connection);
                                }
                                break;
                            }
                            case 'deleteElement':{//删除元素
                                try{
                                    $conveyor=$connection->email;
                                    $ID=(int)$jsonData['data']['id'];
                                    $Time=creatDate();
                                    $tmpId=null;
                                    $newLayerData=null;
                                    $sendJson0=null;
                                    if(array_key_exists('tmpId',$jsonData['data'])){$tmpId=$jsonData['data']['tmpId'];}
                                    $updateStatus=$newMDE->updateElementPhase($ID,2);//更改数据库
                                    if($updateStatus===true){//更改成功则广播所有人
                                        /*
                                         *pick和select操作移除Start
                                         */
                                        if(array_key_exists('E'.$ID,$theData['activeElement'])){//存在选中
                                            unset($theData['activeElement']['E'.$ID]);//移除所有选中
                                            $sendArray=[//返回数据
                                                'type'=>'broadcast','class'=>'pickSelectEndElements',
                                                'conveyor'=>'','time'=>$Time,
                                                'data'=>[
                                                    'id'=>[$ID]
                                                ]
                                            ];
                                            $sendJson0=json_encode($sendArray,JSON_UNESCAPED_UNICODE);
                                        }
                                        /*
                                         *pick和select操作移除End
                                         */
                                        sendCorrect('delete',['rid'=>$ID],$connection);//单独发送成功指令
                                        /*
                                         *图层移除元素操作Start
                                         */
                                        if($tmpId!==null){
                                            $changeLayerId=$newLDE->removeElement($ID,$tmpId);
                                            if($changeLayerId!==-1){//图层变动
                                                $LayerData=$newLDE->getLayerMembersStructure($changeLayerId,false);
                                                if($LayerData!==false){
                                                    $newLayerData=[
                                                        'id'=>(int)$changeLayerId,
                                                        'members'=>$LayerData['members'],
                                                        'structure'=>$LayerData['structure']
                                                    ];
                                                }
                                            }
                                        }
                                        /*
                                         *图层移除元素操作End
                                         */
                                        $sdJson1=['type'=>'broadcast','class'=>'deleteElement','conveyor'=>$conveyor,'time'=>$Time,'data'=>['id'=>$ID]];//组合
                                        $sendJson1=json_encode($sdJson1,JSON_UNESCAPED_UNICODE);//返回数据
                                        $sdJson2=['type'=>'broadcast','class'=>'updateLayerData','conveyor'=>$conveyor,'time'=>$Time,'data'=>$newLayerData];//组合
                                        $sendJson2=json_encode($sdJson2,JSON_UNESCAPED_UNICODE);//返回数据
                                        /*
                                         *返回数据
                                         */
                                        foreach ($socket_worker->connections as $con){
                                            if(property_exists($con,'email')){//避免发送给匿名用户
                                                if($con->email != ''){
                                                    if($sendJson0!==null){
                                                    $con->send($sendJson0);
                                                    }
                                                    $con->send($sendJson1);
                                                    if($newLayerData!==null){
                                                    $con->send($sendJson2);
                                                    }
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
                                $sendJson = json_encode($sendArr,JSON_UNESCAPED_UNICODE);
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
                                $mysqlStructure=[];//元素数据库结构
                                if(!$newQIR->arrayPropertiesCheck('data',$jsonData)){break;}//1.检查是否包含data键名
                                if(!$newQIR->getDataType($jsonData['data'])=='array'){break;}//2.检查data是否为数组
                                if(!$newQIR->arrayPropertiesCheck('id',$jsonData['data'])){break;}//2.5检查id是否存在
                                if($newQIR->digitalCheck($jsonData['data']['id'])){//2.6检查id是否为数字，是则加入，否则退出case
                                    $basicStructure['id']=$jsonData['data']['id'];
                                    $mysqlStructure['id']=$jsonData['data']['id'];
                                }else{
                                    echo '所更新的id包含非数字字符'."\n";
                                    break;
                                }
                                if(!$newQIR->arrayPropertiesCheck('type',$jsonData['data'])){break;}//2.7检查type是否存在
                                if($newQIR->elementTypeCheck($jsonData['data']['type'])){//2.7.检查type是否正确
                                    $basicStructure['type']=$jsonData['data']['type'];
                                    $mysqlStructure['type']=$jsonData['data']['type'];
                                }else{
                                    echo '所更新的id元素类型不符标准'."\n";
                                    break;
                                }
                                if(!$newQIR->arrayPropertiesCheck('changes',$jsonData['data'])){break;}//3.检查changes是否存在
                                if(!$newQIR->getDataType($jsonData['data']['changes'])=='array'){break;}//4.检查changes是否为数组
                                if($newQIR->arrayPropertiesCheck('color',$jsonData['data']['changes'])){//5.检查color是否存在，存在则检查
                                    if($newQIR->color16Check($jsonData['data']['changes']['color'])){//5.1检查颜色格式是否正确
                                        $basicStructure['color']=$jsonData['data']['changes']['color'];
                                        $mysqlStructure['color']=$jsonData['data']['changes']['color'];
                                    }
                                }
                                if($newQIR->arrayPropertiesCheck('width',$jsonData['data']['changes'])){//6.检查是否存在width，并检查是否为数字
                                    if($newQIR->digitalCheck($jsonData['data']['changes']['width'])){//6.1检查是否是数字，存在则检查
                                        $basicStructure['width']=$jsonData['data']['changes']['width'];
                                        $mysqlStructure['width']=$jsonData['data']['changes']['width'];
                                    }
                                }
                                if($newQIR->arrayPropertiesCheck('details',$jsonData['data']['changes'])){//7.1检查details是否存在
                                    if($newQIR->getDataType($jsonData['data']['changes']['details'])=='array'){//7.2检查details数据结构是否为数组
                                        foreach ($jsonData['data']['changes']['details'] as $value){
                                            if ($newQIR->arrayPropertiesCheck('key',$value) AND
                                                $newQIR->arrayPropertiesCheck('value',$value)){//7.3检查该项键值对是否存在
                                                if($newQIR->commonKeyNameCheck($value['key']) AND
                                                    $newQIR->illegalCharacterCheck($value['value'])){//8.4检查键名和键值是否正常，如果不正常则跳出当前循环case
                                                    $details[]=$value;
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
                                  *应用元素的模板规则
                                  */
                                if(isset($jsonData['data']['changes']['details'])){//在更新元素属性时应用模板规则
                                    $elementId=$jsonData['data']['id'];
                                    $elementData=$newMDE->getElementById($elementId);
                                    if($elementData===false){//无法查询到该元素的数据
                                        echo '所更新的元素在数据库中不存在，此元素将不会产生任何变动id:'.$elementId."\n";
                                        break;
                                    }
                                    $elementData['custom']=json_decode($elementData['custom'],true);
                                    if($newQIR->arrayPropertiesCheck('tmpId',$elementData['custom'])===false){
                                        echo '所更新的元素未使用任何模板，此元素将不会产生任何变动id:'.$elementId."\n";
                                        break;
                                    }else{
                                        $templateId=$elementData['custom']['tmpId'];
                                        $template=$newLDE->getTemplateById($templateId);
                                        if($template===false){
                                            echo '所更新的元素引用的模板在地图中不存在，此元素将不会产生任何变动id:'.$elementId."\n";
                                            break;
                                        }else{
                                            $matchColor=$newLDE->ruleMatchByColor($template['colorRule'],$jsonData['data']['changes']['details']);
                                            $matchWidth=$newLDE->ruleMatchByWidth($template['widthRule'],$jsonData['data']['changes']['details']);
                                            if($matchColor!=='error'){
                                                $color=substr($matchColor,1);//移除#前缀
                                                $basicStructure['color']=$color;//更换颜色
                                                $mysqlStructure['color']=$color;
                                            }
                                            if($matchWidth!=='error'){
                                                $basicStructure['width']=$matchWidth;//更换宽度
                                                $mysqlStructure['width']=$matchWidth;
                                            }
                                        }
                                    }
                                }
                                /*模板应用完毕
                                 *打包$details custom
                                 */
                                if(count($details)!=0){
                                    $basicStructure['details']=$details;
                                    $mysqlStructure['details']=$newJDT->jsonPack($details);
                                }
                                if($custom!==null){
                                    $basicStructure['custom']=$custom;
                                    $mysqlStructure['custom']=$newJDT->jsonPack($custom);
                                }
                                $isSuccess=$newMDE->updateElementData($mysqlStructure);//上传数据库
                                if($isSuccess){//更新成功后广播
                                    $broadcastEmail=$connection->email;
                                    $dateTime=creatDate();
                                    $sdJson=['type'=>'broadcast','class'=>'updateElement','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$basicStructure];//组合
                                    $sendJson=json_encode($sdJson,JSON_UNESCAPED_UNICODE);//返回数据
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
                                $nodeObject=[];//广播至客户端用此数据
                                $nodeMorph=[];//上传至数据库用该变体数据
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
                                if($lock){//如果存在异常的节点数据则退出case不做操作
                                    break;
                                }else{
                                    $nodeObject['id']=$jsonData['data']['id'];
                                    $nodeMorph['id']=$jsonData['data']['id'];
                                    $nodeObject['points']=$jsonData['data']['points'];
                                    $nodeMorph['points']=$jsonData['data']['points'];
                                    if($newQIR->arrayPropertiesCheck('point',$jsonData['data'])){ //判断是否存在point
                                        if($newQIR->getDataType($jsonData['data']['point'])=='array'){//检查是否为数组
                                            if($newQIR->arrayPropertiesCheck('x',$jsonData['data']['point']) && $newQIR->arrayPropertiesCheck('y',$jsonData['data']['point'])){//检查是否存在xy
                                                if($newQIR->digitalCheck($jsonData['data']['point']['x']) && $newQIR->digitalCheck($jsonData['data']['point']['y'])){//检查是否为数字
                                                    $nodeObject['point']=[];
                                                    $nodeMorph['point']=[];
                                                    $nodeObject['point']['x']=$jsonData['data']['point']['x'];
                                                    $nodeMorph['point']['x']=$jsonData['data']['point']['x'];
                                                    $nodeObject['point']['y']=$jsonData['data']['point']['y'];
                                                    $nodeMorph['point']['y']=$jsonData['data']['point']['y'];
                                                    //$nodeObject['point']=$newJDT->jsonPack($nodeObject['point']);//不再使用json重复打包数据
                                                    $nodeMorph['point']=$newJDT->jsonPack($nodeObject['point']);//上传至数据库的需要使用json打包为json数据
                                                }
                                            }
                                        }
                                    }
                                }
                                //$nodeObject['points']=$newJDT->jsonPack($nodeObject['points']);//不再使用json重复打包数据
                                $nodeMorph['points']=$newJDT->jsonPack($nodeMorph['points']);//上传至数据库的需要使用json打包为json数据
                                //$nodeObject['points']=$newJDT->btoa($nodeObject['points']);//不再使用base64编码
                                $isSuccess=$newMDE->updateElementData($nodeMorph);//上传数据库
                                if($isSuccess){
                                    if($newQIR->arrayPropertiesCheck('type',$jsonData['data'])){
                                        $nodeObject['type']=$jsonData['data']['type'];
                                    }
                                    $broadcastEmail=$connection->email;//发送广播的email
                                    $dateTime=creatDate();
                                    $sdJson=['type'=>'broadcast','class'=>'updateElementNode','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$nodeObject];//组合
                                    $sendJson=json_encode($sdJson,JSON_UNESCAPED_UNICODE);//返回数据
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
                                $sendJson=json_encode($sendArr,JSON_UNESCAPED_UNICODE);
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
                                $sendJson=json_encode($sendArr,JSON_UNESCAPED_UNICODE);
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
                                $sendJson=json_encode($sendArr,JSON_UNESCAPED_UNICODE);
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
                                $sendJson=json_encode($sendArr,JSON_UNESCAPED_UNICODE);
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
                                    $ID=(int)$jsonData['data']['id'];
                                    $elementData=$newMDE->getElementById($ID);
                                    if($elementData!==false){//查询成功
                                        $broadcastEmail=$connection->email;
                                        $elementType=$elementData['type'];
                                        $Time=creatDate();
                                        $tmpId=null;
                                        $newLayerData=null;
                                        if(array_key_exists('tmpId',$jsonData['data'])){$tmpId=$jsonData['data']['tmpId'];}
                                        $updateStatus=$newMDE->updateElementPhase($ID,1);//更新元素周期
                                        $elementData['phase']=1;
                                        if($updateStatus===true){
                                            /*
                                              *图层重新增加元素操作Start
                                              */
                                            if($tmpId!==null){
                                                $changeLayerId=$newLDE->appendElement($ID,$tmpId,$elementType);
                                                if($changeLayerId!==-1){//图层变动
                                                    $LayerData=$newLDE->getLayerMembersStructure($changeLayerId,false);
                                                    if($LayerData!==false){
                                                        $newLayerData=[
                                                            'id'=>(int)$changeLayerId,
                                                            'members'=>$LayerData['members'],
                                                            'structure'=>$LayerData['structure']
                                                        ];
                                                    }
                                                }
                                            }
                                            /*
                                              *图层重新增加元素操作End
                                              */
                                            $elementData['id']=intval($elementData['id']);//转化为整数
                                            $elementData['width']=intval($elementData['width']);//转化为整数
                                            $elementData['point']=json_decode($elementData['point'],true);
                                            $elementData['points']=json_decode($elementData['points'],true);
                                            $elementData['details']=json_decode($elementData['details'],true);
                                            $elementData['custom']=json_decode($elementData['custom'],true);
                                            $sdJson1=['type'=>'broadcast','class'=>$elementData['type'],'conveyor'=>$broadcastEmail,'time'=>$Time,'data'=>$elementData];
                                            $sendJson1=json_encode($sdJson1,JSON_UNESCAPED_UNICODE);
                                            $sdJson2=['type'=>'broadcast','class'=>'updateLayerData','conveyor'=>$broadcastEmail,'time'=>$Time,'data'=>$newLayerData];//组合
                                            $sendJson2=json_encode($sdJson2,JSON_UNESCAPED_UNICODE);//返回数据
                                            $correctJson=['type'=>'send_correct','class'=>'upload','conveyor'=>$broadcastEmail,'time'=>$Time,'data'=>['rid'=>$ID,'vid'=>'restore']];
                                            $sendCorrectJson=json_encode($correctJson,JSON_UNESCAPED_UNICODE);
                                            foreach ($socket_worker->connections as $con) {
                                                if(property_exists($con,'email')){//避免发送给匿名用户
                                                    if($con->email != ''){
                                                        $con->send($sendJson1);
                                                        if($newLayerData!==null){
                                                            $con->send($sendJson2);
                                                        }
                                                    }
                                                }
                                            }
                                            $connection->send($sendCorrectJson);
                                        }
                                        $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$broadcastEmail,'id'=>$ID];//写入日志
                                        createLog('restoreElement',$logData);
                                    }
                                }catch (Exception $E){
                                    print_r('未知错误：restoreElement收到不明信息:');
                                    print_r($jsonData);
                                }
                                break;
                            }
                            case 'adjustElementOrder':{
                                if(!array_key_exists('data',$jsonData)){break;}
                                if(!is_array($jsonData['data'])){break;}
                                if(!array_key_exists('elementA',$jsonData['data'])){break;}
                                if(!array_key_exists('elementB',$jsonData['data'])){break;}
                                if(!array_key_exists('templateA',$jsonData['data'])){break;}
                                if(!array_key_exists('templateB',$jsonData['data'])){break;}
                                if(!array_key_exists('method',$jsonData['data'])){break;}
                                $elementA=$jsonData['data']['elementA'];
                                $elementB=$jsonData['data']['elementB'];
                                $templateA=$jsonData['data']['templateA'];
                                $templateB=$jsonData['data']['templateB'];
                                $method=$jsonData['data']['method'];
                                $affected=$newLDE->adjustElementOrder($elementA,$elementB,$templateA,$templateB,$method);
                                if(count($affected)===0){break;}//空数组表示出现异常了
                                if(count($affected['layers'])===0){break;}
                                elseif(count($affected['layers'])===1){//受到影响的只有一个图层
                                    $LayersData=[];
                                    foreach($affected['layers'] as $item){
                                        $layerData=$newLDE->getLayerMembersStructure($item,false);
                                        if($layerData!==false){
                                            unset($layerData['members']);//不需要members
                                            $layerData['id']=$item;//附加图层id
                                            $LayersData[]=$layerData;
                                        }
                                    }
                                    if(count($LayersData)!==0){
                                        $sendJson=$instruct->broadcast_batchUpdateLayerData($connection->email,$LayersData);
                                        foreach ($socket_worker->connections as $con) {
                                            if(property_exists($con,'email')){//避免发送给匿名用户
                                                if($con->email != ''){
                                                    $con->send($sendJson);
                                                }
                                            }
                                        }
                                    }
                                }
                                else{//多个图层受影响，还可能存在元素影响
                                    $LayersData=[];//被改动的图层们
                                    $ElementData=null;//被改动的元素数据
                                    foreach($affected['layers'] as $item){
                                        $layerData=$newLDE->getLayerMembersStructure($item,false);
                                        if($layerData!==false){
                                            $layerData['id']=$item;//附加图层id
                                            $LayersData[]=$layerData;
                                        }
                                    }
                                    if($affected['element']!==-1){
                                        $getData=$newMDE->getElementById($affected['element']);
                                        if($getData!==false){
                                            $ElementData['details']=json_decode($getData['details'],true);
                                            $ElementData['custom']=json_decode($getData['custom'],true);
                                            $ElementData['id']=(int)$getData['id'];
                                            $ElementData['type']=$getData['type'];
                                        }
                                    }
                                    if(count($LayersData)!==0){
                                        $broadcastEmail=$connection->email;
                                        $dateTime=creatDate();
                                        $sendJson1=$instruct->broadcast_batchUpdateLayerData($broadcastEmail,$LayersData);
                                        $sendArray2=['type'=>'broadcast','class'=>'updateElement','conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$ElementData];
                                        $sendJson2=json_encode($sendArray2,JSON_UNESCAPED_UNICODE);
                                        foreach ($socket_worker->connections as $con) {
                                            if(property_exists($con,'email')){//避免发送给匿名用户
                                                if($con->email != ''){
                                                    $con->send($sendJson1);
                                                    if($ElementData!==null){
                                                    $con->send($sendJson2);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                break;
                            }
                            case 'updateLayerOrder':{//更新图层排序
                                if(!$newQIR->arrayPropertiesCheck('passive',$jsonData['data'])){break;}
                                if(!$newQIR->arrayPropertiesCheck('active',$jsonData['data'])){break;}
                                if(!$newQIR->arrayPropertiesCheck('type',$jsonData['data'])){break;}
                                $passive=(int)$jsonData['data']['passive'];
                                $active=(int)$jsonData['data']['active'];
                                $nowType=$jsonData['data']['type'];
                                $affected=$newLDE->updateLayerOrder($passive,$active,$nowType);
                                if($affected!==false){
                                    $newOrder=$newLDE->getOrderMembers();
                                    if($newOrder!==[]){
                                        $broadcastEmail=$connection->email;
                                        $Time=creatDate();
                                        $sdJson=['type'=>'broadcast','class'=>'updateLayerOrder','conveyor'=>$broadcastEmail,'time'=>$Time,'data'=>['members'=>$newOrder]];
                                        $sendJson=json_encode($sdJson,JSON_UNESCAPED_UNICODE);
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
                                $creator="{$connection->userData['user_name']}({$connection->email})";
                                $refData=$newLDE->createGroupLayer($creator);//groupLayer&orderLayer
                                $broadcastEmail=$connection->email;
                                $Time=creatDate();
                                $sdJson=['type'=>'broadcast','class'=>'createGroupLayer','conveyor'=>$broadcastEmail,'time'=>$Time,'data'=>$refData['groupLayer']];
                                $sendJson1=json_encode($sdJson,JSON_UNESCAPED_UNICODE);
                                $sendOrderJson=['type'=>'broadcast','class'=>'updateLayerOrder','conveyor'=>$broadcastEmail,'time'=>$Time,'data'=>['members'=>$refData['orderLayer']]];
                                $sendJson2=json_encode($sendOrderJson,JSON_UNESCAPED_UNICODE);
                                foreach ($socket_worker->connections as $con) {
                                    if(property_exists($con,'email')){//避免发送给匿名用户
                                        if($con->email != ''){
                                            $con->send($sendJson1);
                                            $con->send($sendJson2);
                                        }
                                    }
                                }
                                $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$connection->email,'id'=>$refData['groupLayer']['id']];
                                createLog('createGroupLayer',$logData);
                                break;
                            }
                            case 'deleteLayerAndMembers':{//删除图层
                                if($newQIR->arrayPropertiesCheck('data',$jsonData)){
                                    if($newQIR->arrayPropertiesCheck('id',$jsonData['data'])){
                                        $deleteId=(int)$jsonData['data']['id'];
                                        $result=$newLDE->deleteLayerAndMembers($deleteId);
                                        if($result!==false){
                                            $broadcastEmail=$connection->email;
                                            $Time=creatDate();
                                            $sendOrderArr=['type'=>'broadcast','class'=>'updateLayerOrder','conveyor'=>$broadcastEmail,'time'=>$Time,'data'=>['members'=>$result['order']]];
                                            $sendOrderJson=json_encode($sendOrderArr,JSON_UNESCAPED_UNICODE);
                                            $sendDeleteArr=['type'=>'broadcast','class'=>'deleteLayerAndMembers','conveyor'=>$broadcastEmail,'time'=>$Time,'data'=>['id'=>$result['id'],'members'=>$result['members']]];
                                            $sendDeleteJson=json_encode($sendDeleteArr,JSON_UNESCAPED_UNICODE);
                                            foreach($socket_worker->connections as $con){
                                                if(property_exists($con,'email')){
                                                    if($con->email != ''){
                                                        $con->send($sendDeleteJson);
                                                        $con->send($sendOrderJson);
                                                    }
                                                }
                                            }
                                            $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$broadcastEmail,'id'=>$result['id']];
                                            createLog('deleteLayerAndMembers',$logData);
                                        }
                                    }
                                }
                                break;
                            }
                            case 'batchDeleteElement':{
                                try{
                                    $conveyor=$connection->email;
                                    $IDs=$jsonData['data']['id'];//Array
                                    $Time=creatDate();
                                    $psEndIds=[];//pick and select ids
                                    $sendJson0=null;//pickSelectEndElements
                                    $updateResult=$newMDE->updateElementsPhase($IDs,2);//更改数据库
                                    if($updateResult!=false){//成功
                                        /*
                                         *pick和select操作移除Start
                                         */
                                        foreach($IDs as $item){
                                            if(array_key_exists('E'.$item,$theData['activeElement'])) {//存在选中
                                                unset($theData['activeElement']['E'.$item]);//移除所有选中
                                                $psEndIds[]=(int)$item;
                                            }
                                        }
                                        if(count($psEndIds)!==0){//需要返回ps end elements
                                            $sendArray=[//返回数据
                                                'type'=>'broadcast','class'=>'pickSelectEndElements',
                                                'conveyor'=>'','time'=>$Time,
                                                'data'=>[
                                                    'id'=>$psEndIds
                                                ]
                                            ];
                                            $sendJson0=json_encode($sendArray,JSON_UNESCAPED_UNICODE);
                                        }
                                        /*
                                         *pick和select操作移除End
                                         */
                                        /*
                                         *图层移除元素操作Start
                                         */
                                        $changeLayerIds=[];//变动了的图层id
                                        $layersData=[];//变动了的图层数据
                                        $elements=$newMDE->getElementsByIds($IDs);//获取元素数据
                                        if($elements!==false){
                                            foreach($elements as $key=>$element){
                                                $CUSTOM=$elements['custom'];
                                                if($CUSTOM===null){continue;}
                                                $custom=json_decode($CUSTOM,true);//解析
                                                if($custom===null){continue;}//解析失败跳过
                                                if(!is_array($custom)){continue;}//不是数组跳过
                                                if(!array_key_exists('tmpId',$custom)){continue;}//不含tmpId属性跳过
                                                if(!is_string($custom['tmpId'])){continue;}//tmpId类型错误跳过
                                                $tmpId=$custom['tmpId'];//获取元素tmpId
                                                $eId=(int)$element['id'];
                                                $changeLayerId=$newLDE->removeElement($eId,$tmpId);
                                                if($changeLayerId!==-1){//图层变动
                                                    if(!in_array($changeLayerId,$changeLayerIds)){//判断是否已经添加
                                                        $changeLayerIds[]=$changeLayerId;
                                                    }
                                                }
                                            }
                                        }
                                        foreach($changeLayerIds as $layId){//获取新的图层数据
                                            $LayerData=$newLDE->getLayerMembersStructure($layId,false);
                                            if($LayerData!==false){
                                                $newLayerData=[
                                                    'id'=>(int)$layId,
                                                    'members'=>$LayerData['members'],
                                                    'structure'=>$LayerData['structure']
                                                ];
                                                $layersData[]=$newLayerData;
                                            }
                                        }
                                        /*
                                         *图层移除元素操作End
                                         */
                                        $sendArr=['type'=>'broadcast','class'=>'batchDeleteElement','conveyor'=>$conveyor,'time'=>$Time,'data'=>$updateResult];
                                        $sendJson1=json_encode($sendArr,JSON_UNESCAPED_UNICODE);
                                        $sdJson2=['type'=>'broadcast','class'=>'batchUpdateLayerData','conveyor'=>'','time'=>$Time,'data'=>$layersData];//组合
                                        $sendJson2=json_encode($sdJson2,JSON_UNESCAPED_UNICODE);//返回数据
                                        foreach($socket_worker->connections as $con){
                                            if(property_exists($con,'email')){//避免发送给匿名用户
                                                if($con->email != ''){
                                                    if($sendJson0!==null){
                                                    $con->send($sendJson0);//ps end element
                                                    }
                                                    $con->send($sendJson1);//batch delete ids
                                                    if(count($layersData)!==0){
                                                    $con->send($sendJson2);//batch update layer data
                                                    }
                                                }
                                            }
                                        }
                                        $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$conveyor,'id'=>implode(',',$IDs)];//写入日志
                                        createLog('batchDeleteElement',$logData);
                                    }
                                }catch (Exception $E){
                                    print_r('未知错误：batchDeleteElement收到不明信息:');
                                    print_r($jsonData);
                                }
                                break;
                            }
                            case 'renameLayer':{
                                if(!$newQIR->arrayPropertiesCheck('data',$jsonData)){break;}
                                if(!is_array($jsonData['data'])){break;}
                                if(!$newQIR->arrayPropertiesCheck('id',$jsonData['data'])){break;}
                                if(!$newQIR->arrayPropertiesCheck('name',$jsonData['data'])){break;}
                                $renameStatus=$newLDE->renameLayer($jsonData['data']['id'],$jsonData['data']['name']);
                                $Time=creatDate();
                                $broadcastEmail=$connection->email;
                                if($renameStatus){
                                    $sendObj=[
                                        'type'=>'broadcast','class'=>'renameLayer','conveyor'=>$broadcastEmail,'time'=>$Time,
                                        'data'=>['id'=>(int)$jsonData['data']['id'],'name'=>$jsonData['data']['name']]
                                    ];
                                    $sendJson=json_encode($sendObj,JSON_UNESCAPED_UNICODE);
                                    foreach ($socket_worker->connections as $con) {
                                        if(property_exists($con,'email')){
                                            if($con->email != ''){
                                                $con->send($sendJson);
                                            }
                                        }
                                    }
                                    $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$broadcastEmail,'id'=>$jsonData['data']['id'],'name'=>$jsonData['data']['name']];//写入日志
                                    createLog('renameLayer',$logData);
                                }
                            }
                            case 'updateTemplateData':{
                                $broadcastEmail=$connection->email;
                                if(!$newQIR->arrayPropertiesCheck('data',$jsonData)){break;}
                                if(!is_array($jsonData['data'])){break;}
                                if(!$newQIR->arrayPropertiesCheck('template',$jsonData['data'])){break;}
                                if(!is_array($jsonData['data']['template'])){break;}
                                $template=$jsonData['data']['template'];
                                $checkStatus=$newLDE->tpCheck($template);
                                if($checkStatus!==true){break;}
                                /*
                                  * 更新图层数据 start
                                  */
                                $affected=$newLDE->updateTemplateData($template);
                                /*
                                  * 更新图层数据 end
                                  */
                                $layerData=$newLDE->getLayerMembersStructure($affected['layer'],false);
                                $layerData['id']=$affected['layer'];
                                $layerData['templateVary']=true;
                                $sendJson0=$instruct->broadcast_updateLayerData($broadcastEmail,$layerData);
                                $sendJson1=$instruct->broadcast_batchUpdateElement($broadcastEmail,$affected['updateElements']);
                                $sendJson2=$instruct->broadcast_batchDeleteElement($broadcastEmail,$affected['deleteElements']);
                                $updateCount=count($affected['updateElements']);
                                $deleteCount=count($affected['deleteElements']['point'])+
                                                          count($affected['deleteElements']['line'])+
                                                          count($affected['deleteElements']['area'])+
                                                          count($affected['deleteElements']['curve']);
                                foreach ($socket_worker->connections as $con) {
                                    if(property_exists($con,'email')){
                                        if($con->email != ''){
                                            $con->send($sendJson0);
                                            if($updateCount!==0){$con->send($sendJson1);}
                                            if($deleteCount!==0){$con->send($sendJson2);}
                                        }
                                    }
                                }
                                $logData=['connectionId'=>$connection->id,'broadcastEmail'=>$broadcastEmail,'tmpId'=>$template['id']];//写入日志
                                createLog('updateTemplateData',$logData);
                            }
                            case 'forceUpdate':{//强制刷新客户端-实验性指令
                                $Time=creatDate();
                                $broadcastEmail=$connection->email;
                                $sendObj=['type'=>'broadcast','class'=>'forceUpdate','conveyor'=>$broadcastEmail,'time'=>$Time];
                                $sendJson=json_encode($sendObj,JSON_UNESCAPED_UNICODE);
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
                }break;
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
                        $sendJson = json_encode(['type'=>'send_serverImg','data'=>['string'=>$pngData,'time'=>$lt]],JSON_UNESCAPED_UNICODE);
                        $connection->send($sendJson);
                    }
                }
                }
                break;
            }
            case 'get_serverConfig':{//获取服务器的配置
                        $sendArr=['type'=>'send_serverConfig','data'=>[
                            /*
                             * 版本信息
                             */
                            'version'=>$Version,
                            /*
                             *以下为服务器属性配置信息
                             */
                            'anonymous_login'=>__ANONYMOUS_LOGIN__,
                            'key'=>__SERVER_CONFIG__KEY__,
                            'url'=>__SERVER_CONFIG__URL__,
                            'name'=>__SERVER_CONFIG__NAME__,
                            'online_number'=>getOnlineNumber(),
                            'max_online'=>__SERVER_CONFIG__MAX_USER__,
                            'default_x'=>__SERVER_CONFIG__DEFAULT_X__,
                            'default_y'=>__SERVER_CONFIG__DEFAULT_Y__,
                            /*底图
                             *以下为关于额外底图的配置信息
                             */
                            'enable_base_map'=>__SERVER_CONFIG__ENABLE_BASE_MAP__,
                            'base_map_type'=>__SERVER_CONFIG__BASE_MAP_TYPE__,
                            'base_map_url'=>__SERVER_CONFIG__BASE_MAP_URL__
                        ]];
                        $sendJson=json_encode($sendArr,JSON_UNESCAPED_UNICODE);
                        $connection->send($sendJson);
                        break;
                    }
            case 'get_activeData':{//获取元素活动的数据
                if($activated){
                    $sendData=[
                        'type'=>'send_activeData',
                        'data'=>$theData['activeElement']
                    ];
                    $sendJson=json_encode($sendData,JSON_UNESCAPED_UNICODE);
                    $connection->send($sendJson);
                    break;
                }
            }
            case 'get_publickey':{//获取公钥数据
                        $sendArr=['type'=>'publickey','data'=>getPublickey()];
                        $sendJson=json_encode($sendArr,JSON_UNESCAPED_UNICODE);
                        $connection->send($sendJson);
                        break;
                    }
            case 'login':{//登录
                        if(getOnlineNumber()>=__SERVER_CONFIG__MAX_USER__){//如果在线人数大于最大允许人数则不再接受新会话登录
                            break;
                        }
                        $Email=$jsonData['data']['email'];
                        $Password=$jsonData['data']['password'];
                        if(!is_string($Email) || !is_string($Password)){//检查是否为字符串
                            break;
                        }
                        $RealPws=RsaTranslate($Password,'decode');//1.解密
                        if(!$instruct->ckLogonAccount($Email,$RealPws)){//检查账号密码是否合法
                            break;
                        }
                        $logUserData=$newMDE->loginServer($Email,$RealPws);//2.数据库进行查询
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
                if(getOnlineNumber()>=__SERVER_CONFIG__MAX_USER__){//如果在线人数大于最大允许人数则不再接受新会话登录
                    break;
                }
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
                if($jsonData['data']['name']===''){
                    break;
                }
                $accountName=$jsonData['data']['name'];
                if(!$instruct->ckAnonymousLogonAccount($accountName)){
                    break;
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
                if($activated){//必须是非匿名会话才能使用
                    $theUserEmail = $connection->email;
                    if(substr($theUserEmail,-10)==='@anonymous'){//如果启用了匿名登录
                        $ref=$instruct->send_userData($connection->userData);
                        if($ref!==false){
                            $connection->send($ref);
                        }
                    }else{
                        $userData = $newMDE->getUserData($theUserEmail);
                        $ref=$instruct->send_userData($userData);
                        if($ref!==false){
                            $connection->send($ref);
                        }
                    }
                }
                break;
            }
            case 'get_mapData':{//获取地图数据
                if($activated){//必须是非匿名会话才能使用
                    $ref=$newMDE->getMapData();
                    $sendArr=['type'=>'send_mapData','data'=>$ref];//返回数据
                    $sendJson=json_encode($sendArr,JSON_UNESCAPED_UNICODE);
                    $connection->send($sendJson);
                }
                break;
            }
            case 'get_mapLayer':{//获取图层数据
                if($activated){//必须是非匿名会话才能使用
                    $ref=$newLDE->getLayerData(true);
                    $sendArr=['type'=>'send_mapLayer','data'=>$ref];
                    $sendJson=json_encode($sendArr,JSON_UNESCAPED_UNICODE);
                    $connection->send($sendJson);
                }
                break;
            }
            case 'get_presence':{//获取在线用户数据
                if($activated){//必须是非匿名会话才能使用
                    $usersList=[];//已经添加的用户
                    $usersData=[];//用户数据汇总
                    foreach($socket_worker->connections as $con){
                        if(property_exists($con,'userData')){//获取所有登录用户数据
                            $nowEmail=$con->userData['user_email'];
                            if(in_array($nowEmail,$usersList)){//跳过重复的用户数据
                                continue;
                            }
                            $userData=[
                                'userEmail'=>$nowEmail,
                                'userQq'=>$con->userData['user_qq'],
                                'userName'=>$con->userData['user_name'],
                                'headColor'=>$con->userData['head_color'],
                            ];
                            $usersData[]=$userData;
                            $usersList[]=$nowEmail;
                        }
                    }
                    $sendData=$instruct->send_presence($usersData);
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
    return true;
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
                    $sendJson=json_encode($sendArr,JSON_UNESCAPED_UNICODE);
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
                    $sendJson=json_encode($sendArr,JSON_UNESCAPED_UNICODE);
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
                    $sendJson=json_encode($sendArr,JSON_UNESCAPED_UNICODE);
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
    $users=[];
    foreach ($socket_worker->connections as $con){
        if(!property_exists($con,'email')){//匿名且未登录的socket
            continue;
        }
        if($con->email===''){//空用户
            continue;
        }
        if(in_array($con->email,$users)){//同一账号但不同会话的socket
            continue;
        }
        array_push($users,$con->email);
    }
    return count($users);
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
        case 'updateTemplateData':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Account为:{$logData['broadcastEmail']};tmpId为:{$logData['tmpId']};更新图层模板

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
        case 'renameLayer':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Account为:{$logData['broadcastEmail']};layerId为:{$logData['id']};newName为:{$logData['name']};更新图层名称

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
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
        case 'createGroupLayer':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Account为:{$logData['broadcastEmail']};新建分组图层id:{$logData['id']}

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
        case 'userAddCurve':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Account为:{$logData['broadcastEmail']};新增一条曲线:{$logData['addId']}

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
        case 'batchDeleteElement':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Account为:{$logData['broadcastEmail']};批量删除元素:{$logData['id']}

ETX;
            echo $log;
            fwrite($theConfig['logFile'],$log);
            break;
        }
        case 'deleteLayerAndMembers':{
            $log=<<<ETX

{$time}--连接Id为:{$logData['connectionId']};Account为:{$logData['broadcastEmail']};删除一个图层及其成员id:{$logData['id']}

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
    $sendJson=json_encode(
        ['type'=>'send_error','class'=>$class,'conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>['source'=>$data,'message'=>$message]],
        JSON_UNESCAPED_UNICODE
    );
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
    $sendJson=json_encode(
        ['type'=>'send_correct','class'=>$class,'conveyor'=>$broadcastEmail,'time'=>$dateTime,'data'=>$data],
        JSON_UNESCAPED_UNICODE
    );
    $con->send($sendJson);
}
/**
</internal-procedures>
 **/
/**
<test>
 **/

/**
</test>
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
  *
  */
$socket_worker->onWorkerStart=function($socket_worker){
    global $theConfig;
    /*
     * Time 1 自动断开超时未登录的连接
     */
    $socket_worker->timer_id_1=\Workerman\Lib\Timer::add(10,
        function () use($socket_worker){
            $time_now=time();
            foreach($socket_worker->connections as $connection){
                /*有可能该connection还没收到过消息，
                 *则lastMessageTime设置为当前时间
                 */
                if(empty($connection->lastMessageTime)){
                    $connection->lastMessageTime=$time_now;
                    continue;
                }
                /*上次通讯时间间隔大于心跳间隔，
                 *则认为客户端已经下线，关闭连接
                 */
                if(!property_exists($connection,'email')){//如果是匿名连接
                    if($time_now - $connection->lastMessageTime > HEARTBEAT_TIME){
                        echo "\n[自动任务]已经清理1个匿名会话...\n";
                        $connection->close();
                    }
                }
            }
        }
    );
    /*
    * Time 2 自动清理每个ip的连接次数统计
    */
    $socket_worker->timer_id_2=\Workerman\Lib\Timer::add($theConfig['automateTime'],
        function(){
            global $theData;
            //echo "\n[自动任务]正在清理ip连接统计...\n";
            $theData['ip_connect_times']=[];
        }
    );
    /*
    * Time 3 自动更新图层数据到数据库
    */
    $socket_worker->timer_id_3=\Workerman\Lib\Timer::add($theConfig['automateTime'],
        function(){
            global $newLDE;
            //echo "\n[自动任务]正在保存图层数据...\n";
            $newLDE->renewalLayerData();
        }
    );
    /*
    * Time 4 自动构建图层数据及模板Link
    */
    $socket_worker->timer_id_4=\Workerman\Lib\Timer::add($theConfig['automateTime'],
        function(){
            global $newLDE;
            //echo "\n[自动任务]正在构建模板链接...\n";
            $newLDE->buildTemplateLink();
        }
    );
    /*
    * Time 5 定期连接数据库
    */
    $socket_worker->timer_id_5=\Workerman\Lib\Timer::add($theConfig['automateTime'],
        function(){
            global $newMDE;
            //echo "\n[自动任务]正在心跳数据库...\n";
            $newMDE->pdoHeartbeat();
            $newMDE->ensureMysqliConnection();
        });
};
startSetting();
Worker::runAll();
/**
</worker-setting>
 **/