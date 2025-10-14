<?php
declare(strict_types=1);


$host = getenv('MYSQL_HOST') ?: 'db';
$port = getenv('MYSQL_PORT') ?: '3306';
$db = 'fussdb';
$user = getenv('MYSQL_USER') ?: 'root';
$pass = getenv('MYSQL_PASSWORD') ?: '';


$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";


try {
$pdo = new PDO($dsn, $user, $pass, [
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
} catch (PDOException $e) {
http_response_code(500);
echo 'Database connection failed.';
error_log('DB connect: '.$e->getMessage());
exit;
}


function db(): PDO { global $pdo; return $pdo; }