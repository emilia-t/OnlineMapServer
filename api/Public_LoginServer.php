<?php
function LoginServer($email,$password){
    global $mysql_public_server_address,$mysql_public_user,$mysql_public_password,$mysql_public_db_name;
    //检测输入
    $pattern = "/[^a-zA-Z0-9_@.+\/=-]/";
    preg_match($pattern, $email.$password,$res);
    if(count($res)>0){
        //存在不合规字符
        return false;
    }else{
        //连接库
        $link=mysqli_connect($mysql_public_server_address,$mysql_public_user,$mysql_public_password);
        //选择库
        $dataHouse=mysqli_select_db($link,$mysql_public_db_name);
        //编辑查询语句
        $sql="SELECT * FROM account_data cou WHERE cou.user_email='$email' AND cou.pass_word='$password'";
        $sqlTest=mysqli_query($link,$sql);
        if(mysqli_num_rows($sqlTest)==1){
            //正确的密码和账号
            return true;
        }else{
            //相反
            return false;
        }
    }
}