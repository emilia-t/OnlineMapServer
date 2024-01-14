<?php
function LoginServer($email,$password){//存在则返回用户数据，否则返回false
    global $mysql_public_server_address,$mysql_public_user,$mysql_public_password,$mysql_public_db_name;
    $pattern="/[^a-zA-Z0-9_@.+\/=-]/";
    preg_match($pattern,$email.$password,$res);//检测输入
    if(count($res)>0){//存在不合规字符
        return false;
    }else{
        $link=mysqli_connect(
            $mysql_public_server_address,
            $mysql_public_user,
            $mysql_public_password,
            $mysql_public_db_name);//连接数据库
        $sql="SELECT * FROM account_data cou WHERE cou.user_email=? AND cou.pass_word=?";//准备查询语句
        $stmt=mysqli_prepare($link,$sql);//创建预处理语句
        mysqli_stmt_bind_param($stmt,"ss",$email,$password);//绑定参数
        mysqli_stmt_execute($stmt);//执行查询
        $result=mysqli_stmt_get_result($stmt);//获取查询结果
        if(mysqli_num_rows($result)==1){//检查是否有结果
            $userData=mysqli_fetch_assoc($result);
            mysqli_close($link);
            return ['userEmail'=>$userData['user_email'],'userQq'=>$userData['user_qq'],'userName'=>$userData['user_name'],'headColor'=>$userData['head_color']];
        } else {
            mysqli_close($link);
            return false;
        }
    }
}