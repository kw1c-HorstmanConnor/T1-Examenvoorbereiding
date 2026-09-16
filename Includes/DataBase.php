<?php

require_once __DIR__ . '/../Functions/Helpers/DatabaseConfig.php';

$databaseConfig = maple_database_config();
$conn = new mysqli(
    $databaseConfig['host'],
    $databaseConfig['username'],
    $databaseConfig['password'],
    $databaseConfig['database']
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset($databaseConfig['charset']);
