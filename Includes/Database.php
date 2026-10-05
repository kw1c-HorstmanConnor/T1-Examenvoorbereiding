<?php

const MAPLE_DB_HOST = 'localhost';
const MAPLE_DB_USER = 'root';
const MAPLE_DB_PASSWORD = '';
const MAPLE_DB_NAME = 'camping_maple';

$conn = new mysqli(MAPLE_DB_HOST, MAPLE_DB_USER, MAPLE_DB_PASSWORD, MAPLE_DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
?>
