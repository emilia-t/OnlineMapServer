<?php
/**地图数据库操作工具
 * 1.上传点数据
 * updatePointData
*/
class MapDataBaseEdit
{
    /**上传点数据，请注意不要使用二维数组，尔维值请转为json->base64
     * @param $pointObj array
     * @return array|false
     */
    function updatePointData($pointObj){
        try {
            global $mysql_public_server_address, $mysql_public_user, $mysql_public_password, $mysql_public_db_name;
            //连接库
            $link = mysqli_connect($mysql_public_server_address, $mysql_public_user, $mysql_public_password);
            //选择库
            $dataHouse = mysqli_select_db($link, $mysql_public_db_name);
            //编辑查询语句
            $sql = "INSERT INTO map_0_data (id,type,points,point,color,length,width,size,child_relations,father_relation,child_nodes,father_node,details) VALUES (NULL,'{$pointObj['type']}','{$pointObj['points']}','{$pointObj['point']}','{$pointObj['color']}',NULL,{$pointObj['width']},NULL,NULL,NULL,NULL,NULL,'{$pointObj['details']}')";
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
            $sql = "SELECT max(id) FROM map_0_data";
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
}