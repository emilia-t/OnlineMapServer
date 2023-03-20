<?php
/**这是一个实例配置文件
 * 服务器地址配置：
 **/
const __SERVER_IP_PORT__='0.0.0.0:9998';
/**
 * 数据库账号配置：
 **/
$mysql_public_server_address="localhost";
//公用账号，如果没有账号，请手动创建一个公用账号，然后将账号和密码分别填入下方
$mysql_public_user="name";
$mysql_public_password="password";
//地图数据库名称，请根据实际配置如果没有地图数据库我们会创建下面名字的地图数据库
$mysql_public_db_name="map";
//root账号，您可以在确保地图数据库正常且完整运行一次AmapService.php后改为空字符串
$mysql_root_password="password";
/**
 * RSA配置：
 **/
//贴入您的RSA公钥
const RSA_public = '';
//贴入您的RSA私钥
const RSA_private = '';
/**
 * SMTP邮箱配置：
 **/
//贴入您的SMTP-IMAP code
const LicenseCodeIMAP = '';
//贴入您的SMTP-POP3 code
const LicenseCodePOP3 = '';
/**
 * SSL配置：
 **/
//证书文件地址，请写入文件的全局地址
const __SERVER_SSL_CRT__ = '';
const __SERVER_SSL_KEY__ = '';
//下方表示是否启用ssl
const __SERVER_SSL_STA__ = false;