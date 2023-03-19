<?php
function GetUserData($email){
    global $mysql_public_server_address,$mysql_public_user,$mysql_public_password,$mysql_public_db_name;
    //检测输入
    //web端RegExp("[^a-zA-Z0-9\_@.+/=]","i");
    $pattern = "/[^a-zA-Z0-9_@.+\/=-]/";
    preg_match($pattern, $email,$res);
    if(count($res)>0){
        //存在不合规字符
        return false;
    }else{
        //连接库
        $link=mysqli_connect($mysql_public_server_address,$mysql_public_user,$mysql_public_password);
        //选择库
        $dataHouse=mysqli_select_db($link,$mysql_public_db_name);
        //编辑查询语句
        $sql="SELECT user_email,user_name,map_layer,default_a1,save_point,user_qq,head_color FROM account_data cou WHERE cou.user_email='$email'";
        $sqlTest=mysqli_query($link,$sql);
        if(mysqli_num_rows($sqlTest)==1){
            return mysqli_fetch_array($sqlTest,MYSQLI_ASSOC);
        }else{
            //相反
            return false;
        }
    }
}