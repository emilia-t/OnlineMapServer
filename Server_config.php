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
//地图表名称，请根据双击配置，如果没有我们会创建一个名叫"map_0_data"的数据表来存放地图数据
$mysql_public_sheet_name='map_0_data';
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
/**
 * 服务器属性配置
 */
//服务器基础信息-这些信息将会用于告知客户端
const __SERVER_CONFIG__IMG__ = '';//地图的背景图片位置，图像尺寸为：220px * 165px 最大为60kb 支持PNG和jpg类型的图片格式
const __SERVER_CONFIG__KEY__ = '';//key是唯一的，格式k[a-Z0-9]，是客户端采用https://name.com/ + m/key 的方式快捷访问地图服务器的
const __SERVER_CONFIG__URL__ = '';//您的服务器websocket链接的地址
const __SERVER_CONFIG__NAME__ = '';//您的服务器名称
const __SERVER_CONFIG__MAX_USER__ = '';//服务器最大在线人数
const __SERVER_CONFIG__MAX_HEIGHT__ = '';//最大高度
const __SERVER_CONFIG__MAX_WIDTH__ = '';//最大宽度
const __SERVER_CONFIG__DEFAULT_X__ = '';//默认x
const __SERVER_CONFIG__DEFAULT_Y__ = '';//默认y