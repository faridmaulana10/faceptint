<?php
// db.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "monitoring_faceprint";

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
