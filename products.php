<?php
require_once 'koneksi.php';

// Cek jika admin belum login
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
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

// Hapus produk
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $produk_id = (int)$_GET['delete'];
    
    // Ambil data produk untuk mendapatkan path gambar
    $query = "SELECT gambar_path FROM products WHERE id = ?";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("i", $produk_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $produk = $result->fetch_assoc();
        
        // Hapus produk dari database
        $delete_query = "DELETE FROM products WHERE id = ?";
        $delete_stmt = $koneksi->prepare($delete_query);
        $delete_stmt->bind_param("i", $produk_id);
        
        if ($delete_stmt->execute()) {
            // Hapus gambar jika ada
            if (!empty($produk['gambar_path']) && file_exists($produk['gambar_path'])) {
                unlink($produk['gambar_path']);
            }
            
            $_SESSION['success'] = "Produk berhasil dihapus!";
        } else {
            $_SESSION['error'] = "Gagal menghapus produk!";
        }
    }
    
    header("Location: products.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - Aplikasi Penjualan</title>
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
        
        /* Add product button */
        .btn-add-product {
            background: linear-gradient(135deg, var(--admin-color), var(--admin-secondary));
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            padding: 12px 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(106, 0, 173, 0.3);
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        .btn-add-product i {
            margin-right: 8px;
        }
        
        .btn-add-product:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(106, 0, 173, 0.4);
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
        
        /* Search and filter container */
        .search-filter-container {
            background: white;
            border-radius: 16px;
            padding: 20px;
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
        
        .btn-search {
            background: linear-gradient(135deg, var(--admin-color), var(--admin-secondary));
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            padding: 12px 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(106, 0, 173, 0.3);
        }
        
        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(106, 0, 173, 0.4);
        }
        
        /* Product table */
        .product-table-container {
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
        
        /* Product image */
        .product-thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        /* Stock badges */
        .stock-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .stock-high {
            background-color: var(--success-color);
            color: white;
        }
        
        .stock-medium {
            background-color: var(--warning-color);
            color: white;
        }
        
        .stock-low {
            background-color: var(--danger-color);
            color: white;
        }
        
        /* Action buttons */
        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 3px;
            transition: all 0.3s ease;
        }
        
        .btn-edit {
            background-color: var(--warning-color);
            color: white;
        }
        
        .btn-edit:hover {
            background-color: #e6ac00;
            transform: translateY(-2px);
        }
        
        .btn-delete {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #e04500;
            transform: translateY(-2px);
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
            
            .btn-add-product {
                margin-top: 15px;
                width: 100%;
                justify-content: center;
            }
            
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .product-thumbnail {
                width: 40px;
                height: 40px;
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
                        <a class="nav-link active" href="products.php">Manajemen Produk</a>
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
    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">Manajemen Produk</h1>
                    <p class="page-subtitle">Kelola semua produk toko Anda</p>
                </div>
                <a href="product_form.php" class="btn-add-product">
                    <i class="bi bi-plus-circle"></i> Tambah Produk
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

            <!-- Search and Filter -->
            <div class="search-filter-container">
                <div class="row g-3">
                    <div class="col-md-6">
                        <form method="GET" action="products.php" class="d-flex">
                            <input class="form-control me-2" type="search" name="search" placeholder="Cari produk..." 
                                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <button class="btn btn-search" type="submit">
                                <i class="bi bi-search"></i> Cari
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" action="products.php">
                            <select class="form-select" name="kategori" onchange="this.form.submit()">
                                <option value="">Semua Kategori</option>
                                <?php 
                                // Reset result pointer
                                $kategori_result->data_seek(0);
                                while ($kategori = $kategori_result->fetch_assoc()): ?>
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

            <!-- Product Table -->
            <div class="product-table-container">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Gambar</th>
                                <th>SKU</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Harga Jual</th>
                                <th>Stok</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($produk_result->num_rows > 0): ?>
                                <?php while ($produk = $produk_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($produk['gambar_path'])): ?>
                                                <img src="<?php echo htmlspecialchars($produk['gambar_path']); ?>" 
                                                     class="product-thumbnail" alt="<?php echo htmlspecialchars($produk['nama']); ?>">
                                            <?php else: ?>
                                                <img src="https://via.placeholder.com/60?text=No+Image" 
                                                     class="product-thumbnail" alt="No Image">
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($produk['sku']); ?></td>
                                        <td><?php echo htmlspecialchars($produk['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($produk['kategori_nama']); ?></td>
                                        <td>Rp <?php echo number_format($produk['harga_jual'], 0, ',', '.'); ?></td>
                                        <td>
                                            <span class="stock-badge <?php echo $produk['stok'] > 10 ? 'stock-high' : ($produk['stok'] > 0 ? 'stock-medium' : 'stock-low'); ?>">
                                                <?php echo $produk['stok']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="product_form.php?id=<?php echo $produk['id']; ?>" class="action-btn btn-edit" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="products.php?delete=<?php echo $produk['id']; ?>" 
                                               class="action-btn btn-delete" title="Hapus"
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="empty-state">
                                            <i class="bi bi-inbox"></i>
                                            <p>Tidak ada produk yang ditemukan.</p>
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