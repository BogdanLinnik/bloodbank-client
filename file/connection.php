<?php
$host = getenv('DB_HOST');
$username = getenv('DB_USER');
$password = getenv('DB_PASSWORD');
$dbname = getenv('DB_NAME');

$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, '/etc/ssl/certs/ca-certificates.crt', NULL, NULL);

$conn->real_connect($host, $username, $password, $dbname, 3306);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
?>
