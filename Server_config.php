<?php
/**这是一个示例配置文件
 * 服务器地址配置：
 **/
const __SERVER_IP_PORT__='192.168.1.1:9998';//服务器ip和port
const __LANGUAGE__='chinese';//服务器语言 chinese || english
/**
 * 数据库配置：
 **/
const __DATABASE_NAME__="mysql";//使用的数据库名称 sqlite || mysql(default)
//sqlite初始化管理员账户名称：administrators@a.com，账户密码：administrators

/*↓↓↓以下配置仅在__DATABASE_NAME__=mysql时有用↓↓↓*/
$mysql_public_server_address="localhost";//docker版请输入mysql容器名一般为mysql
$mysql_public_server_hostname="localhost";//docker版请输入%
//公用账号，如果没有账号，请手动创建一个公用账号，然后将账号和密码分别填入下方
$mysql_public_user="name";
$mysql_public_password="password";
//地图数据库名称，请根据实际配置，如果没有地图数据库我们会尝试以这个名称创建地图数据库
$mysql_public_db_name="map";
//地图元素表名称，请根据实际配置，如果没有我们会尝试创建这个数据表来存放元素数据
$mysql_public_sheet_name='map_0_data';
//底图图层表名称，请根据实际配置，如果没有我们会尝试创建这个数据表来存放图层数据
$mysql_public_layer_name='map_0_layer';
//mysql 的 root账号，如果是第一次运行需要这个mysql root 账户来初始化地图数据库
$mysql_root_password="password";
/*↑↑↑以上配置仅在__DATABASE_NAME__=mysql时有用↑↑↑*/
/**
 * SSL配置：
 **/
//是否启用ssl，如果启用了还需要填证书的文件地址
const __SERVER_SSL_STA__ = false;
//证书文件地址，请写入文件的全局地址
const __SERVER_SSL_CRT__ = '';
const __SERVER_SSL_KEY__ = '';
/**
 * 服务器属性配置：
 **/
const __ANONYMOUS_LOGIN__ = false;//是否启用匿名登录功能
const __SERVER_CONFIG__IMG__ = './config/map_img.png';//地图的背景图片位置，图像尺寸为：220px * 165px 最大为60kb 支持PNG和jpg类型的图片格式
const __SERVER_CONFIG__KEY__ = 'k0';//key是唯一的长度不限，格式k[a-Z0-9]，是客户端采用https://name.com/ + m/key 的方式快捷访问地图服务器的
const __SERVER_CONFIG__URL__ = 'ws://192.168.1.1:9998';//您的服务器websocket链接的地址
const __SERVER_CONFIG__NAME__ = '地图名称';//您的服务器名称
const __SERVER_CONFIG__MAX_USER__ = '20';//服务器最大在线人数
const __SERVER_CONFIG__DEFAULT_X__ = '0';//默认中心的经度x
const __SERVER_CONFIG__DEFAULT_Y__ = '0';//默认中心的纬度y
//底图配置，下面的参数可以按照默认的设置
const __SERVER_CONFIG__ENABLE_BASE_MAP__ = true;//表示是否启用额外的底图服务
const __SERVER_CONFIG__BASE_MAP_TYPE__ = 'realistic';//realistic || fictitious
const __SERVER_CONFIG__BASE_MAP_URL__ = '';//底图服务的默认底图URL，可以为空
/**
 * RSA配置：
 **/
//RSA公钥(不想要自己生成可以直接用默认的)
const RSA_public = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCr2zOuj+3yEh79CisMrDthvWv9
wu8aI0OYrcW0NX9zqqmcMpRupn7AenK2BlHBUrylB5sj4J47Z0QRumdFhRAjNhKH
oflXeSyLxauTPGj7w6sK9iSGjF9ipHkRcCQbSkPDMHnd5BkyW4F25/ADQLvXY41O
eOY8XSxmSFFhWYAXbwIDAQAB
-----END PUBLIC KEY-----';
//RSA私钥
const RSA_private = '-----BEGIN PRIVATE KEY-----
MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBAKvbM66P7fISHv0K
KwysO2G9a/3C7xojQ5itxbQ1f3OqqZwylG6mfsB6crYGUcFSvKUHmyPgnjtnRBG6
Z0WFECM2Eoeh+Vd5LIvFq5M8aPvDqwr2JIaMX2KkeRFwJBtKQ8Mwed3kGTJbgXbn
8ANAu9djjU545jxdLGZIUWFZgBdvAgMBAAECgYBi86DrZvYjxqlPG1a0QksiuPWA
NIiFrT5Tn+LRI2iSSfbE+B6dI4KiAx8fjb3vKVtzTlDWtJOHMqtv5btmvPoPyo1j
b9IaJd/C6Ry3Mtoql+0kV+u2Fa9rMux2J+KVe9jqETeJEj3+OUAHSQVxtduRH8rd
wzqQ30Hg0qJPnKkwmQJBANZlPqFDGHwoFDWWw50yM5hd/zycn15iBopj0AZXzAsZ
wMGLkmUqpK1mppMKmtWGsiiaI/GddY5eTNo9IOOuKJMCQQDNNLCtQaIqgXyjYn6G
8BTLpyomtvN7wMVzXx7vEVe0Ij4Cx9OpfQfzAL0ZJKURKpwZibkagwEZNWTw4G0N
eys1AkEA1V5HeWh0CsQ8cKTNozld/eq2ZNUfCmiWR85ULqvcBsQngLduB77ryyLY
7ofkVlNKJXxZ/1EMuJaC98NUYyNlfwJBAJtCzJKqYDps8pLkSPtr1zAncNsN/bea
qUqbo9oacxNV/Tk5XEqW0VbpLipB8arFZIpmC+mlSUV7gr5F7/0NPikCQQC8bjGd
Mu4WfW+ydKDaEqX83Y8a6u4J2dPmTPN2fYvYIHkFXFjwbaVwr2TBQJUCGHpleIX0
ccTNg51xKPp2jZdq
-----END PRIVATE KEY-----';