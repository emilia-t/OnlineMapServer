<?php
/**地图数据库操作工具-用于更新、新增、删除地图数据
 * 1.上传点数据
 * updatePointData
*/
class MapDataEdit
{
    private $mapDateSheetName;
    private $mapDateLayerName;
    private $linkMysqli;
    private $linkPdo;
    private $linkSqlite;
    private $isLinkMysqli;
    private $isLinkPdo;
    private $isLinkSqlite;
    public function __construct($dateSheetName='map_0_data',$dateLayerName='map_0_layer'){
        $this->mapDateSheetName=$dateSheetName;
        $this->mapDateLayerName=$dateLayerName;
        $this->startSetting();
    }
    function startSetting(){
        if(__DATABASE_NAME__==='mysql'){
            $this->linkDatabase();
            $this->testLayerOrder();
        }
        elseif(__DATABASE_NAME__==='sqlite'){
            $this->linkDatabaseSqlite();
            $this->testLayerOrderSqlite();
        }else{
            exit("[异常退出]因为使用的数据库不支持\n[Exception exit] Because the database being used does not support it\n");
        }
    }
    /**获取地图数据
     * @return array|false
     */
    function getMapDataSqlite(){
        try {
            if(__DATABASE_NAME__==='mysql'){
                return $this->getMapData();
            }
            $sql = "SELECT * FROM map_0_data WHERE phase!=2";//编辑查询语句
            $stmt = $this->linkSqlite->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }catch (PDOException $e){
            echo "数据库操作失败: " . $e->getMessage();
            return false;
        }
    }
    /**获取地图数据
     * @return array|false
     */
    function getMapData(){
        if(__DATABASE_NAME__==='sqlite'){
            return $this->getMapDataSqlite();
        }
        $sql = "SELECT * FROM $this->mapDateSheetName WHERE phase!=2";//编辑查询语句
        $sqlTest = mysqli_query($this->linkMysqli, $sql);
        if ($sqlTest) {
            return mysqli_fetch_all($sqlTest, MYSQLI_ASSOC);
        } else {
            return false;
        }
    }
    /**登录服务器
     * @param $email
     * @param $password
     * @return array|false
     */
    function loginServerSqlite($email,$password){
        $pattern="/[^a-zA-Z0-9_@.+\/=-]/";
        preg_match($pattern,$email.$password,$res);//检测输入
        if(count($res)>0){//存在不合规字符
            return false;
        }
        else{
            try {
                $sql="SELECT * FROM account_data WHERE user_email=:value1 AND pass_word=:value2";//准备查询语句
                $stmt=$this->linkSqlite->prepare($sql);
                $stmt->bindParam(':value1', $email, PDO::PARAM_STR);
                $stmt->bindParam(':value2', $password, PDO::PARAM_STR);
                $stmt->execute();// 执行查询
                $result = $stmt->fetch(PDO::FETCH_ASSOC);// 获取结果 关联数组
                $count=0;
                if($result!==false){
                    $count=count($result);
                }
                if($count>0){//检查是否有结果
                    $userData=$result;
                    return [
                        'user_email'=>$userData['user_email'],
                        'user_qq'=>$userData['user_qq'],
                        'user_name'=>$userData['user_name'],
                        'head_color'=>$userData['head_color']
                    ];
                } else {
                    return false;
                }
            }catch (PDOException $e){
                echo "数据库操作失败: " . $e->getMessage();
                return false;
            }
        }
    }
    /**登录服务器
     * @param $email
     * @param $password
     * @return array|false
     */
    function loginServer($email,$password){
        if(__DATABASE_NAME__==='sqlite'){
            return $this->loginServerSqlite($email,$password);
        }
        $pattern="/[^a-zA-Z0-9_@.+\/=-]/";
        preg_match($pattern,$email.$password,$res);//检测输入
        if(count($res)>0){//存在不合规字符
            return false;
        }
        else{
            $sql="SELECT * FROM account_data cou WHERE cou.user_email=? AND cou.pass_word=?";//准备查询语句
            $stmt=mysqli_prepare($this->linkMysqli,$sql);//创建预处理语句
            mysqli_stmt_bind_param($stmt,"ss",$email,$password); // 'ss' 指定两个参数类型为字符串
            mysqli_stmt_execute($stmt);//执行查询
            $result=mysqli_stmt_get_result($stmt);//获取查询结果
            if(mysqli_num_rows($result)==1){//检查是否有结果
                $userData=mysqli_fetch_assoc($result);
                return [
                    'user_email'=>$userData['user_email'],
                    'user_qq'=>$userData['user_qq'],
                    'user_name'=>$userData['user_name'],
                    'head_color'=>$userData['head_color']
                ];
            } else {
                return false;
            }
        }
    }
    /**获取用户数据
     * @param $email
     * @return array|false
     */
    function getUserDataSqlite($email){
        if(__DATABASE_NAME__==='mysql'){
            return $this->getUserData($email);
        }
        $pattern = "/[^a-zA-Z0-9_@.+\/=-]/";
        preg_match($pattern, $email,$res);
        if(count($res)>0){//存在不合规字符
            return false;
        }
        else{
            try {
                $sql="SELECT user_email, user_name, map_layer, default_a1, save_point, user_qq, head_color FROM account_data WHERE user_email = :value1";
                $stmt = $this->linkSqlite->prepare($sql);
                $stmt->bindValue(':value1', $email, PDO::PARAM_STR);
                $stmt->execute();
                return $stmt->fetch(PDO::FETCH_ASSOC);//如果返回值为空则返回false否则返回用户数据
            }catch (PDOException $e){
                echo "数据库操作失败: " . $e->getMessage();
                return false;
            }
        }
    }
    /**获取用户数据
     * @param $email
     * @return array|false
     */
    function getUserData($email){
        if(__DATABASE_NAME__==='sqlite'){
            return $this->getUserDataSqlite($email);
        }
        $pattern = "/[^a-zA-Z0-9_@.+\/=-]/";
        preg_match($pattern, $email,$res);
        if(count($res)>0){//存在不合规字符
            return false;
        }
        else{
            $sql="SELECT user_email, user_name, map_layer, default_a1, save_point, user_qq, head_color FROM account_data cou WHERE cou.user_email = ?";
            $stmt = $this->linkMysqli->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $email); // 's' 指定参数类型为字符串
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows == 1) {
                    $stmt->close();// 关闭语句
                    return $result->fetch_assoc();// 获取关联数组形式的结果
                } else {
                    $stmt->close();// 关闭语句
                    return false;
                }
            }
            else{
                error_log("预处理语句创建失败: " . $this->linkMysqli->error);
                return false;
            }
        }
    }

    /**用于防止PDO断连
     * @return bool
     */
    function pdoHeartbeatSqlite(){
        if(__DATABASE_NAME__==='mysql'){
            return $this->pdoHeartbeat();
        }
        $time=creatDate();
        $log="\n{$time}--PDO断开连接\n";
        if($this->linkSqlite->query("SELECT 1")){
            $this->isLinkPdo=true;
        }else{
            $this->isLinkPdo=false;
            echo $log;
        }
        return true;
    }
    /**用于防止PDO断连
     * @return bool
     */
    function pdoHeartbeat(){
        if(__DATABASE_NAME__==='sqlite'){
            return $this->pdoHeartbeatSqlite();
        }
        $time=creatDate();
        $log="\n{$time}--PDO断开连接\n";
        if($this->linkPdo->query("SELECT 1")){
            $this->isLinkPdo=true;
        }else{
            $this->isLinkPdo=false;
            echo $log;
        }
        return true;
    }

    /**用于防止Sqlite断连
     * @return bool
     */
    function ensureSqliteConnection(){
        if(__DATABASE_NAME__==='mysql'){
            return $this->ensureMysqliConnection();
        }
        $sqlite_db = __DIR__ . '/../tools/SQLite/data.sqlite'; // SQLite 数据库文件路径
        // 尝试执行一个简单的查询来检测连接是否有效
        if ($this->linkSqlite === null || !$this->linkSqlite->query('SELECT 1')) {
            if ($this->linkSqlite !== null) {
                $this->linkSqlite->close();
            }
            $this->linkSqlite = new PDO("sqlite:$sqlite_db");
            $this->linkSqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return true;
    }

    /**用于防止Mysqli断连
     * @return bool
     */
    function ensureMysqliConnection(){
        if(__DATABASE_NAME__==='sqlite'){
            return $this->ensureSqliteConnection();
        }
        global $mysql_public_server_address, $mysql_public_user, $mysql_public_password, $mysql_public_db_name;
        // 尝试执行一个简单的查询来检测连接是否有效
        if ($this->linkMysqli === null || !$this->linkMysqli->query('SELECT 1')) {
            if ($this->linkMysqli !== null) {
                $this->linkMysqli->close();
            }
            $this->linkMysqli = new mysqli($mysql_public_server_address,$mysql_public_user,$mysql_public_password,$mysql_public_db_name);
        }
        return true;
    }
    /**检测图层数据是否存在order
     * @return void
     */
    function testLayerOrderSqlite(){
        if($this->isLinkSqlite===true){
            $sql="SELECT id,type,members FROM map_0_layer WHERE type='order'";
            $stmt = $this->linkSqlite->query($sql);// 执行查询
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);// 获取结果
            $rowCount=0;
            if($rows!==false){
                $rowCount=count($rows);
            }
            if($rowCount===0){
                $insertSQL = "INSERT INTO map_0_layer(id, type, members, structure, phase) VALUES(:value1, :value2, :value3, :value4 ,:value5)";// 插入数据的 SQL 语句
                $stmt = $this->linkSqlite->prepare($insertSQL);// 预处理 SQL 语句
                $stmt->bindParam(':value1', 1);// 绑定参数
                $stmt->bindParam(':value2', 'order');
                $stmt->bindParam(':value3', '[]');
                $stmt->bindParam(':value4', '');
                $stmt->bindParam(':value5', 1);
                $stmt->execute();// 执行插入
            }
        }
    }
    /**检测图层数据是否存在order
     * @return void
     */
    function testLayerOrder(){
        if($this->isLinkPdo===true){
            $databaseLink=$this->linkPdo;
            $sql="SELECT id,type,members FROM $this->mapDateLayerName WHERE type='order'";
            $execute=$databaseLink->prepare($sql);
            $execute->execute();
            $rowCount=$execute->rowCount();
            if($rowCount===0){
                $addOrderSql="INSERT INTO $this->mapDateLayerName SET id=1,type='order',members='[]',structure=''";
                $execute=$databaseLink->prepare($addOrderSql);
                $execute->execute();
            }
        }
    }
    /**获取全部图层数据
     * @return array|boolean
     */
    function getAllLayerDataSqlite(){
        if(__DATABASE_NAME__==='mysql'){
            return $this->getAllLayerData();
        }
        try {
            $sql = "SELECT * FROM map_0_layer";
            $stmt = $this->linkSqlite->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $rowCount=count($rows);
            if($rowCount>0){
                return $rows;// 获取结果
            }else{
                return [];
            }
        } catch (PDOException $e) {
            echo '数据库查询错误: ' . $e->getMessage();
            return false;
        }
    }
    /**获取全部图层数据
     * @return array|boolean
     */
    function getAllLayerData(){
        if(__DATABASE_NAME__==='sqlite'){
            return $this->getAllLayerDataSqlite();
        }
        try {
            $dbh = $this->linkPdo;
            $sql = "SELECT * FROM $this->mapDateLayerName";
            $stmt = $dbh->prepare($sql);// 创建语句对象
            $stmt->execute();// 执行查询
            $rowCount=$stmt->rowCount();
            if($rowCount>0){
                return $stmt->fetchAll(PDO::FETCH_ASSOC);// 获取结果
            }else{
                return [];
            }
        } catch (PDOException $e) {
            echo '数据库查询错误: ' . $e->getMessage();
            return false;
        }
    }
    /**连接数据库
     * @return void
     */
    function linkDatabaseSqlite(){
        try{
            $sqlite_db = __DIR__ . '/../tools/SQLite/data.sqlite'; // SQLite 数据库文件路径
            $this->linkSqlite = new PDO("sqlite:$sqlite_db");
            $this->linkSqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->isLinkSqlite=true;
        }catch (PDOException $e){
            echo "数据库连接失败: " . $e->getMessage();
        }
    }
    /**连接数据库
     * @return void
     */
    function linkDatabase(){
        global $mysql_public_server_address, $mysql_public_user, $mysql_public_password, $mysql_public_db_name;
        $this->linkMysqli=new mysqli($mysql_public_server_address, $mysql_public_user, $mysql_public_password,$mysql_public_db_name);
        if($this->linkMysqli){
            $this->linkMysqli->set_charset("utf8");
            $this->isLinkMysqli=true;
        }else{
            $this->isLinkMysqli=false;
        }
        $dsn='mysql:host='.$mysql_public_server_address.';dbname='.$mysql_public_db_name.';charset=utf8';
        $options=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION];
        $this->linkPdo=new PDO($dsn,$mysql_public_user,$mysql_public_password,$options);
        if($this->linkPdo->query("SELECT 1")){
            $this->linkPdo->setAttribute(PDO::ATTR_PERSISTENT, true);
            $this->isLinkPdo=true;
        }else{
            $this->isLinkPdo=false;
        }
    }

    /**上传地图数据，请注意不要使用二维数组，二维值请转换为json数据
     * @param $lineObj array
     * @param $type string
     * @return int element id or -1
     */
    function uploadElementDataSqlite($lineObj,$type){
        if(__DATABASE_NAME__==='mysql'){
            return $this->uploadElementData($lineObj,$type);
        }
        try{
            if($type==='point' || $type==='line' || $type==='area' || $type==='curve'){
                $pdo=$this->linkPdo;
                $sql="INSERT INTO map_0_data (id,type,points,point,color,phase,width,child_relations,father_relation,child_nodes,father_node,details,custom) VALUES (NULL,:typ,:points,:point,:color,1,:width,NULL,NULL,NULL,NULL,:details,:custom)";
                $stmt=$this->linkSqlite->prepare($sql);//准备预处理语句

                $stmt->bindParam(':typ',$type);//执行预处理语句，并绑定参数
                $stmt->bindParam(':points',$lineObj['points']);
                $stmt->bindParam(':point',$lineObj['point']);
                $stmt->bindParam(':color',$lineObj['color']);
                $stmt->bindParam(':width',$lineObj['width']);
                $stmt->bindParam(':details',$lineObj['details']);
                $stmt->bindParam(':custom',$lineObj['custom']);
                $stmt->execute();
                if($stmt->rowCount()>0){//输出成功或失败的信息
                    return $this->linkSqlite->lastInsertId();
                }else{
                    return -1;
                }
            }else{
                return -1;
            }
        }catch(PDOException $e){
            echo "数据库操作失败: " . $e->getMessage();
            return -1;
        }
    }

    /**上传地图数据，请注意不要使用二维数组，二维值请转换为json数据
     * @param $lineObj array
     * @param $type string
     * @return int element id or -1
     */
    function uploadElementData($lineObj,$type){
        if(__DATABASE_NAME__==='sqlite'){
            return $this->uploadElementDataSqlite($lineObj,$type);
        }
        try{
            if($type==='point' || $type==='line' || $type==='area' || $type==='curve'){
                $pdo=$this->linkPdo;
                $sql="INSERT INTO {$this->mapDateSheetName} (id,type,points,point,color,phase,width,child_relations,father_relation,child_nodes,father_node,details,custom) VALUES (NULL,:typ,:points,:point,:color,1,:width,NULL,NULL,NULL,NULL,:details,:custom)";
                $stmt=$pdo->prepare($sql);//准备预处理语句

                $stmt->bindParam(':typ',$type);//执行预处理语句，并绑定参数
                $stmt->bindParam(':points',$lineObj['points']);
                $stmt->bindParam(':point',$lineObj['point']);
                $stmt->bindParam(':color',$lineObj['color']);
                $stmt->bindParam(':width',$lineObj['width']);
                $stmt->bindParam(':details',$lineObj['details']);
                $stmt->bindParam(':custom',$lineObj['custom']);
                $stmt->execute();
                if($stmt->rowCount()>0){//输出成功或失败的信息
                    return $pdo->lastInsertId();
                }else{
                    return -1;
                }
            }else{
                return -1;
            }
        }catch(Exception $E){
            return -1;
        }
    }
    /**更新一个地图要素的生命周期
     * @param int $elementId
     * @param int $phase
     * @return bool
     */
    function updateElementPhaseSqlite($elementId,$phase){
        if(__DATABASE_NAME__==='mysql'){
            return $this->updateElementPhase($elementId,$phase);
        }
        try{
            $updateSQL = "UPDATE map_0_data SET phase = :value1 WHERE id = :id";
            $stmt = $this->linkSqlite->prepare($updateSQL);
            $stmt->bindParam(':value1', $phase);
            $stmt->bindParam(':id', $elementId);
            $stmt->execute();
            if ($stmt->rowCount()>0) {
                return true;
            } else {
                return false;
            }
        }
        catch (Exception $E){
            return false;
        }
    }
    /**更新一个地图要素的生命周期
     * @param int $elementId
     * @param int $phase
     * @return bool
     */
    function updateElementPhase($elementId,$phase){
        if(__DATABASE_NAME__==='sqlite'){
            return $this->updateElementPhaseSqlite($elementId,$phase);
        }
        try{
            $link = $this->linkMysqli;
            $sql = "UPDATE {$this->mapDateSheetName} SET phase={$phase} WHERE id='{$elementId}'";
            $result = mysqli_query($link, $sql);
            if ($result) {
                if(mysqli_affected_rows($link)===1){
                    return true;
                }else{
                    return false;
                }
            } else {
                return false;
            }
        }
        catch (Exception $E){
            return false;
        }
    }
    /**更新多个地图要素的生命周期并且返回一个按元素类型分类后的数组
     * $phase为2时仅返回被删除的元素id，并且会按元素类型分组
     * $phase为1时返回被还原的元素数据，并且会按元素类型分组
     * @param array $ids
     * @param int $phase
     * @return array|false
     */
    function updateElementsPhaseSqlite($ids,$phase){
        if(__DATABASE_NAME__==='mysql'){
            return $this->updateElementsPhase($ids,$phase);
        }
        try{
            $change=['point'=>[],'line'=>[],'area'=>[],'curve'=>[]];
            $idsString=implode(',', $ids);
            $selectSql="SELECT * FROM map_0_data WHERE id IN ({$idsString})";
            $stmt = $this->linkSqlite->query($selectSql);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $count = count($result);
            if ($count!==0){//分类要操作的元素
                if($phase==2){//删除
                    foreach($result as $row){
                        if($row['type']=='point'){array_push($change['point'],(int)$row['id']);
                        }elseif($row['type']=='line'){array_push($change['line'],(int)$row['id']);
                        }elseif($row['type']=='area'){array_push($change['area'],(int)$row['id']);
                        }elseif($row['type']=='curve'){array_push($change['curve'],(int)$row['id']);}
                    }
                }elseif($phase==1){//还原
                    foreach($result as $row){
                        if($row['type']=='point'){array_push($change['point'],$row);
                        }elseif($row['type']=='line'){array_push($change['line'],$row);
                        }elseif($row['type']=='area'){array_push($change['area'],$row);
                        }elseif($row['type']=='curve'){array_push($change['curve'],$row);}
                    }
                }
            }
            if($count!==0){//执行删除/恢复操作
                $pha=1;
                if($phase==2)$pha=2;
                $updateSql="UPDATE map_0_data SET phase=:value1 WHERE id IN ({$idsString})";
                $stmt = $this->linkSqlite->prepare($updateSql);
                $stmt->bindParam(':value1', $pha);
                $stmt->execute();
                if($stmt->rowCount()>0){
                    return $change;
                }else{
                    return false;
                }
            }
        }
        catch (Exception $E){
            return false;
        }
    }
    /**更新多个地图要素的生命周期并且返回一个按元素类型分类后的数组
     * $phase为2时仅返回被删除的元素id，并且会按元素类型分组
     * $phase为1时返回被还原的元素数据，并且会按元素类型分组
     * @param array $ids
     * @param int $phase
     * @return array|false
     */
    function updateElementsPhase($ids,$phase){
        if(__DATABASE_NAME__==='sqlite'){
            return $this->updateElementsPhaseSqlite($ids,$phase);
        }
        try{
            $change=['point'=>[],'line'=>[],'area'=>[],'curve'=>[]];
            $link=$this->linkMysqli;
            $idsString=implode(',', $ids);
            $selectSql="SELECT * FROM {$this->mapDateSheetName} WHERE id IN ({$idsString})";
            $resultA=mysqli_query($link, $selectSql);
            if ($resultA){//分类要操作的元素
                if($phase==2){//删除
                    while($row=mysqli_fetch_assoc($resultA)){
                        if($row['type']=='point'){array_push($change['point'],(int)$row['id']);
                        }elseif($row['type']=='line'){array_push($change['line'],(int)$row['id']);
                        }elseif($row['type']=='area'){array_push($change['area'],(int)$row['id']);
                        }elseif($row['type']=='curve'){array_push($change['curve'],(int)$row['id']);}
                    }
                }elseif($phase==1){//还原
                    while($row=mysqli_fetch_assoc($resultA)){
                        if($row['type']=='point'){array_push($change['point'],$row);
                        }elseif($row['type']=='line'){array_push($change['line'],$row);
                        }elseif($row['type']=='area'){array_push($change['area'],$row);
                        }elseif($row['type']=='curve'){array_push($change['curve'],$row);}
                    }
                }
            }
            if($resultA){//执行删除/恢复操作
                $pha=1;
                if($phase==2)$pha=2;
                $updateSql="UPDATE {$this->mapDateSheetName} SET phase={$pha} WHERE id IN ({$idsString})";
                $resultB=mysqli_query($link,$updateSql);
                if($resultB){
                    if(mysqli_affected_rows($link)!=0){//受影响的行数大于0
                        return $change;
                    }else{
                        return false;
                    }
                }
            }
        }
        catch (Exception $E){
            return false;
        }
    }
    /**更新一个地图要素
     * @param array $newData
     * @return boolean
     */
    function updateElementDataSqlite($newData){
        if(__DATABASE_NAME__==='mysql'){
            return $this->updateElementData($newData);
        }
        try{
            $id=$newData['id'];//获取id属性
            unset($newData['id']);
            unset($newData['type']);
            $keys=array_keys($newData);//提取改变的属性名称
            $cols=implode('=?, ',$keys).'=?';//为每个属性名称添加占位符并在最后为id属性添加占位符
            $sql="UPDATE map_0_data SET {$cols} WHERE id=?";
            $stmt=$this->linkSqlite->prepare($sql);//添加预处理语句
            $params=array_values($newData);//重新索引数组
            $params[]=$id;//末尾添加id
            $stmt->execute($params);//执行预处理语句，并绑定参数
            if($stmt->rowCount()>0){
                return true;
            }else{
                return false;
            }
        }
        catch (PDOException $e){
            echo "数据库操作失败: " . $e->getMessage();
            return false;
        }
    }
    /**更新一个地图要素
     * @param array $newData
     * @return boolean
     */
    function updateElementData($newData){
        if(__DATABASE_NAME__==='sqlite'){
            return $this->updateElementDataSqlite($newData);
        }
        try{
            $db=$this->linkPdo;
            $id=$newData['id'];//获取id属性
            unset($newData['id']);
            unset($newData['type']);
            $keys=array_keys($newData);//提取改变的属性名称
            $cols=implode('=?, ',$keys).'=?';//为每个属性名称添加占位符并在最后为id属性添加占位符
            $sql="UPDATE {$this->mapDateSheetName} SET {$cols} WHERE id=?";
            $stmt=$db->prepare($sql);//添加预处理语句
            $params=array_values($newData);//重新索引数组
            $params[]=$id;//末尾添加id
            $stmt->execute($params);//执行预处理语句，并绑定参数
            if($stmt->rowCount()>0){
                return true;
            }else{
                return false;
            }
        }
        catch (Exception $E){
            return false;
        }
    }
    /**根据ID获取一个地图要素的数据 返回的是数据库原始数据一纬数组
     * @param int $elementId
     * @return array | false
     */
    function getElementByIdSqlite($elementId){
        if(__DATABASE_NAME__==='mysql'){
            return $this->getElementById($elementId);
        }
        try {
            $sql = "SELECT * FROM map_0_data WHERE id = :id";
            $stmt = $this->linkSqlite->prepare($sql);// 创建语句对象
            $stmt->bindParam(':id', $elementId, PDO::PARAM_INT);// 绑定参数
            $stmt->execute();// 执行查询
            $row=$stmt->fetch(PDO::FETCH_ASSOC);//返回false或数组
            if($row===false){
                return false;
            }else{
                return $row;
            }
        } catch (PDOException $e) {
            echo "\n";
            echo '数据库查询错误: ' . $e->getMessage();
            echo "\n";
            return false;
        }
    }
    /**根据ID获取一个地图要素的数据 返回的是数据库的原始数据一纬数组
     * @param int $elementId
     * @return array | false
     */
    function getElementById($elementId){
        if(__DATABASE_NAME__==='sqlite'){
            return $this->getElementByIdSqlite($elementId);
        }
        try {
            $dbh = $this->linkPdo;
            $sql = "SELECT * FROM $this->mapDateSheetName WHERE id = :id";
            $stmt = $dbh->prepare($sql);// 创建语句对象
            $stmt->bindParam(':id', $elementId, PDO::PARAM_INT);// 绑定参数
            $stmt->execute();// 执行查询
            $rowCount=$stmt->rowCount();
            if($rowCount===1){
                return $stmt->fetch(PDO::FETCH_ASSOC);// 获取结果
            }else{
                return false;
            }
        } catch (PDOException $e) {
            echo "\n";
            echo '数据库查询错误: ' . $e->getMessage();
            echo "\n";
            return false;
        }
    }
    /**根据多个ID获取多个地图要素的数据
     * @param array $elementIds
     * @return false
     */
    function getElementsByIdsSqlite($elementIds){
        if(__DATABASE_NAME__==='mysql'){
            return $this->getElementsByIds($elementIds);
        }
        try {
            $sql="SELECT * FROM map_0_data WHERE id IN (:ids)";
            $stmt=$this->linkSqlite->prepare($sql);// 创建语句对象
            $idsStr=implode(',', $elementIds);
            $stmt->bindParam(':ids', $idsStr, PDO::PARAM_INT);// 绑定参数
            $stmt->execute();// 执行查询
            $rowCount=$stmt->rowCount();
            if($rowCount!==0){
                return $stmt->fetch(PDO::FETCH_ASSOC);// 获取结果
            }else{
                return false;
            }
        } catch (PDOException $e) {
            echo "\n";
            echo '数据库查询错误: ' . $e->getMessage();
            echo "\n";
            return false;
        }
    }
    /**根据多个ID获取多个地图要素的数据
     * @param array $elementIds
     * @return false
     */
    function getElementsByIds($elementIds){
        if(__DATABASE_NAME__==='sqlite'){
            return $this->getElementsByIdsSqlite($elementIds);
        }
        try {
            $dbh = $this->linkPdo;
            $sql="SELECT * FROM $this->mapDateSheetName WHERE id IN (:ids)";
            $stmt=$dbh->prepare($sql);// 创建语句对象
            $idsStr=implode(',', $elementIds);
            $stmt->bindParam(':ids', $idsStr, PDO::PARAM_INT);// 绑定参数
            $stmt->execute();// 执行查询
            $rowCount=$stmt->rowCount();
            if($rowCount!==0){
                return $stmt->fetch(PDO::FETCH_ASSOC);// 获取结果
            }else{
                return false;
            }
        } catch (PDOException $e) {
            echo "\n";
            echo '数据库查询错误: ' . $e->getMessage();
            echo "\n";
            return false;
        }
    }
    /**更新图层数据
     * @param array $newData
     * @return boolean
     */
    /**---------------------------------**/
    /**These functions were not used**/
    /**-----You can ignore them-----**/
    /**-------↓----------------↓-------**/
    function updateLayerData($newData){
        try{
            $db=$this->linkPdo;
            //获取id属性
            $id=$newData['id'];
            //移除id属性
            unset($newData['id']);
            $keys=array_keys($newData);
            $cols=implode('=?, ',$keys).'=?';//在最后一个键名后面加上"=?"
            $sql="UPDATE {$this->mapDateLayerName} SET {$cols} WHERE id=?";
            //准备预处理语句
            $stmt=$db->prepare($sql);
            //执行预处理语句，并绑定参数
            $params=array_values($newData);
            $params[]=$id;
            $stmt->execute($params);
            //输出成功或失败的信息
            if ($stmt->rowCount()>0){
                return true;
            } else {
                return false;
            }
        }
        catch (Exception $E){
            return false;
        }
    }
    /**获取排序图层的数据
     * @return array | boolean
     */
    function getOrderLayerData(){
        try {
            $databaseLink=$this->linkPdo;
            $getSql="SELECT members FROM {$this->mapDateLayerName} WHERE type='order'";
            $execute=$databaseLink->prepare($getSql);//创建语句对象
            $execute->execute();//执行查询
            $rowCount=$execute->rowCount();
            if($rowCount>0){
                return $execute->fetch(PDO::FETCH_ASSOC);//获取结果
            }else{
                return [];
            }
        } catch (PDOException $e) {
            echo '数据库查询错误: ' . $e->getMessage();
            return false;
        }
    }
    /**更新排序图层的数据
     * @param $layerId | int
     * @param $means | string
     * @return boolean
     * 更新成功后会返回新排序图层的members的JSON
     */
    function updateOrderLayerData($layerId,$means){
        try{
            if(!is_numeric($layerId) || !is_string($means)){
                return false;
            }
            $databaseLink=$this->linkPdo;
            $membersSql="SELECT members FROM $this->mapDateLayerName WHERE type='order'";
            $execute=$databaseLink->prepare($membersSql);
            $execute->execute();
            $members=null;
            if($execute->rowCount()>0){
                $members=$execute->fetch(PDO::FETCH_ASSOC)['members'];
                $members=json_decode($members,true);
                $error=json_last_error();
                if(!empty($error)) {
                    return false;
                }else{
                    if(is_array($members)){
                        switch ($means){
                            case 'push':{
                                array_push($members,$layerId);
                                $newMembers=json_encode($members,JSON_UNESCAPED_UNICODE);
                                $updateMembersSql="UPDATE {$this->mapDateLayerName} SET members=? WHERE type='order'";
                                $execute=$databaseLink->prepare($updateMembersSql);
                                $execute->execute([$newMembers]);
                                $affectedCount=$execute->rowCount();
                                if($affectedCount>0) {
                                    return $newMembers;
                                }else{
                                    return false;
                                }
                                break;
                            }
                            case 'remove':{
                                $indexId=array_search($layerId,$members,true);
                                if($indexId!==false){
                                    array_splice($members,$indexId,1);
                                    $newMembers=json_encode($members,JSON_UNESCAPED_UNICODE);
                                    $updateMembersSql="UPDATE {$this->mapDateLayerName} SET members=? WHERE type='order'";
                                    $execute=$databaseLink->prepare($updateMembersSql);
                                    $execute->execute([$newMembers]);
                                    $affectedCount=$execute->rowCount();
                                    if($affectedCount>0) {
                                        return $newMembers;
                                    }else{
                                        return false;
                                    }
                                }else{
                                    return false;
                                }
                                break;
                            }
                        }
                    }
                }
            }else{
                return false;
            }
        }
        catch (Exception $E){
            return false;
        }
    }
    /**调整图层顺序
     * @param $newMembers | array
     * @return boolean array
     */
    function adjustOrderLayerData($newMembers){
        try{
            $databaseLink=$this->linkPdo;
            $newMembers=json_encode($newMembers,JSON_UNESCAPED_UNICODE);
            $updateMembersSql="UPDATE {$this->mapDateLayerName} SET members=? WHERE type='order'";
            $execute=$databaseLink->prepare($updateMembersSql);
            $execute->execute([$newMembers]);
            $affectedCount=$execute->rowCount();
            if($affectedCount>0) {
                return $newMembers;
            }else{
                return false;
            }
        }catch (Exception $E){
            return false;
        }
    }
    /**创建图层数据
     * @param array $newData
     * @return boolean
     */
    function createLayerData($newData){
        try{
            $db=$this->linkPdo;
            $sql="INSERT INTO {$this->mapDateLayerName} (type,members,structure) VALUES (?,?,?)";
            //准备预处理语句
            $stmt=$db->prepare($sql);
            //执行预处理语句，并绑定参数
            $params=array_values($newData);
            $stmt->execute($params);
            //输出成功或失败的信息
            if ($stmt->rowCount()>0){
                return true;
            } else {
                return false;
            }
        }
        catch (Exception $E){
            print_r("createLayerData异常\n");
            return false;
        }
    }
    /**更新图层phase状态
     * @param $id | int
     * @param $phase | int
     * @return boolean
     */
    function updateLayerPhase($id,$phase){
        try{
            if(!is_numeric($id) || !is_numeric($phase)){
                return false;
            }
            $databaseLink=$this->linkPdo;
            $updateLayerPhaseSql="UPDATE {$this->mapDateLayerName} SET phase=? WHERE type!='order' AND id=?";
            $execute=$databaseLink->prepare($updateLayerPhaseSql);
            $execute->execute([$phase,$id]);
            if ($execute->rowCount()>0){
                return true;
            } else {
                return false;
            }
        }
        catch (Exception $E){
            print_r("updateLayerPhase异常\n");
            return false;
        }
    }
    /**更新图层成员的phase状态并且返回删除成功的成员数组，失败则返回false
     * @param $id | int
     * @param $phase | int
     * @return boolean | array
     */
    function updateLayerMembersPhase($id,$phase){
        try{
            if(!is_numeric($id) || !is_numeric($phase)){
                return false;
            }
            $databaseLink=$this->linkPdo;
            $SqlGetMembers="SELECT members FROM {$this->mapDateLayerName} WHERE id=$id";
            $MembersExecute=$databaseLink->query($SqlGetMembers);
            $MembersResult=$MembersExecute->fetch();//['members'=>'']
            $MembersObj=json_decode($MembersResult['members']);
            $ref=[];//['145'=>1,'id'=>TypeNumber]
            foreach($MembersObj as $key=>$value){
                $status=$this->updateElementPhase($key,2);
                if($status===true){
                    $ref[$key]=$value;
                }
            }
            return $ref;
        }
        catch (Exception $E){
            print_r("updateLayerMembersPhase异常\n");
            return false;
        }
    }
    /**获取图层数据
     * @return array|boolean
     */
    function getLayerData(){
        try {
            $dbh = $this->linkPdo;
            $sql = "SELECT * FROM $this->mapDateLayerName WHERE phase=1";
            $stmt = $dbh->prepare($sql);// 创建语句对象
            $stmt->execute();// 执行查询
            $rowCount=$stmt->rowCount();
            if($rowCount>0){
                return $stmt->fetchAll(PDO::FETCH_ASSOC);// 获取结果
            }else{
                return [];
            }
        } catch (PDOException $e) {
            echo '数据库查询错误: ' . $e->getMessage();
            return false;
        }
    }
    /**查询最新的ID号
     * @param
     * @return int|false
     */
    function selectNewId(){
        try {
            $link = $this->linkMysqli;
            //编辑查询语句
            $sql = "SELECT max(id) FROM {$this->mapDateSheetName}";
            $sqlQu = mysqli_query($link, $sql);
            $ref=mysqli_fetch_array($sqlQu,MYSQLI_NUM);
            if ($sqlQu) {
                return $ref[0];
            } else {
                return false;
            }
        }catch (Exception $E){
            return false;
        }
    }
    /**查询最新的图层ID号
     * @param
     * @return int|false
     */
    function selectNewLayerId(){
        try {
            $link = $this->linkMysqli;
            //编辑查询语句
            $sql = "SELECT max(id) FROM {$this->mapDateLayerName}";
            $sqlQu = mysqli_query($link, $sql);
            $ref=mysqli_fetch_array($sqlQu,MYSQLI_NUM);
            if ($sqlQu) {
                return $ref[0];
            } else {
                return false;
            }
        }catch (Exception $E){
            return false;
        }
    }
    /**完全删除一个地图要素
     * @param int $elementId
     * @return bool
     */
    function deleteElementData($elementId){
        try{
            $link = $this->linkMysqli;
            $sql = "DELETE FROM {$this->mapDateSheetName} WHERE id='{$elementId}'";
            $sqlQu = mysqli_query($link, $sql);
            if ($sqlQu) {
                return true;
            } else {
                return false;
            }
        }
        catch (Exception $E){
            return false;
        }
    }
    /**-------↑----------------↑-------**/
    /**These functions were not used**/
    /**-----You can ignore them-----**/
    /**---------------------------------**/
}