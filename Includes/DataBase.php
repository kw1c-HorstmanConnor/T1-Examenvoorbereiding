<?php

$conn = new mysqli("localhost", "root", "", "camping_maple");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>