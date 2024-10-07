<?php
function getCreateMapDatabaseSql($mysql_public_db_name){
return <<<ETXGX
FLUSH PRIVILEGES;
CREATE DATABASE `$mysql_public_db_name` DEFAULT CHARACTER
SET utf8 COLLATE utf8_general_ci; FLUSH PRIVILEGES;
USE $mysql_public_db_name;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `account_data`;
CREATE TABLE `account_data` (
`user_email` VARCHAR (255) CHARACTER SET utf8 COLLATE utf8_croatian_ci NOT NULL DEFAULT 'unknown' COMMENT '用户电子邮箱',
`user_name` VARCHAR (255) CHARACTER SET utf8 COLLATE utf8_croatian_ci NOT NULL DEFAULT 'unknown' COMMENT '用户名',
`pass_word` VARCHAR (255) CHARACTER SET utf8 COLLATE utf8_croatian_ci NOT NULL DEFAULT 'unknown' COMMENT '密码',
`map_layer` INT (11) NULL DEFAULT 0 COMMENT '默认层级',
`default_a1` VARCHAR (255) CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL DEFAULT '{x:0,y:0}' COMMENT '默认的A1坐标',
`save_point` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL COMMENT '用户保存的坐标，收藏点',
`user_qq` VARCHAR (255) CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL DEFAULT '100006' COMMENT '用户QQ号',
`head_color` VARCHAR (6) CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL DEFAULT 'ffffff' COMMENT '头像颜色16进制的',
PRIMARY KEY (`user_email`) USING BTREE
) 
ENGINE=INNODB CHARACTER
SET=utf8 COLLATE=utf8_croatian_ci ROW_FORMAT=Dynamic;
DROP TABLE IF EXISTS `map_0_data`;
CREATE TABLE `map_0_data` (
`id` BIGINT (20) NOT NULL AUTO_INCREMENT COMMENT 'id',
`type` VARCHAR (12) CHARACTER SET utf8 COLLATE utf8_croatian_ci NOT NULL COMMENT '数据类型',
`points` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_croatian_ci NOT NULL COMMENT '节点',
`point` VARCHAR (255) CHARACTER SET utf8 COLLATE utf8_croatian_ci NOT NULL COMMENT '起始坐标点',
`color` VARCHAR (255) CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL DEFAULT NULL COMMENT '颜色',
`phase` INT (1) NOT NULL DEFAULT 1 COMMENT '元素周期,默认为1',
`width` INT (11) NULL DEFAULT NULL COMMENT '点线的显示宽度，单位为px',
`child_relations` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL COMMENT '此条数据下的子关系id集合',
`father_relation` VARCHAR (255) CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL DEFAULT NULL COMMENT '此条数据的上级关系id',
`child_nodes` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL COMMENT '对于关系类型的数据类型的成员节点',
`father_node` VARCHAR (255) CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL DEFAULT NULL COMMENT '对于关系类型的数据类型的父节点',
`details` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL COMMENT '可以拓展的列，例如一些详细描述信息',
`custom` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_croatian_ci NULL COMMENT '可以拓展的元素配置，例如一些特殊图标',
PRIMARY KEY (`id`) USING BTREE
) 
ENGINE=INNODB CHARACTER
SET=utf8 COLLATE=utf8_croatian_ci ROW_FORMAT=Dynamic;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `map_0_layer`;
CREATE TABLE `map_0_layer` (
`id` BIGINT (20) NOT NULL AUTO_INCREMENT,
`type` VARCHAR (255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
`members` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
`structure` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
`phase` int(1) NOT NULL DEFAULT 1,
PRIMARY KEY (`id`) USING BTREE
) 
ENGINE=INNODB AUTO_INCREMENT=29 CHARACTER
SET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=Dynamic;
SET FOREIGN_KEY_CHECKS=1;
SET FOREIGN_KEY_CHECKS=1;
INSERT INTO `map_0_layer` VALUES (1,'order','[2]','',1);
INSERT INTO `map_0_layer` VALUES (2,'group','{0:0}','["default layer",{"template":{"id":"defaultlayerid","locked":false,"modify":"2020-01-01T00:00:00","name":"template","explain":"none","creator":"root(root@localhost)","typeRule":{"point":true,"line":true,"area":true,"curve":true},"detailsRule":[{"default":"☍tunknown","name":"name","set":false,"type":"text"}],"colorRule":{"basis":"","type":"","condition":[]},"widthRule":{"basis":"","type":"","condition":[]}}}]',1);
FLUSH PRIVILEGES;
ETXGX;
}

function getGrantPublicAccountPermissionsSql($mysql_public_db_name,$mysql_public_user,$mysql_public_server_hostname){
return <<<ETXGX
FLUSH PRIVILEGES;
GRANT SELECT,INSERT,DELETE,
UPDATE ON $mysql_public_db_name.*TO '$mysql_public_user'@'$mysql_public_server_hostname';
FLUSH PRIVILEGES;
ETXGX;
}

function getCreateAccountDataTableSqlite(){
return <<<ETXGX
CREATE TABLE account_data (
user_email varchar(255) NOT NULL,
user_name varchar(255) NOT NULL,
pass_word varchar(255) NOT NULL,
map_layer int(11),
default_a1 varchar(255),
save_point TEXT,
user_qq varchar(255),
head_color varchar(6),
PRIMARY KEY (user_email)
)
ETXGX;
}

function getCreateMap0DataTableSqlite(){
return <<<ETXGX
CREATE TABLE map_0_data (
id INTEGER PRIMARY KEY AUTOINCREMENT,
type varchar(12) NOT NULL,
points TEXT NOT NULL,
point varchar(255) NOT NULL,
color varchar(255) DEFAULT NULL,
phase int(1) NOT NULL,
width int(11) DEFAULT NULL,
child_relations TEXT,
father_relation varchar(255) DEFAULT NULL,
child_nodes TEXT,
father_node varchar(255) DEFAULT NULL,
details TEXT,
custom TEXT
)
ETXGX;
}

function getCreateMap0LayerTableSqlite(){
return <<<ETXGX
CREATE TABLE map_0_layer (
id INTEGER PRIMARY KEY AUTOINCREMENT,
type varchar(255) NOT NULL,
members TEXT NOT NULL,
structure TEXT NOT NULL,
phase int(1) NOT NULL
)
ETXGX;
}

function getInsertDefaultAccountSql(){
return <<<ETXGX
INSERT INTO account_data(
user_email, user_name, pass_word, map_layer, default_a1, save_point, user_qq, head_color
) VALUES (
'administrators@a.com', 'administrators', 'administrators', 0, '{x:0,y:0}', NULL, '1000001', '3399ff'
)
ETXGX;
}

function getInsertDefaultOrderSql(){
return <<<ETXGX
INSERT INTO map_0_layer(
id, type, members, structure, phase
) VALUES (
1, 'order', '[2]', '', 1
)
ETXGX;
}

function getInsertDefaultGroupSql(){
return <<<ETXGX
INSERT INTO map_0_layer(
id, type, members, structure, phase
) VALUES (
2, 'group', '[0]', '["default layer",{"template":{"id":"defaultlayerid","locked":false,"modify":"2020-01-01T00:00:00","name":"template","explain":"none","creator":"root(root@localhost)","typeRule":{"point":true,"line":true,"area":true,"curve":true},"detailsRule":[{"default":"☍tunknown","name":"name","set":false,"type":"text"}],"colorRule":{"basis":"","type":"","condition":[]},"widthRule":{"basis":"","type":"","condition":[]}}}]', 1
)
ETXGX;
}