<?php
require_once 'koneksi.php';

// Cek jika user belum login
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    header("Location: user_login.php");
    exit();
}

// Ambil produk untuk autocomplete
$produk_query = "SELECT p.*, c.nama AS kategori_nama 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.stok > 0 
                ORDER BY p.nama";
$produk_result = $koneksi->query($produk_query);

// Ambil pelanggan (hanya untuk admin)
$pelanggan_result = null;
if (isset($_SESSION['admin_id'])) {
    $pelanggan_query = "SELECT * FROM customers ORDER BY nama";
    $pelanggan_result = $koneksi->query($pelanggan_query);
}

// Inisialisasi keranjang
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Tambah produk ke keranjang
if (isset($_GET['add']) && !empty($_GET['add'])) {
    $produk_id = (int)$_GET['add'];
    
    // Ambil data produk
    $query = "SELECT * FROM products WHERE id = ? AND stok > 0";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("i", $produk_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $produk = $result->fetch_assoc();
        
        // Cek jika produk sudah ada di keranjang
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $produk_id) {
                // Cek stok
                if ($item['qty'] < $produk['stok']) {
                    $item['qty']++;
                    $found = true;
                    break;
                } else {
                    $_SESSION['error'] = "Stok tidak mencukupi!";
                    break;
                }
            }
        }
        
        // Jika produk belum ada di keranjang
        if (!$found) {
            $_SESSION['cart'][] = [
                'id' => $produk['id'],
                'sku' => $produk['sku'],
                'nama' => $produk['nama'],
                'harga' => $produk['harga_jual'],
                'qty' => 1,
                'stok' => $produk['stok']
            ];
        }
    }
    
    header("Location: pos.php");
    exit();
}

// Hapus item dari keranjang
if (isset($_GET['remove']) && !empty($_GET['remove'])) {
    $index = (int)$_GET['remove'];
    
    if (isset($_SESSION['cart'][$index])) {
        unset($_SESSION['cart'][$index]);
        // Reindex array
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    }
    
    header("Location: pos.php");
    exit();
}

// Update quantity
if (isset($_POST['update_qty']) && isset($_POST['index']) && isset($_POST['qty'])) {
    $index = (int)$_POST['index'];
    $qty = (int)$_POST['qty'];
    
    if (isset($_SESSION['cart'][$index]) && $qty > 0) {
        // Cek stok
        $produk_id = $_SESSION['cart'][$index]['id'];
        $query = "SELECT stok FROM products WHERE id = ?";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("i", $produk_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $produk = $result->fetch_assoc();
        
        if ($qty <= $produk['stok']) {
            $_SESSION['cart'][$index]['qty'] = $qty;
        } else {
            $_SESSION['error'] = "Stok tidak mencukupi!";
        }
    }
    
    header("Location: pos.php");
    exit();
}

// Kosongkan keranjang
if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    header("Location: pos.php");
    exit();
}

