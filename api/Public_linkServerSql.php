<?php
error_reporting(0);
$userids = $_GET["uid"];
$passwords = $_GET["pwd"];
require_once('often.php');//判断特殊字符
require_once('mysql_700108.php');
$link = mysqli_connect($mysql_server_name,$mysql_user,$mysql_password);//连接数据库
$databasers = mysqli_select_db($link,"atsw");//连接库
//if (mb_strlen($username)>10){die("用户名过长（最长为10个字符）<a href='../denglu.html'>返回</a>");};
//if (mb_strlen($username)<2){die("用户名过短（最短为2个字符）<a href='../denglu.html'>返回</a>");};
//if (mb_strlen($password)>18){die("密码过长（最长为18）<a href='../denglu.html'>返回</a>");};
//if (mb_strlen($password)<8){die("密码过短（最短为8）<a href='../denglu.html'>返回</a>");};
//检查id是否对应token
@$sql_c_2="SELECT a.id,a.token FROM user_data a WHERE a.id='$userids' and a.token='$passwords'";
@$sql_c_2_t=mysqli_query($link,$sql_c_2);
if (mysqli_num_rows($sql_c_2_t)==1) {
//设置cookie用户名称
session_start();
$lifeTime = 336 * 3600;
$_SESSION["this_users"]=$userids;
setcookie($userids, session_id(), time() + $lifeTime, "/");
echo true;
}
else {echo false;}
mysqli_close($link);
