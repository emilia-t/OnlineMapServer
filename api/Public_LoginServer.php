<?php
function LoginServer($email,$password){//存在则返回用户数据，否则返回false
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
        $sqlQuery=mysqli_query($link,$sql);
        if(mysqli_num_rows($sqlQuery)==1){
            $userData=mysqli_fetch_assoc($sqlQuery);
            return ['userEmail'=>$userData['user_email'],'userQq'=>$userData['user_qq'],'userName'=>$userData['user_name'],'headColor'=>$userData['head_color']];
        }else{
            //相反
            return false;
        }
    }
}