// Hitung total
$total = 0;
$total_items = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal = $item['harga'] * $item['qty'];
    $total += $subtotal;
    $total_items += $item['qty'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Aplikasi Penjualan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        /* Custom styles for modern look */
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #f72585;
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
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
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
            color: var(--primary-color) !important;
        }
        
        .navbar-nav .nav-link.active {
            color: var(--primary-color) !important;
        }
        
        .navbar-nav .nav-link:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
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
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 10px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .page-subtitle {
            color: #6c757d;
            font-size: 1rem;
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
        
        /* Product section */
        .product-section {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
        
        .section-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 20px;
            font-weight: 700;
            color: var(--dark-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-body {
            padding: 20px;
        }
        
        /* Search box */
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-box input {
            border-radius: 12px;
            border: 1px solid #e9ecef;
            padding: 12px 20px 12px 50px;
            font-weight: 500;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .search-box input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }
        
        .search-box i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        /* Product table */
        .product-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .product-table thead th {
            background-color: #f8f9fa;
            color: var(--dark-color);
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
            padding: 15px;
            text-align: left;
        }
        
        .product-table tbody tr {
            transition: all 0.2s ease;
        }
        
        .product-table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .product-table td, .product-table th {
            padding: 15px;
            vertical-align: middle;
        }
        
        .btn-add-product {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            padding: 8px 15px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3);
            display: inline-flex;
            align-items: center;
            text-decoration: none;
        }
        
        .btn-add-product i {
            margin-right: 5px;
        }
        
        .btn-add-product:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(67, 97, 238, 0.4);
            color: white;
        }
        
        /* Cart section */
        .cart-section {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 100px;
        }
        
        /* Cart items */
        .cart-items {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .cart-item {
            padding: 15px;
            border-bottom: 1px solid #f1f3f5;
            display: flex;
            align-items: center;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-item-info {
            flex-grow: 1;
        }
        
        .cart-item-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .cart-item-price {
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        .cart-item-qty {
            display: flex;
            align-items: center;
            margin: 0 15px;
        }
        
        .cart-item-qty input {
            width: 60px;
            text-align: center;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 5px;
        }
        
        .cart-item-subtotal {
            font-weight: 700;
            min-width: 100px;
            text-align: right;
        }
        
        .cart-item-remove {
            color: var(--danger-color);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .cart-item-remove:hover {
            transform: scale(1.2);
        }
        
        /* Cart summary */
        .cart-summary {
            padding: 20px;
            border-top: 1px solid #f1f3f5;
        }
        
        .cart-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .cart-total-label {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .cart-total-amount {
            font-weight: 800;
            font-size: 1.5rem;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Checkout form */
        .checkout-form {
            margin-top: 20px;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e9ecef;
            padding: 12px 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }
        
        .btn-checkout {
            background: linear-gradient(135deg, var(--success-color), #00d084);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            padding: 12px 20px;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(6, 255, 165, 0.3);
        }
        
        .btn-checkout:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(6, 255, 165, 0.4);
        }
        
        .btn-clear-cart {
            background: var(--danger-color);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            padding: 6px 12px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }
        
        .btn-clear-cart:hover {
            background-color: #e04500;
            transform: translateY(-2px);
        }
        
        /* Empty cart */
        .empty-cart {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-cart i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #dee2e6;
        }
        
        .btn-shop-now {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            padding: 12px 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
            display: inline-block;
            text-decoration: none;
            margin-top: 15px;
        }
        
        .btn-shop-now:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(67, 97, 238, 0.4);
            color: white;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .cart-section {
                position: static;
                margin-top: 30px;
            }
            
            .cart-items {
                max-height: 300px;
            }
            
            .cart-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .cart-item-qty {
                margin: 10px 0;
            }
            
            .cart-item-subtotal {
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-shop"></i> Aplikasi Penjualan
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="pos.php">Keranjang 
                            <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                                <span class="badge bg-danger"><?php echo count($_SESSION['cart']); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_nama']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="user_logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php elseif (isset($_SESSION['admin_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['admin_nama']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="admin_dashboard.php">Dashboard</a></li>
                                <li><a class="dropdown-item" href="products.php">Manajemen Produk</a></li>
                                <li><a class="dropdown-item" href="sales_list.php">Daftar Transaksi</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Keranjang Belanja</h1>
                <p class="page-subtitle">Kelola produk pembelian Anda</p>
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

            <div class="row">
                <!-- Produk -->
                <div class="col-lg-8 mb-4">
                    <div class="product-section">
                        <div class="section-header">
                            <h5 class="mb-0">Tambah Produk</h5>
                        </div>
                        <div class="section-body">
                            <div class="search-box">
                                <i class="bi bi-search"></i>
                                <input type="text" class="form-control" id="searchProduct" placeholder="Cari produk...">
                            </div>
                            
                            <div class="table-responsive">
                                <table class="product-table">
                                    <thead>
                                        <tr>
                                            <th>SKU</th>
                                            <th>Nama Produk</th>
                                            <th>Harga</th>
                                            <th>Stok</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($produk_result->num_rows > 0): ?>
                                            <?php while ($produk = $produk_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($produk['sku']); ?></td>
                                                    <td><?php echo htmlspecialchars($produk['nama']); ?></td>
                                                    <td>Rp <?php echo number_format($produk['harga_jual'], 0, ',', '.'); ?></td>
                                                    <td><?php echo $produk['stok']; ?></td>
                                                    <td>
                                                        <a href="pos.php?add=<?php echo $produk['id']; ?>" class="btn-add-product">
                                                            <i class="bi bi-plus-circle"></i> Tambah
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">Tidak ada produk yang tersedia.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Keranjang -->
                <div class="col-lg-4 mb-4">
                    <div class="cart-section">
                        <div class="section-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Keranjang Belanja</h5>
                            <?php if (!empty($_SESSION['cart'])): ?>
                                <a href="pos.php?clear=1" class="btn-clear-cart" onclick="return confirm('Kosongkan keranjang?')">
                                    <i class="bi bi-trash"></i> Kosongkan
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="section-body">
                            <?php if (empty($_SESSION['cart'])): ?>
                                <div class="empty-cart">
                                    <i class="bi bi-cart-x"></i>
                                    <p>Keranjang belanja kosong</p>
                                    <a href="index.php" class="btn-shop-now">Belanja Sekarang</a>
                                </div>
                            <?php else: ?>
                                <div class="cart-items">
                                    <?php foreach ($_SESSION['cart'] as $index => $item): ?>
                                        <div class="cart-item">
                                            <div class="cart-item-info">
                                                <div class="cart-item-name"><?php echo htmlspecialchars($item['nama']); ?></div>
                                                <div class="cart-item-price">Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></div>
                                            </div>
                                            <div class="cart-item-qty">
                                                <form method="POST" action="pos.php" style="display: flex;">
                                                    <input type="hidden" name="index" value="<?php echo $index; ?>">
                                                    <input type="number" name="qty" class="form-control form-control-sm" 
                                                           value="<?php echo $item['qty']; ?>" min="1" max="<?php echo $item['stok']; ?>">
                                                    <button type="submit" name="update_qty" class="btn btn-sm btn-outline-primary ms-2">
                                                        <i class="bi bi-arrow-clockwise"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            <div class="cart-item-subtotal">Rp <?php echo number_format($item['harga'] * $item['qty'], 0, ',', '.'); ?></div>
                                            <div class="cart-item-remove">
                                                <a href="pos.php?remove=<?php echo $index; ?>">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="cart-summary">
                                    <div class="cart-total">
                                        <span class="cart-total-label">Total:</span>
                                        <span class="cart-total-amount">Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                                    </div>
                                    
                                    <form method="POST" action="process_sale.php" class="checkout-form">
                                        <?php if (isset($_SESSION['admin_id'])): ?>
                                            <select class="form-select" name="pelanggan">
                                                <option value="">Umum</option>
                                                <?php 
                                                // Reset result pointer
                                                if ($pelanggan_result) {
                                                    $pelanggan_result->data_seek(0);
                                                    while ($pelanggan = $pelanggan_result->fetch_assoc()): ?>
                                                        <option value="<?php echo $pelanggan['id']; ?>">
                                                            <?php echo htmlspecialchars($pelanggan['nama']); ?>
                                                        </option>
                                                    <?php endwhile;
                                                } ?>
                                            </select>
                                        <?php else: ?>
                                            <input type="hidden" name="pelanggan" value="<?php echo $_SESSION['user_id']; ?>">
                                        <?php endif; ?>
                                        
                                        <select class="form-select" name="pembayaran" required>
                                            <option value="">Pilih Metode Pembayaran</option>
                                            <option value="Tunai">Tunai</option>
                                            <option value="Kartu Debit">Kartu Debit</option>
                                            <option value="Kartu Kredit">Kartu Kredit</option>
                                            <option value="Transfer Bank">Transfer Bank</option>
                                            <option value="E-Wallet">E-Wallet</option>
                                        </select>
                                        
                                        <input type="hidden" name="total_amount" value="<?php echo $total; ?>">
                                        <input type="hidden" name="total_items" value="<?php echo $total_items; ?>">
                                        
                                        <button type="submit" class="btn-checkout">
                                            <i class="bi bi-cash-stack me-2"></i> Proses Pembayaran
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        // Search product functionality
        document.getElementById('searchProduct').addEventListener('keyup', function() {
            let searchValue = this.value.toLowerCase();
            let rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                let productName = row.cells[1].textContent.toLowerCase();
                let productSku = row.cells[0].textContent.toLowerCase();
                
                if (productName.includes(searchValue) || productSku.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>