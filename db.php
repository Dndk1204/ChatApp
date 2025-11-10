<?php
    $hostname = "127.0.0.1";
    $username = "root";
    $password = "";
    $dbname = "chatappsql";
    $conn = new mysqli($hostname, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Kết nối thất bại: " . $conn->connect_error);
    }

    mysqli_set_charset($conn, "utf8"); 
?>
