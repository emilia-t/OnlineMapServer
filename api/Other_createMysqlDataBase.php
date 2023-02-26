<?php
//测试数据连接
function testDataBaseLink(){
    global $mysql_root_password,$mysql_public_server_address,$mysql_public_user,$mysql_public_password,$mysql_public_db_name;
    //SQL 创建数据库
    define('CreateSqlA',"FLUSH PRIVILEGES;CREATE DATABASE `$mysql_public_db_name` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;FLUSH PRIVILEGES;USE $mysql_public_db_name;SET NAMES utf8mb4;SET FOREIGN_KEY_CHECKS=0;DROP TABLE IF EXISTS `account_data`;CREATE TABLE `account_data` (`user_email` VARCHAR (255) CHARACTER SET utf8 COLLATE utf8_croatian_ci NOT NULL COMMENT '用户电子邮箱',`user_name` VARCHAR (255) CHARACTER SET utf8 COLLATE utf8_croatian_ci NOT NULL COMMENT '用户名',`pass_word` VARCHAR (255) CHARACTER SET utf8 COLLATE utf8_croatian_ci NOT NULL COMMENT '密码',`map_layer` INT (11) NULL DEFAULT 0 COMMENT '默认层级',`default_a1` VARCHAR (255) CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL DEFAULT '{x:0,y:0}' COMMENT '默认的A1坐标',`save_point` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL COMMENT '用户保存的坐标，收藏点',`user_qq` BIGINT (12) NULL DEFAULT 1077365277 COMMENT '用户QQ号，默认为1077365277',PRIMARY KEY (`user_email`) USING BTREE) ENGINE=INNODB CHARACTER SET=utf8 COLLATE=utf8_croatian_ci ROW_FORMAT=Dynamic;DROP TABLE IF EXISTS `map_0_data`;CREATE TABLE `map_0_data` (`id` BIGINT (20) NOT NULL AUTO_INCREMENT COMMENT 'id:number;数字类型;例如:20007',`type` VARCHAR (12) CHARACTER SET utf8 COLLATE utf8_croatian_ci NOT NULL COMMENT '数据类型,共计4种:line,缩写l，point,缩写p，area,缩写a，relation,缩写r',`points` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_croatian_ci NOT NULL COMMENT '数据点；理论上最多存储26886个点，请限制用户最大1000个点',`point` VARCHAR (255) CHARACTER SET utf8 COLLATE utf8_croatian_ci NOT NULL COMMENT '起始坐标点',`color` VARCHAR (255) CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL DEFAULT NULL COMMENT '颜色',`length` INT (11) NULL DEFAULT NULL COMMENT '线的实际长度，单位为m',`width` INT (11) NULL DEFAULT NULL COMMENT '线的显示宽度，单位为px',`size` INT (11) NULL DEFAULT NULL COMMENT '区域的实际面积，单位是m^2',`child_relations` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL COMMENT '此条数据下的子关系id集合',`father_relation` VARCHAR (255) CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL DEFAULT NULL COMMENT '此条数据的上级关系id',`child_nodes` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL COMMENT '对于关系类型的数据类型的成员节点',`father_node` VARCHAR (255) CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL DEFAULT NULL COMMENT '对于关系类型的数据类型的父节点',`details` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL COMMENT '可以拓展的列，例如一些详细描述信息',PRIMARY KEY (`id`) USING BTREE) ENGINE=INNODB CHARACTER SET=utf8 COLLATE=utf8_croatian_ci ROW_FORMAT=Dynamic;DROP TABLE IF EXISTS `map_template_data`;CREATE TABLE `map_template_data` (`id` VARCHAR (12) CHARACTER SET utf8 COLLATE utf8_croatian_ci NOT NULL COMMENT 'id:mini-type+number;字符串类型;例如:l20007',`type` VARCHAR (12) CHARACTER SET utf8 COLLATE utf8_croatian_ci NOT NULL COMMENT '数据类型,共计4种:line,缩写l，point,缩写p，area,缩写a，relation,缩写r',`points` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_croatian_ci NOT NULL COMMENT '数据点；理论上最多存储26886个点，请限制用户最大1000个点',`point` VARCHAR (255) CHARACTER SET utf8 COLLATE utf8_croatian_ci NOT NULL COMMENT '起始坐标点',`color` VARCHAR (255) CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL DEFAULT NULL COMMENT '颜色',`length` INT (11) NULL DEFAULT NULL COMMENT '线的实际长度，单位为m',`width` INT (11) NULL DEFAULT NULL COMMENT '线的显示宽度，单位为px',`size` INT (11) NULL DEFAULT NULL COMMENT '区域的实际面积，单位是m^2',`child_relations` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL COMMENT '此条数据下的子关系id集合',`father_relation` VARCHAR (255) CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL DEFAULT NULL COMMENT '此条数据的上级关系id',`child_nodes` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL COMMENT '对于关系类型的数据类型的成员节点',`father_node` VARCHAR (255) CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL DEFAULT NULL COMMENT '对于关系类型的数据类型的父节点',PRIMARY KEY (`id`) USING BTREE) ENGINE=INNODB CHARACTER SET=utf8 COLLATE=utf8_croatian_ci ROW_FORMAT=Dynamic; SET FOREIGN_KEY_CHECKS=1;FLUSH PRIVILEGES;GRANT SELECT,INSERT,DELETE,UPDATE ON $mysql_public_db_name.* TO '$mysql_public_user'@'$mysql_public_server_address';FLUSH PRIVILEGES;");
    //检测数据库是否创建
    $PbLink=mysqli_connect($mysql_public_server_address, $mysql_public_user, $mysql_public_password);
    //检测公开账号连接
    if(!$PbLink){//无法连接公用账号
        print_r("公用账号连接数据库失败，请检查/config/Mysql_OZ4pTiFHZf.php下的配置\n");
    }else{//能连接->检测数据库是否存在
        print_r("公用账号连接数据库成功\n");
        print_r("检测数据库中：\n");
        mysqli_select_db($PbLink,$mysql_public_db_name);
        $Er=mysqli_error($PbLink);
        $Exp="/Unknown/i";
        if(preg_match($Exp,$Er)){
            //连接数据库失败
            print_r("正在创建地图数据库\n");
            //创建数据库：
            //1关闭上一个连接
            mysqli_close($PbLink);
            //2.新建root连接
            $RootLink=mysqli_connect($mysql_public_server_address, 'root', $mysql_root_password);
            if(!$RootLink){
                print_r("root账号连接失败，请检查/config/Mysql_OZ4pTiFHZf.php下的配置\n");
            }else{
                //3.创建数据库
                $RootLink->multi_query(CreateSqlA);
                //4.结束连接
                mysqli_close($RootLink);
                print_r("已创建数据库{$mysql_public_db_name}......已经为{$mysql_public_user}授予insert,update,delete,select权限......Done\n");
                print_r("请主动检查“{$mysql_public_db_name}”是否已创建，请主动检查“{$mysql_public_user}”权限是否正确\n...All Done\n");
            }
        }else{
            print_r("...All Done\n");
        }
        try {
            mysqli_close($PbLink);
        }catch (Exception $E){

        }
    }
}