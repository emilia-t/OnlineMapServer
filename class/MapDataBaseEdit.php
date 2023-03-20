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
    /**上传点数据，请注意不要使用二维数组，尔维值请转为json->base64
     * @param $pointObj array
     * @return bool
     */
    function updatePointData($pointObj){
        try {
            global $mysql_public_server_address, $mysql_public_user, $mysql_public_password, $mysql_public_db_name;
            //连接库
            $link = mysqli_connect($mysql_public_server_address, $mysql_public_user, $mysql_public_password);
            //选择库
            $dataHouse = mysqli_select_db($link, $mysql_public_db_name);
            //编辑查询语句
            $sql = "INSERT INTO {$this->mapDateSheetName} (id,type,points,point,color,length,width,size,child_relations,father_relation,child_nodes,father_node,details) VALUES (NULL,'{$pointObj['type']}','{$pointObj['points']}','{$pointObj['point']}','{$pointObj['color']}',NULL,{$pointObj['width']},NULL,NULL,NULL,NULL,NULL,'{$pointObj['details']}')";
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
    /**删除一个地图要素
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
    /**更新一个地图要素
     * @param int $elementId
     * @return false
     */
    function updateElementData($elementId,$newData){
        try{
            global $mysql_public_server_address, $mysql_public_user, $mysql_public_password, $mysql_public_db_name;
            //连接库
            $link = mysqli_connect($mysql_public_server_address, $mysql_public_user, $mysql_public_password);
            //选择库
            $dataHouse = mysqli_select_db($link, $mysql_public_db_name);
            //编辑查询语句--这里需要根据有哪些需要更新的列值进行构建sql语句
            $sql = "UPDATE {$this->mapDateSheetName} SET points='{$newData['']}',point='{$newData['']}',color='{$newData['']}',length='{$newData['']}',width='{$newData['']}',size='1',child_relations='1',father_relation='1',child_nodes='1',father_node='1',details='1' WHERE id='106'";
            $sqlQu = mysqli_query($link, $sql);
            $ref=mysqli_fetch_array($sqlQu,MYSQLI_NUM);
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
}