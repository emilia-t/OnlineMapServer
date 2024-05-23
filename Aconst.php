<?php
function getCreateMapDatabaseSql($mysql_public_db_name){
    return <<<ETXG
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
INSERT INTO `map_0_layer` VALUES (2,'group','WzBd','WyJkZWZhdWx0IGxheWVyIix7InRlbXBsYXRlIjp7ImlkIjoiZGVmYXVsdGxheWVyaWQiLCJsb2NrZWQiOmZhbHNlLCJtb2RpZnkiOiIyMDIwLTAxLTAxVDAwOjAwOjAwIiwibmFtZSI6InRlbXBsYXRlIiwiZXhwbGFpbiI6Im5vbmUiLCJjcmVhdG9yIjoicm9vdChyb290QGxvY2FsaG9zdCkiLCJ0eXBlUnVsZSI6eyJwb2ludCI6dHJ1ZSwibGluZSI6dHJ1ZSwiYXJlYSI6dHJ1ZSwiY3VydmUiOnRydWV9LCJkZXRhaWxzUnVsZSI6W3siZGVmYXVsdCI6Ilx1MjYwZHR1bmtub3duIiwibmFtZSI6Im5hbWUiLCJzZXQiOmZhbHNlLCJ0eXBlIjoidGV4dCJ9XSwiY29sb3JSdWxlIjp7ImJhc2lzIjoiIiwidHlwZSI6IiIsImNvbmRpdGlvbiI6W119LCJ3aWR0aFJ1bGUiOnsiYmFzaXMiOiIiLCJ0eXBlIjoiIiwiY29uZGl0aW9uIjpbXX19fV0=',1);
FLUSH PRIVILEGES;
ETXG;
}

function getGrantPublicAccountPermissionsSql($mysql_public_db_name,$mysql_public_user,$mysql_public_server_hostname){
    return <<<ETXG
FLUSH PRIVILEGES;
GRANT SELECT,INSERT,DELETE,
UPDATE ON $mysql_public_db_name.*TO '$mysql_public_user'@'$mysql_public_server_hostname';
FLUSH PRIVILEGES;
ETXG;
}
