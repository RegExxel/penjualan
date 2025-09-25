<?php
// Konfigurasi database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'penjualan_db');

// Membuat koneksi
$koneksi = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Set charset
$koneksi->set_charset("utf8mb4");

// Start session
session_start();

// Cek jika user sudah login
if (isset($_SESSION['user_id'])) {
    // Ambil data user dari database
    $query = "SELECT * FROM customers WHERE id = ?";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows != 1) {
        // Jika user tidak ditemukan, hapus session
        unset($_SESSION['user_id']);
        unset($_SESSION['user_nama']);
        unset($_SESSION['user_email']);
    }
}

// Cek jika admin sudah login
if (isset($_SESSION['admin_id'])) {
    // Ambil data admin dari database
    $query = "SELECT * FROM admins WHERE id = ?";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows != 1) {
        // Jika admin tidak ditemukan, hapus session
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_nama']);
    }
}
?>