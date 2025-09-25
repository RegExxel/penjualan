<?php
require_once 'koneksi.php';

// Cek jika admin belum login
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Ambil data untuk dashboard
// Total penjualan hari ini
$tanggal_hari_ini = date('Y-m-d');
$query_penjualan_hari_ini = "SELECT COUNT(*) AS total_transaksi, SUM(total_amount) AS total_pendapatan 
                            FROM sales 
                            WHERE DATE(created_at) = '$tanggal_hari_ini'";
$result_penjualan_hari_ini = $koneksi->query($query_penjualan_hari_ini);
$data_penjualan_hari_ini = $result_penjualan_hari_ini->fetch_assoc();

// Produk dengan stok rendah (stok <= 5)
$query_stok_rendah = "SELECT COUNT(*) AS total FROM products WHERE stok <= 5";
$result_stok_rendah = $koneksi->query($query_stok_rendah);
$data_stok_rendah = $result_stok_rendah->fetch_assoc();

// Produk habis (stok = 0)
$query_stok_habis = "SELECT COUNT(*) AS total FROM products WHERE stok = 0";
$result_stok_habis = $koneksi->query($query_stok_habis);
$data_stok_habis = $result_stok_habis->fetch_assoc();

// Total produk
$query_total_produk = "SELECT COUNT(*) AS total FROM products";
$result_total_produk = $koneksi->query($query_total_produk);
$data_total_produk = $result_total_produk->fetch_assoc();

// Transaksi terakhir
$query_transaksi_terakhir = "SELECT s.*, c.nama AS customer_nama 
                            FROM sales s 
                            LEFT JOIN customers c ON s.customer_id = c.id 
                            ORDER BY s.created_at DESC 
                            LIMIT 5";
