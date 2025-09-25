<?php
require_once 'koneksi.php';

// Cek jika admin belum login
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Ambil path gambar
if (!isset($_GET['path']) || empty($_GET['path'])) {
    header("HTTP/1.0 404 Not Found");
    exit();
}

$image_path = $_GET['path'];

// Cek jika file ada
if (!file_exists($image_path)) {
    header("HTTP/1.0 404 Not Found");
    exit();
}

// Ambil informasi file
$image_info = getimagesize($image_path);
if (!$image_info) {
    header("HTTP/1.0 403 Forbidden");
    exit();
}

// Set header
header('Content-Type: ' . $image_info['mime']);
header('Content-Length: ' . filesize($image_path));
header('Cache-Control: public, must-revalidate, max-age=0');
header('Pragma: public');
header('Expires: 0');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

// Output gambar
readfile($image_path);
exit();
?>