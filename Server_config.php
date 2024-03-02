<?php
/**这是一个示例配置文件
 * 服务器地址配置：
 **/
const __SERVER_IP_PORT__='192.168.1.1:9998';
/**
 * 数据库配置：
 **/
$mysql_public_server_address="localhost";//docker版请输入mysql容器名一般为mysql
$mysql_public_server_hostname="localhost";//docker版请输入%
//公用账号，如果没有账号，请手动创建一个公用账号，然后将账号和密码分别填入下方
$mysql_public_user="name";
$mysql_public_password="password";
//地图数据库名称，请根据实际配置如果没有地图数据库我们会创建下面名字的地图数据库
$mysql_public_db_name="map";
//地图表名称，请根据双击配置，如果没有我们会创建一个名叫"map_0_data"的数据表来存放地图数据
$mysql_public_sheet_name='map_0_data';
//图层数据名称，如果没有我们会创建下面名字的数据表来存放地图数据
$mysql_public_layer_name='map_0_layer';
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
 * SSL配置：
 **/
//证书文件地址，请写入文件的全局地址
const __SERVER_SSL_CRT__ = '';
const __SERVER_SSL_KEY__ = '';
//下方表示是否启用ssl
const __SERVER_SSL_STA__ = false;
/**
 * 服务器属性配置
 **/
//服务器特殊功能
const __ANONYMOUS_LOGIN__ = false;//是否启用匿名登录功能
//服务器基础信息
const __SERVER_CONFIG__IMG__ = './config/map_img.png';//地图的背景图片位置，图像尺寸为：220px * 165px 最大为60kb 支持PNG和jpg类型的图片格式
const __SERVER_CONFIG__KEY__ = 'k0';//key是唯一的长度不限，格式k[a-Z0-9]，是客户端采用https://name.com/ + m/key 的方式快捷访问地图服务器的
const __SERVER_CONFIG__URL__ = 'ws://192.168.1.1:9998';//您的服务器websocket链接的地址
const __SERVER_CONFIG__NAME__ = '地图名称';//您的服务器名称
const __SERVER_CONFIG__MAX_USER__ = '20';//服务器最大在线人数
//以下四个常量规定了地图在什么区域内进行绘制
const __SERVER_CONFIG__MAX_HEIGHT__ = '10000';//最大高度
const __SERVER_CONFIG__MIN_HEIGHT__ = '-10000';//最小高度
const __SERVER_CONFIG__MAX_WIDTH__ = '10000';//最大宽度
const __SERVER_CONFIG__MIN_WIDTH__ = '-10000';//最小宽度
//以下两个常量决定了区域内横轴和纵轴的最小层级下（layer0）每像素移动量单位
const __SERVER_CONFIG__UNIT1_Y__ = '1';//纵轴单位1
const __SERVER_CONFIG__UNIT1_X__ = '1';//横轴单位1
//打开地图时的默认中心点
const __SERVER_CONFIG__P0_X__ = '0';//默认x
const __SERVER_CONFIG__P0_Y__ = '0';//默认y
//层级为地图数据的缩放层级，可限制用户在高层级编辑地图导致严重误差
const __SERVER_CONFIG__MAX_LAYER__ = '5';//最大层级
const __SERVER_CONFIG__MIN_LAYER__ = '0';//最小层级
const __SERVER_CONFIG__DEFAULT_LAYER__ = '0';//默认层级
const __SERVER_CONFIG__ZOOM_ADD__ = '1';//默认缩放比例
//底图配置
//缩放比为带有底图瓦图服务器的地图服务，可限制用户对底图缩放
const __SERVER_CONFIG__ENABLE_BASE_MAP__ = false;//表示是否启用额外的底图服务
const __SERVER_CONFIG__DEFAULT_X__ = '0';//默认x
const __SERVER_CONFIG__DEFAULT_Y__ = '0';//默认y
const __SERVER_CONFIG__RESOLUTION_X__ = '1920';//屏幕默认分辨率
const __SERVER_CONFIG__RESOLUTION_Y__ = '980';//屏幕默认分辨率
const __SERVER_CONFIG__MAX_ZOOM__ = '0';//最大底图缩放等级
const __SERVER_CONFIG__MIN_ZOOM__ = '0';//最小底图缩放等级
const __SERVER_CONFIG__DEFAULT_ZOOM__ = '0';//默认底图缩放等级
const __SERVER_CONFIG__BASE_MAP_URL__ = '';//底图服务的服务器URL