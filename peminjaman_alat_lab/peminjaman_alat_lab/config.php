<?php
// Konfigurasi koneksi database
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', ''); // Kosongkan jika default XAMPP
define('DB_NAME', 'lab_peminjaman');

// Membuat koneksi ke database
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Cek koneksi
if($mysqli === false){
    die("ERROR: Could not connect. " . $mysqli->connect_error);
}

// Memulai session
session_start();
?>