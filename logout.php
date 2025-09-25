<?php
require_once 'koneksi.php';

// Hapus semua session
session_unset();
session_destroy();

// Redirect ke halaman login
header("Location: admin_login.php");
exit();
?>