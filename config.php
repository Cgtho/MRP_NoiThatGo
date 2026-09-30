<?php
declare(strict_types=1);

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'quanlykho_sanxuat';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPassword = getenv('DB_PASSWORD') ?: '';

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";

try {
	$pdo = new PDO($dsn, $dbUser, $dbPassword, [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_EMULATE_PREPARES => false,
	]);
} catch (PDOException $e) {
	http_response_code(500);
	exit('Không thể kết nối cơ sở dữ liệu.');
}
