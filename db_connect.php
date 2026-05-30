<?php
$host = "switchyard.proxy.rlwy.net";
$user = "root";
$pass = "EuOnnXtJYtwQClPayUhJAwklCJmWWXDX";
$dbname = "railway";
$port = 29873;

$conn = mysqli_connect($host, $user, $pass, $dbname, $port);
if(!$conn){
   die("Connection failed: " . mysqli_connect_error());
}
?>
