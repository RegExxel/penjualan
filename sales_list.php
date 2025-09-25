<?php
require_once 'koneksi.php';

// Cek jika admin belum login
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Ambil data transaksi
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');

$query = "SELECT s.*, c.nama AS customer_nama 
          FROM sales s 
          LEFT JOIN customers c ON s.customer_id = c.id 
          WHERE DATE(s.created_at) BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
          ORDER BY s.created_at DESC";
$result = $koneksi->query($query);

// Export CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=transaksi_' . date('Ymd') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Header CSV
    fputcsv($output, [
        'Invoice', 
        'Pelanggan', 
        'Total Item', 
        'Total Amount', 
        'Metode Pembayaran', 
        'Tanggal'
    ]);
    
    // Data
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['invoice_no'],
            $row['customer_nama'] ? $row['customer_nama'] : 'Umum',
            $row['total_items'],
            $row['total_amount'],
            $row['pembayaran_method'],
            $row['created_at']
        ]);
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Transaksi - Aplikasi Penjualan</title>
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
            margin-top: 80px;
        }
        
        /* Page header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 5px;
            background: linear-gradient(90deg, var(--admin-color), var(--admin-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .page-subtitle {
            color: #6c757d;
            font-size: 1rem;
        }
        
        /* Export button */
        .btn-export {
            background: linear-gradient(135deg, var(--success-color), #00d084);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            padding: 12px 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(6, 255, 165, 0.3);
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        .btn-export i {
            margin-right: 8px;
        }
        
        .btn-export:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(6, 255, 165, 0.4);
            color: white;
        }
        
        /* Alert styling */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
            animation: slideInRight 0.5s ease;
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Date filter container */
        .date-filter-container {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e9ecef;
            padding: 12px 15px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--admin-color);
            box-shadow: 0 0 0 0.25rem rgba(106, 0, 173, 0.15);
        }
        
        .btn-filter {
            background: linear-gradient(135deg, var(--admin-color), var(--admin-secondary));
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            padding: 12px 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(106, 0, 173, 0.3);
            width: 100%;
        }
        
        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(106, 0, 173, 0.4);
        }
        
        /* Transaction table */
        .transaction-table-container {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background-color: #f8f9fa;
            color: var(--dark-color);
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
            padding: 15px;
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(106, 0, 173, 0.05);
        }
        
        .table td, .table th {
            padding: 15px;
            vertical-align: middle;
        }
        
        /* Payment method badges */
        .payment-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-block;
        }
        
        .payment-tunai {
            background-color: var(--success-color);
            color: white;
        }
        
        .payment-kartu {
            background-color: var(--primary-color);
            color: white;
        }
        
        .payment-transfer {
            background-color: var(--warning-color);
            color: white;
        }
        
        .payment-ewallet {
            background-color: var(--accent-color);
            color: white;
        }
        
        /* Detail button */
        .btn-detail {
            background: linear-gradient(135deg, #4cc9f0, #4361ee);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            padding: 8px 15px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(76, 201, 240, 0.3);
            display: inline-flex;
            align-items: center;
            text-decoration: none;
        }
        
        .btn-detail i {
            margin-right: 5px;
        }
        
        .btn-detail:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(76, 201, 240, 0.4);
            color: white;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #dee2e6;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .btn-export {
                margin-top: 15px;
                width: 100%;
                justify-content: center;
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
                        <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Manajemen Produk</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pos.php">Kasir</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="sales_list.php">Daftar Transaksi</a>
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
    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">Daftar Transaksi</h1>
                    <p class="page-subtitle">Kelola semua riwayat transaksi toko Anda</p>
                </div>
                <a href="sales_list.php?export=csv&tanggal_awal=<?php echo $tanggal_awal; ?>&tanggal_akhir=<?php echo $tanggal_akhir; ?>" 
                   class="btn-export">
                    <i class="bi bi-download"></i> Export CSV
                </a>
            </div>

            <!-- Alert -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?php echo $_SESSION['success']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $_SESSION['error']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Date Filter -->
            <div class="date-filter-container">
                <form method="GET" action="sales_list.php" class="row g-3">
                    <div class="col-md-5">
                        <label for="tanggal_awal" class="form-label">Tanggal Awal</label>
                        <input type="date" class="form-control" id="tanggal_awal" name="tanggal_awal" 
                               value="<?php echo $tanggal_awal; ?>">
                    </div>
                    <div class="col-md-5">
                        <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                        <input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir" 
                               value="<?php echo $tanggal_akhir; ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-filter">Filter</button>
                    </div>
                </form>
            </div>

            <!-- Transaction Table -->
            <div class="transaction-table-container">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Pelanggan</th>
                                <th>Total Item</th>
                                <th>Total Amount</th>
                                <th>Metode Pembayaran</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($transaksi = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($transaksi['invoice_no']); ?></strong>
                                        </td>
                                        <td><?php echo $transaksi['customer_nama'] ? htmlspecialchars($transaksi['customer_nama']) : 'Umum'; ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $transaksi['total_items']; ?> item</span>
                                        </td>
                                        <td>
                                            <strong>Rp <?php echo number_format($transaksi['total_amount'], 0, ',', '.'); ?></strong>
                                        </td>
                                        <td>
                                            <?php
                                            $paymentClass = '';
                                            switch(strtolower($transaksi['pembayaran_method'])) {
                                                case 'tunai':
                                                    $paymentClass = 'payment-tunai';
                                                    break;
                                                case 'kartu debit':
                                                case 'kartu kredit':
                                                    $paymentClass = 'payment-kartu';
                                                    break;
                                                case 'transfer bank':
                                                    $paymentClass = 'payment-transfer';
                                                    break;
                                                case 'e-wallet':
                                                    $paymentClass = 'payment-ewallet';
                                                    break;
                                                default:
                                                    $paymentClass = 'payment-tunai';
                                            }
                                            ?>
                                            <span class="payment-badge <?php echo $paymentClass; ?>">
                                                <?php echo htmlspecialchars($transaksi['pembayaran_method']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($transaksi['created_at'])); ?></td>
                                        <td>
                                            <a href="sale_detail.php?id=<?php echo $transaksi['id']; ?>" class="btn-detail">
                                                <i class="bi bi-eye"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="empty-state">
                                            <i class="bi bi-receipt"></i>
                                            <p>Tidak ada transaksi yang ditemukan.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>