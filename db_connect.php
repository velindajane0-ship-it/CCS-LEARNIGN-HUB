<?php
$host = getenv("DB_HOST");
$user = getenv("DB_USER");
$pass = getenv("DB_PASSWORD");
$dbname = getenv("DB_NAME");
$port = getenv("DB_PORT");

$conn = mysqli_connect($host, $user, $pass, $dbname, $port);
if(!$conn){
   die("Connection failed: " . mysqli_connect_error());
}
?>
