<?php

$host = "sql307.infinityfree.com";
$user = "if0_41897365";
$pass = "uSnexuhxEh1GPH";
$db   = "if0_41897365_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}