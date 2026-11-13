<?php

require_once 'config.php';

try {
    $dsn = "mysql:host={$mysql_server};dbname={$mysql_db};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $db = new PDO($dsn, $mysql_user, $mysql_pass, $options);
} catch (PDOException $e) {
    die("Error connecting to database!");
}
