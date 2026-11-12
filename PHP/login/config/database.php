<?php
// PHP/login/config/database.php
function getDB()
{
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $dbname = 'LibraryMS';
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}