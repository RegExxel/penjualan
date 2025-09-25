<?php
require_once 'koneksi.php';

// Cek jika admin belum login
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Ambil ID transaksi
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: sales_list.php");
    exit();
}

$sale_id = (int)$_GET['id'];

// Ambil data transaksi
$query = "SELECT s.*, c.nama AS customer_nama, c.email, c.telepon, c.alamat 
          FROM sales s 
          LEFT JOIN customers c ON s.customer_id = c.id 
          WHERE s.id = ?";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows != 1) {
    $_SESSION['error'] = "Transaksi tidak ditemukan!";
    header("Location: sales_list.php");
    exit();
}

$sale = $result->fetch_assoc();

// Ambil detail item
$items_query = "SELECT si.*, p.nama, p.sku 
                FROM sale_items si 
                JOIN products p ON si.product_id = p.id 
                WHERE si.sale_id = ?";
$items_stmt = $koneksi->prepare($items_query);
$items_stmt->bind_param("i", $sale_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Transaksi - Aplikasi Penjualan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
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
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Detail Transaksi</h2>
                    <a href="sales_list.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>

                <!-- Informasi Transaksi -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Informasi Transaksi</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="150">Nomor Invoice</td>
                                        <td>: <?php echo htmlspecialchars($sale['invoice_no']); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Tanggal</td>
                                        <td>: <?php echo date('d/m/Y H:i', strtotime($sale['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Metode Pembayaran</td>
                                        <td>: <?php echo htmlspecialchars($sale['pembayaran_method']); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="150">Total Item</td>
                                        <td>: <?php echo $sale['total_items']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Total Amount</td>
                                        <td>: Rp <?php echo number_format($sale['total_amount'], 0, ',', '.'); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informasi Pelanggan -->
                <?php if (!empty($sale['customer_nama'])): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Informasi Pelanggan</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="150">Nama</td>
                                            <td>: <?php echo htmlspecialchars($sale['customer_nama']); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Email</td>
                                            <td>: <?php echo htmlspecialchars($sale['email']); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="150">Telepon</td>
                                            <td>: <?php echo htmlspecialchars($sale['telepon']); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Alamat</td>
                                            <td>: <?php echo htmlspecialchars($sale['alamat']); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Detail Item -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Detail Item</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Nama Produk</th>
                                        <th>Harga</th>
                                        <th>Qty</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($item = $items_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                            <td><?php echo htmlspecialchars($item['nama']); ?></td>
                                            <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                            <td><?php echo $item['qty']; ?></td>
                                            <td>Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold">Total</td>
                                        <td class="fw-bold">Rp <?php echo number_format($sale['total_amount'], 0, ',', '.'); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
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