<?php
require_once 'koneksi.php';

// Hapus session user
unset($_SESSION['user_id']);
unset($_SESSION['user_nama']);
unset($_SESSION['user_email']);

// Redirect ke halaman utama
header("Location: index.php");
exit();
?>