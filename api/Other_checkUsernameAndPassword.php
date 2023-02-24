<?php
function checkUsernameAndPassword($username,$password){
    if (strpos("$username","'")){die("请勿输入 ' <a href='../zhuce.html'>返回</a>");};
    if (strpos("$username","#")){die("请勿输入 # <a href='../zhuce.html'>返回</a>");};
    if (strpos("$username",'"')){die('请勿输入 " <a href="../zhuce.html">返回</a>');};
    if (strpos("$username","or")){die("请勿输入 or <a href='../zhuce.html'>返回</a>");};
    if (strpos("$username","OR")){die("请勿输入 OR <a href='../zhuce.html'>返回</a>");};
    if (strpos("$username","and")){die("请勿输入 and <a href='../zhuce.html'>返回</a>");};
    if (strpos("$username","AND")){die("请勿输入 AND <a href='../zhuce.html'>返回</a>");};
    if (strpos("$username","null")){die("请勿输入 null <a href='../zhuce.html'>返回</a>");};
    if (strpos("$username","NULL")){die("请勿输入 NULL <a href='../zhuce.html'>返回</a>");};
    if (strpos("$username","/")){die("请勿输入 / <a href='../zhuce.html'>返回</a>");};
    if (strpos("$username","\\")){die("请勿输入 \ <a href='../zhuce.html'>返回</a>");};
    if (strpos("$username"," ")){die("请勿输入 空格 <a href='../zhuce.html'>返回</a>");};
    if (strpos("$username","-- ")){die("请勿输入--  <a href='../zhuce.html'>返回</a>");};
    if (strpos("$username","''")){die("请勿输入 \'\' <a href='../zhuce.html'>返回</a>");};
    if (strpos("$username","'''")){die("请勿输入 \'\' <a href='../zhuce.html'>返回</a>");};
    if (strpos("$username","/*")){die("请勿输入 /* <a href='../zhuce.html'>返回</a>");};

    if (strpos("$password","'")){die("请勿输入 ' <a href='../zhuce.html'>返回</a>");};
    if (strpos("$password","#")){die("请勿输入 # <a href='../zhuce.html'>返回</a>");};
    if (strpos("$password",'"')){die('请勿输入 " <a href="../zhuce.html">返回</a>');};
    if (strpos("$password","or")){die("请勿输入 or <a href='../zhuce.html'>返回</a>");};
    if (strpos("$password","OR")){die("请勿输入 OR <a href='../zhuce.html'>返回</a>");};
    if (strpos("$password","and")){die("请勿输入 and <a href='../zhuce.html'>返回</a>");};
    if (strpos("$password","AND")){die("请勿输入 AND <a href='../zhuce.html'>返回</a>");};
    if (strpos("$password","null")){die("请勿输入 NULL <a href='../zhuce.html'>返回</a>");};
    if (strpos("$password","NULL")){die("请勿输入 null <a href='../zhuce.html'>返回</a>");};
    if (strpos("$password","/")){die("请勿输入 / <a href='../zhuce.html'>返回</a>");};
    if (strpos("$password","\\")){die("请勿输入 \ <a href='../zhuce.html'>返回</a>");};
    if (strpos("$password"," ")){die("请勿输入 空格 <a href='../zhuce.html'>返回</a>");};
    if (strpos("$password","-- ")){die("请勿输入--  <a href='../zhuce.html'>返回</a>");};
    if (strpos("$password","''")){die("请勿输入\'\'  <a href='../zhuce.html'>返回</a>");};
    if (strpos("$password","'''")){die("请勿输入\'\'  <a href='../zhuce.html'>返回</a>");};
    if (strpos("$password","/*")){die("请勿输入/*  <a href='../zhuce.html'>返回</a>");};
}