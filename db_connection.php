<?php
// 数据库连接配置
$servername = "localhost";
$username = "root";
$password = "Yiqun521521~";
$dbname = "easybird";

// 创建数据库连接
$conn = new mysqli($servername, $username, $password, $dbname);

// 检查连接是否成功
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error);
}
?>    