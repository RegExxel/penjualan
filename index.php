<?php
require_once 'koneksi.php';

// Cek jika admin sudah login
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

// Ambil kategori untuk filter
$kategori_query = "SELECT * FROM categories ORDER BY nama";
$kategori_result = $koneksi->query($kategori_query);

// Ambil produk untuk ditampilkan
$produk_query = "SELECT p.*, c.nama AS kategori_nama 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                ORDER BY p.nama";
$produk_result = $koneksi->query($produk_query);

// Filter berdasarkan kategori
if (isset($_GET['kategori']) && !empty($_GET['kategori'])) {
    $kategori_id = (int)$_GET['kategori'];
    $produk_query = "SELECT p.*, c.nama AS kategori_nama 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.category_id = $kategori_id 
                    ORDER BY p.nama";
    $produk_result = $koneksi->query($produk_query);
}

// Pencarian produk
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $koneksi->real_escape_string($_GET['search']);
    $produk_query = "SELECT p.*, c.nama AS kategori_nama 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.nama LIKE '%$search%' OR p.sku LIKE '%$search%' 
                    ORDER BY p.nama";
    $produk_result = $koneksi->query($produk_query);
}

// Proses tambah ke keranjang
if (isset($_GET['add']) && !empty($_GET['add'])) {
    // Cek jika user sudah login
    if (!isset($_SESSION['user_id'])) {
        // Simpan URL untuk redirect setelah login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: user_login.php");
        exit();
    }
    
    $produk_id = (int)$_GET['add'];
    
    // Ambil data produk
    $query = "SELECT * FROM products WHERE id = ? AND stok > 0";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("i", $produk_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $produk = $result->fetch_assoc();
        
        // Inisialisasi keranjang jika belum ada
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Penjualan Barang</title>
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
            background-color: #f5f7ff;
            color: var(--dark-color);
            overflow-x: hidden;
        }
        
        /* Animated background */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(120deg, #f5f7ff 50%, #eef2ff 50%);
            background-size: 200% 200%;
            animation: gradientBG 15s ease infinite;
        }
        
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Navbar styling */
        .navbar {
            background: rgba(255, 255, 255, 0.9) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.18);
            transition: all 0.3s ease;
        }
        
        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
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
        
        .navbar-nav .nav-link:hover:after {
            width: 100%;
        }
        
        /* Hero section */
        .hero-section {
            padding: 60px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 20px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: fadeInUp 1s ease;
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
            color: #6c757d;
            max-width: 700px;
            margin: 0 auto;
            animation: fadeInUp 1s ease 0.2s;
            animation-fill-mode: both;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Search and filter styling */
        .search-container {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 40px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: fadeInUp 1s ease 0.4s;
            animation-fill-mode: both;
        }
        
        .form-control, .form-select {
            border-radius: 12px;
            border: 1px solid #e9ecef;
            padding: 12px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }
        
        .btn-search {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            padding: 12px 25px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-search:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(67, 97, 238, 0.4);
        }
        
        /* Product card styling */
        .product-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            height: 100%;
            background: white;
            position: relative;
            animation: fadeInUp 1s ease;
            animation-fill-mode: both;
        }
        
        .product-card:nth-child(1) { animation-delay: 0.1s; }
        .product-card:nth-child(2) { animation-delay: 0.2s; }
        .product-card:nth-child(3) { animation-delay: 0.3s; }
        .product-card:nth-child(4) { animation-delay: 0.4s; }
        .product-card:nth-child(5) { animation-delay: 0.5s; }
        .product-card:nth-child(6) { animation-delay: 0.6s; }
        
        .product-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .product-image-container {
            position: relative;
            overflow: hidden;
            height: 220px;
        }
        
        .product-image {
            height: 100%;
            width: 100%;
            object-fit: cover;
            transition: transform 0.8s ease;
        }
        
        .product-card:hover .product-image {
            transform: scale(1.1);
        }
        
        .product-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            border-radius: 30px;
            padding: 6px 15px;
            font-weight: 600;
            font-size: 0.8rem;
            z-index: 1;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .product-badge.stock-high {
            background-color: var(--success-color);
            color: white;
        }
        
        .product-badge.stock-medium {
            background-color: var(--warning-color);
            color: white;
        }
        
        .product-badge.stock-low {
            background-color: var(--danger-color);
            color: white;
        }
        
        .product-body {
            padding: 20px;
        }
        
        .product-category {
            color: #6c757d;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        
        .product-title {
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: var(--dark-color);
            height: 50px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .product-description {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 15px;
            height: 60px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .product-footer {
            padding: 0 20px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .product-price {
            font-weight: 800;
            font-size: 1.3rem;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .product-card .btn {
            border-radius: 30px;
            font-weight: 600;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        
        .product-card .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }
        
        .product-card .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(67, 97, 238, 0.4);
        }
        
        .product-card .btn-secondary {
            background-color: #e9ecef;
            border: none;
            color: #6c757d;
        }
        
        /* Alert styling */
        .alert {
            border-radius: 15px;
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
        
        /* Footer styling */
        footer {
            background: var(--dark-color);
            color: white;
            margin-top: 80px;
            padding: 40px 0 20px;
            position: relative;
        }
        
        footer:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }
        
        /* Empty state styling */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            animation: fadeInUp 1s ease;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        
        .empty-state-text {
            color: #6c757d;
            font-size: 1.2rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
            
            .product-card {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="animated-bg"></div>
    
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
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="pos.php">Keranjang 
                                <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                                    <span class="badge bg-danger"><?php echo count($_SESSION['cart']); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>
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
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="user_login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="user_register.php">Daftar</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_login.php">Admin</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container" style="margin-top: 80px;">
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

        <!-- Hero Section -->
        <div class="hero-section">
            <h1 class="hero-title">Katalog Produk</h1>
            <p class="hero-subtitle">Temukan berbagai produk berkualitas dengan harga terbaik</p>
        </div>

        <!-- Filter dan Pencarian -->
        <div class="search-container">
            <div class="row g-3">
                <div class="col-md-6">
                    <form method="GET" action="index.php" class="d-flex">
                        <input class="form-control me-2" type="search" name="search" placeholder="Cari produk..." 
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button class="btn btn-search" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                </div>
                <div class="col-md-6">
                    <form method="GET" action="index.php">
                        <select class="form-select" name="kategori" onchange="this.form.submit()">
                            <option value="">Semua Kategori</option>
                            <?php while ($kategori = $kategori_result->fetch_assoc()): ?>
                                <option value="<?php echo $kategori['id']; ?>" 
                                        <?php echo (isset($_GET['kategori']) && $_GET['kategori'] == $kategori['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($kategori['nama']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </form>
                </div>
            </div>
        </div>

        <!-- Daftar Produk -->
        <div class="row">
            <?php if ($produk_result->num_rows > 0): ?>
                <?php while ($produk = $produk_result->fetch_assoc()): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="product-card">
                            <div class="product-image-container">
                                <?php if (!empty($produk['gambar_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($produk['gambar_path']); ?>" 
                                         class="product-image" alt="<?php echo htmlspecialchars($produk['nama']); ?>">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/300x200?text=No+Image" 
                                         class="product-image" alt="No Image">
                                <?php endif; ?>
                                <span class="product-badge <?php echo $produk['stok'] > 10 ? 'stock-high' : ($produk['stok'] > 0 ? 'stock-medium' : 'stock-low'); ?>">
                                    Stok: <?php echo $produk['stok']; ?>
                                </span>
                            </div>
                            <div class="product-body">
                                <div class="product-category"><?php echo htmlspecialchars($produk['kategori_nama']); ?></div>
                                <h5 class="product-title"><?php echo htmlspecialchars($produk['nama']); ?></h5>
                                <p class="product-description"><?php echo htmlspecialchars(substr($produk['deskripsi'], 0, 100)) . '...'; ?></p>
                            </div>
                            <div class="product-footer">
                                <span class="product-price">Rp <?php echo number_format($produk['harga_jual'], 0, ',', '.'); ?></span>
                                <?php if ($produk['stok'] > 0): ?>
                                    <a href="index.php?add=<?php echo $produk['id']; ?>" class="btn btn-primary">
                                        <i class="bi bi-cart-plus"></i>
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled>
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="bi bi-inbox"></i>
                        </div>
                        <div class="empty-state-text">Tidak ada produk yang ditemukan.</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Aplikasi Penjualan Barang. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>