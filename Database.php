<?php

$host = "sql208.infinityfree.com";
$username = "if0_42771213";
$password = "J3OK60jGkKgtYlz";
$database = "if0_42771213_clinic_system";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");