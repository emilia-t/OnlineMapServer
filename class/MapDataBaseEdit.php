<?php
/**地图数据库操作工具-用于更新、新增、删除地图数据
 * 1.上传点数据
 * updatePointData
*/
class MapDataBaseEdit
{
    private $mapDateSheetName;
    public function __construct($dateSheetName='map_0_data'){
        $this->mapDateSheetName=$dateSheetName;
    }
    /**上传线数据，请注意不要使用二维数组，二维值请转为json->base64
     * @param $lineObj array
     * @return bool
     */
    function uploadLineData($lineObj){
        try {
            global $mysql_public_server_address, $mysql_public_user, $mysql_public_password, $mysql_public_db_name;
            // 连接数据库
            $dsn='mysql:host='.$mysql_public_server_address.';dbname='.$mysql_public_db_name;
            $options=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION];
            $db=new PDO($dsn,$mysql_public_user,$mysql_public_password,$options);
            $sql="INSERT INTO {$this->mapDateSheetName} (id,type,points,point,color,phase,width,child_relations,father_relation,child_nodes,father_node,details) VALUES (NULL,:typ,:points,:point,:color,:phase,:width,NULL,NULL,NULL,NULL,:details)";
            //准备预处理语句
            $stmt=$db->prepare($sql);
            //执行预处理语句，并绑定参数
            $stmt->bindParam(':typ',$lineObj['type']);
            $stmt->bindParam(':points',$lineObj['points']);
            $stmt->bindParam(':point',$lineObj['point']);
            $stmt->bindParam(':color',$lineObj['color']);
            $stmt->bindParam(':width',$lineObj['width']);
            $stmt->bindParam(':details',$lineObj['details']);
            $stmt->bindParam(':phase',$lineObj['phase']);
            $stmt->execute();
            //输出成功或失败的信息
            if ($stmt->rowCount()>0){
                return true;
            } else {
                return false;
            }
        }catch (Exception $E){
            return false;
        }
    }
    /**上传点数据，请注意不要使用二维数组，二维值请转为json->base64
     * @param $pointObj array
     * @return bool
     */
    function uploadPointData($pointObj){
        try {
            global $mysql_public_server_address, $mysql_public_user, $mysql_public_password, $mysql_public_db_name;
            //连接库
            $link = mysqli_connect($mysql_public_server_address, $mysql_public_user, $mysql_public_password);
            //选择库
            $dataHouse = mysqli_select_db($link, $mysql_public_db_name);
            //编辑查询语句
            $sql = "INSERT INTO {$this->mapDateSheetName} (id,type,points,point,color,phase,width,child_relations,father_relation,child_nodes,father_node,details,custom) VALUES (NULL,'{$pointObj['type']}','{$pointObj['points']}','{$pointObj['point']}','{$pointObj['color']}',{$pointObj['phase']},{$pointObj['width']},NULL,NULL,NULL,NULL,'{$pointObj['details']}','{$pointObj['custom']}')";
            $sqlQu = mysqli_query($link, $sql);
            if ($sqlQu) {
                mysqli_close($link);
                return true;
            } else {
                //相反
                mysqli_close($link);
                return false;
            }
        }catch (Exception $E){
            return false;
        }
    }
    /**查询最新的ID号
     * @param
     * @return int|false
     */
    function selectNewId(){
        try {
            global $mysql_public_server_address, $mysql_public_user, $mysql_public_password, $mysql_public_db_name;
            //连接库
            $link = mysqli_connect($mysql_public_server_address, $mysql_public_user, $mysql_public_password);
            //选择库
            $dataHouse = mysqli_select_db($link, $mysql_public_db_name);
            //编辑查询语句
            $sql = "SELECT max(id) FROM {$this->mapDateSheetName}";
            $sqlQu = mysqli_query($link, $sql);
            $ref=mysqli_fetch_array($sqlQu,MYSQLI_NUM);
            if ($sqlQu) {
                mysqli_close($link);
                return $ref[0];
            } else {
                //相反
                mysqli_close($link);
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
            global $mysql_public_server_address, $mysql_public_user, $mysql_public_password, $mysql_public_db_name;
            //连接库
            $link = mysqli_connect($mysql_public_server_address, $mysql_public_user, $mysql_public_password);
            //选择库
            $dataHouse = mysqli_select_db($link, $mysql_public_db_name);
            //编辑查询语句
            $sql = "DELETE FROM {$this->mapDateSheetName} WHERE id='{$elementId}'";
            $sqlQu = mysqli_query($link, $sql);
            if ($sqlQu) {
                mysqli_close($link);
                return true;
            } else {
                //相反
                mysqli_close($link);
                return false;
            }
        }
        catch (Exception $E){
            return false;
        }
    }
    /**更新一个地图要素的生命周期
     * @param int $elementId
     * @return bool
     */
    function updateElementPhase($elementId,$phase){
        try{
            global $mysql_public_server_address, $mysql_public_user, $mysql_public_password, $mysql_public_db_name;
            //连接库
            $link = mysqli_connect($mysql_public_server_address, $mysql_public_user, $mysql_public_password);
            //选择库
            $dataHouse = mysqli_select_db($link, $mysql_public_db_name);
            //编辑查询语句
            $sql = "UPDATE {$this->mapDateSheetName} SET phase={$phase} WHERE id='{$elementId}'";
            $sqlQu = mysqli_query($link, $sql);
            if ($sqlQu) {
                mysqli_close($link);
                return true;
            } else {
                //相反
                mysqli_close($link);
                return false;
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
    function updateElementData($newData){
        try{
            global $mysql_public_server_address, $mysql_public_user, $mysql_public_password, $mysql_public_db_name;
            // 连接数据库
            $dsn='mysql:host='.$mysql_public_server_address.';dbname='.$mysql_public_db_name;
            $options=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION];
            $db=new PDO($dsn,$mysql_public_user,$mysql_public_password,$options);
            //获取id属性
            $id=$newData['id'];
            //移除id属性
            unset($newData['id']);
            $keys=array_keys($newData);
            $cols=implode('=?, ',$keys).'=?';//在最后一个键名后面加上"=?"
            $sql="UPDATE {$this->mapDateSheetName} SET {$cols} WHERE id=?";
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
    /**根据ID获取一个地图要素的数据
     * @param int $elementId
     * @return array
     */
    function getElementById($elementId){
        global $mysql_public_server_address, $mysql_public_user, $mysql_public_password, $mysql_public_db_name;
        // 建立数据库连接
        $dsn = "mysql:host=$mysql_public_server_address;dbname=$mysql_public_db_name;charset=utf8";
        $user = $mysql_public_user;
        $password = $mysql_public_password;
        try {
            $dbh = new PDO($dsn, $user, $password);
            $sql = "SELECT * FROM $this->mapDateSheetName WHERE id = :id";
            $stmt = $dbh->prepare($sql);// 创建语句对象
            $stmt->bindParam(':id', $elementId, PDO::PARAM_INT);// 绑定参数
            $stmt->execute();// 执行查询
            return $stmt->fetch(PDO::FETCH_ASSOC);// 获取结果
        } catch (PDOException $e) {
            echo '数据库查询错误: ' . $e->getMessage();
        }
        $dbh = null;// 关闭连接
    }
}