$result_transaksi_terakhir = $koneksi->query($query_transaksi_terakhir);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Aplikasi Penjualan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        /* Custom styles for modern look */
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #f72585;
            --admin-color: #6a0dad;
            --admin-secondary: #8a2be2;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #06ffa5;
            --warning-color: #ffbe0b;
            --danger-color: #fb5607;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7ff 0%, #eef2ff 100%);
            background-size: 200% 200%;
            animation: gradientBG 15s ease infinite;
            color: var(--dark-color);
            min-height: 100vh;
        }
        
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Navbar styling */
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.18);
            padding-top: 15px;
            padding-bottom: 15px;
        }
        
        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            background: linear-gradient(90deg, var(--admin-color), var(--admin-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .navbar-nav .nav-link {
            font-weight: 500;
            color: var(--dark-color) !important;
            margin: 0 10px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .navbar-nav .nav-link:hover {
            color: var(--admin-color) !important;
        }
        
        .navbar-nav .nav-link.active {
            color: var(--admin-color) !important;
        }
        
        .navbar-nav .nav-link:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--admin-color);
            transition: width 0.3s ease;
        }
        
        .navbar-nav .nav-link:hover:after,
        .navbar-nav .nav-link.active:after {
            width: 100%;
        }
        
        /* Main content */
        .main-content {
            padding: 30px 20px;
        }
        
        /* Welcome section */
        .welcome-section {
            margin-bottom: 30px;
        }
        
        .welcome-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 10px;
            background: linear-gradient(90deg, var(--admin-color), var(--admin-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .welcome-subtitle {
            color: #6c757d;
            font-size: 1.1rem;
        }
        
        /* Stats cards */
        .stats-card {
            border: none;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .stats-card-primary {
            background: linear-gradient(135deg, var(--admin-color), var(--admin-secondary));
            color: white;
        }
        
        .stats-card-success {
            background: linear-gradient(135deg, var(--success-color), #00d084);
            color: white;
        }
        
        .stats-card-warning {
            background: linear-gradient(135deg, var(--warning-color), #fb8500);
            color: white;
        }
        
        .stats-card-info {
            background: linear-gradient(135deg, #4cc9f0, #4361ee);
            color: white;
        }
        
        .stats-card-body {
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .stats-content h4 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stats-content p {
            margin: 0;
            opacity: 0.9;
            font-weight: 500;
        }
        
        .stats-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        /* Card styling */
        .custom-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            height: 100%;
        }
        
        .custom-card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 15px 20px;
            font-weight: 700;
            color: var(--dark-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .custom-card-body {
            padding: 20px;
        }
        
        /* Table styling */
        .table-custom {
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table-custom thead th {
            background-color: #f8f9fa;
            color: var(--dark-color);
            font-weight: 600;
            border-top: none;
            border-bottom: 2px solid #dee2e6;
        }
        
        .table-custom tbody tr {
            transition: all 0.2s ease;
        }
        
        .table-custom tbody tr:hover {
            background-color: rgba(106, 0, 173, 0.05);
        }
        
        .table-custom td, .table-custom th {
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        /* Quick actions */
        .quick-action-btn {
            border-radius: 12px;
            font-weight: 600;
            padding: 12px 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: white;
        }
        
        .quick-action-btn i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .quick-action-btn-primary {
            background: linear-gradient(135deg, var(--admin-color), var(--admin-secondary));
            box-shadow: 0 4px 15px rgba(106, 0, 173, 0.3);
        }
        
        .quick-action-btn-success {
            background: linear-gradient(135deg, var(--success-color), #00d084);
            box-shadow: 0 4px 15px rgba(6, 255, 165, 0.3);
        }
        
        .quick-action-btn-info {
            background: linear-gradient(135deg, #4cc9f0, #4361ee);
            box-shadow: 0 4px 15px rgba(76, 201, 240, 0.3);
        }
        
        .quick-action-btn-warning {
            background: linear-gradient(135deg, var(--warning-color), #fb8500);
            box-shadow: 0 4px 15px rgba(255, 190, 11, 0.3);
        }
        
        .quick-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #dee2e6;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .welcome-title {
                font-size: 1.5rem;
            }
            
            .stats-content h4 {
                font-size: 1.5rem;
            }
            
            .stats-icon {
                font-size: 2rem;
            }
            
            .table-responsive {
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="bi bi-speedometer2"></i> Admin Dashboard
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Manajemen Produk</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pos.php">Kasir</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="sales_list.php">Daftar Transaksi</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['admin_nama']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content" style="margin-top: 80px;">
        <div class="container-fluid">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1 class="welcome-title">Dashboard Admin</h1>
                <p class="welcome-subtitle">Selamat datang, <?php echo htmlspecialchars($_SESSION['admin_nama']); ?>! Berikut ringkasan aktivitas toko Anda hari ini.</p>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card stats-card-primary">
                        <div class="stats-card-body">
                            <div class="stats-content">
                                <h4><?php echo $data_penjualan_hari_ini['total_transaksi']; ?></h4>
                                <p>Transaksi Hari Ini</p>
                            </div>
                            <div class="stats-icon">
                                <i class="bi bi-cart-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card stats-card-success">
                        <div class="stats-card-body">
                            <div class="stats-content">
                                <h4>Rp <?php echo number_format($data_penjualan_hari_ini['total_pendapatan'], 0, ',', '.'); ?></h4>
                                <p>Pendapatan Hari Ini</p>
                            </div>
                            <div class="stats-icon">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card stats-card-warning">
                        <div class="stats-card-body">
                            <div class="stats-content">
                                <h4><?php echo $data_stok_rendah['total']; ?></h4>
                                <p>Produk Stok Rendah</p>
                            </div>
                            <div class="stats-icon">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card stats-card-info">
                        <div class="stats-card-body">
                            <div class="stats-content">
                                <h4><?php echo $data_total_produk['total']; ?></h4>
                                <p>Total Produk</p>
                            </div>
                            <div class="stats-icon">
                                <i class="bi bi-box-seam"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Transaksi Terakhir -->
                <div class="col-lg-8 mb-4">
                    <div class="custom-card">
                        <div class="custom-card-header">
                            <h5 class="mb-0">Transaksi Terakhir</h5>
                            <a href="sales_list.php" class="btn btn-sm" style="background: linear-gradient(135deg, var(--admin-color), var(--admin-secondary)); color: white; border-radius: 8px;">
                                Lihat Semua
                            </a>
                        </div>
                        <div class="custom-card-body">
                            <?php if ($result_transaksi_terakhir->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-custom">
                                        <thead>
                                            <tr>
                                                <th>Invoice</th>
                                                <th>Pelanggan</th>
                                                <th>Total</th>
                                                <th>Metode Pembayaran</th>
                                                <th>Tanggal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($transaksi = $result_transaksi_terakhir->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($transaksi['invoice_no']); ?></td>
                                                    <td><?php echo $transaksi['customer_nama'] ? htmlspecialchars($transaksi['customer_nama']) : '-'; ?></td>
                                                    <td>Rp <?php echo number_format($transaksi['total_amount'], 0, ',', '.'); ?></td>
                                                    <td><?php echo htmlspecialchars($transaksi['pembayaran_method']); ?></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($transaksi['created_at'])); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <p>Belum ada transaksi.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="col-lg-4 mb-4">
                    <div class="custom-card">
                        <div class="custom-card-header">
                            <h5 class="mb-0">Aksi Cepat</h5>
                        </div>
                        <div class="custom-card-body">
                            <a href="product_form.php" class="quick-action-btn quick-action-btn-primary">
                                <i class="bi bi-plus-circle"></i> Tambah Produk Baru
                            </a>
                            <a href="pos.php" class="quick-action-btn quick-action-btn-success">
                                <i class="bi bi-cash"></i> Transaksi Baru
                            </a>
                            <a href="products.php" class="quick-action-btn quick-action-btn-info">
                                <i class="bi bi-box-seam"></i> Kelola Produk
                            </a>
                            <a href="sales_list.php" class="quick-action-btn quick-action-btn-warning">
                                <i class="bi bi-receipt"></i> Lihat Transaksi
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>