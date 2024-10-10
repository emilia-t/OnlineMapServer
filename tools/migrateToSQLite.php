<?php
/**
 * This script is used to migrate from MySQL database to SQLite
 * prerequisite
 * 1.Installed pdo_mysql and pdo_sqlite extensions.
 * 2.You have access to the MySQL database.
 **/

// MySQL 配置
$mysql_host = 'localhost';
$mysql_user = 'root'; // MySQL 用户名
echo "请输入mysql root密码：";
//$mysql_pass = ""; //  MySQL 密码
$mysql_pass = trim(fgets(STDIN)); //  MySQL 密码
$mysql_db = 'map'; //  MySQL 数据库名称

// SQLite 配置
$sqlite_db = './SQLite/data.sqlite'; // SQLite 数据库文件路径

try {
    // 连接 MySQL 数据库
    $mysql = new PDO("mysql:host=$mysql_host;dbname=$mysql_db;charset=utf8", $mysql_user, $mysql_pass);
    $mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 连接或创建 SQLite 数据库
    $sqlite = new PDO("sqlite:$sqlite_db");
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 获取 MySQL 中的所有表
    $tables = $mysql->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        echo "\n正在处理表: $table\n";

        // 获取 MySQL 表的创建语句
        $createTableStmt = $mysql->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_ASSOC)['Create Table'];
        print_r("\n");
        print_r("\n");
        print_r("原始mysql创建表sql语句：\n");
        print_r($createTableStmt);
        print_r("\n");
        print_r("\n");
        // 去除 MySQL 特有的排序规则和引擎定义
        $createTableStmt = preg_replace('/ENGINE=InnoDB/i', '', $createTableStmt);
        $createTableStmt = preg_replace('/AUTO_INCREMENT=\d+ /i', '', $createTableStmt);
        $createTableStmt = preg_replace('/AUTO_INCREMENT/i', '', $createTableStmt);  // 另一种形式的 AUTO_INCREMENT
        $createTableStmt = preg_replace('/COLLATE\s+\w+/i', '', $createTableStmt);  // 移除 COLLATE 排序规则
        $createTableStmt = preg_replace('/COMMENT\s+\'[^\']*\'/i', '', $createTableStmt);  // 移除 COMMENT
        $createTableStmt = preg_replace('/USING\s+\w+/i', '', $createTableStmt);  // 移除 USING 部分
        $createTableStmt = preg_replace('/DEFAULT CHARSET=\w+/i', '', $createTableStmt);  // 移除 DEFAULT CHARSET
        $createTableStmt = preg_replace('/ROW_FORMAT=DYNAMIC/i', '', $createTableStmt);  // 移除 ROW_FORMAT
        $createTableStmt = preg_replace('/DEFAULT\s+\'[^\']*\'/i', '', $createTableStmt);  // 移除字符串 DEFAULT 值
        $createTableStmt = preg_replace('/DEFAULT\s+CURRENT_TIMESTAMP/i', '', $createTableStmt);  // 移除 CURRENT_TIMESTAMP 默认值
        $createTableStmt = preg_replace('/COLLATE=\w+/i', '', $createTableStmt);
        $createTableStmt = preg_replace('/mediumtext/i', 'text', $createTableStmt); // 替换 mediumtext 为 TEXT
        $createTableStmt = preg_replace('/bigint\(20\)/i', 'integer', $createTableStmt);// 改bigint为INTEGER，INTEGER可以自增
        $createTableStmt = str_replace('`', '', $createTableStmt); // 去除反引号

        print_r("\n");
        print_r("\n");
        print_r("处理过后的创建表sql语句：\n");
        print_r($createTableStmt);
        print_r("\n");
        print_r("\n");
        // 在 SQLite 中创建表
        $sqlite->exec($createTableStmt);

        // 获取 MySQL 表中的所有数据
        $rows = $mysql->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) > 0) {
            // 获取列名
            $columns = array_keys($rows[0]);
            $columnsList = implode(', ', $columns);
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));

            // 准备插入语句
            $insertStmt = $sqlite->prepare("INSERT INTO $table ($columnsList) VALUES ($placeholders)");

            // 将数据插入 SQLite
            foreach ($rows as $row) {
                $insertStmt->execute(array_values($row));
            }

            echo "已成功插入 " . count($rows) . " 行数据到表: $table\n";
        }
    }

    echo "数据库转换完成！";

} catch (PDOException $e) {
    echo "数据库操作失败: " . $e->getMessage();
}
