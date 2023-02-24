<?php
function GetMapData(){
    global $mysql_public_server_address, $mysql_public_user, $mysql_public_password, $mysql_public_db_name;
    //连接库
    $link = mysqli_connect($mysql_public_server_address, $mysql_public_user, $mysql_public_password);
    //选择库
    $dataHouse = mysqli_select_db($link, $mysql_public_db_name);
    //编辑查询语句
    $sql = "SELECT * FROM map_0_data";
    $sqlTest = mysqli_query($link, $sql);
    if ($sqlTest) {
        return mysqli_fetch_all($sqlTest, MYSQLI_ASSOC);
    } else {
        //相反
        return false;
    }
}