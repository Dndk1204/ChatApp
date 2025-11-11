<?php
    $hostname = "localhost";
    $username = "root";
    $password = "";
    $dbname = "chatappsql";

    $conn = new mysqli($hostname, $username, $password, $dbname);

    if ($conn->connect_error) {

        die("Kết nối CSDL thất bại: " . $conn->connect_error);
    }

    mysqli_set_charset($conn, "utf8"); 
?>