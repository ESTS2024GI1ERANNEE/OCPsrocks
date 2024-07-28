<?php
    $servername = "localhost";
    $username = "root";
    $password = '';
    $dbname = 'gestion_stocks';
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die('error'. $conn->connect_error);
    }
?>
