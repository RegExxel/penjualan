<?php
require_once 'koneksi.php';

// Cek jika admin belum login
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Ambil kategori
$kategori_query = "SELECT * FROM categories ORDER BY nama";
$kategori_result = $koneksi->query($kategori_query);

// Mode edit atau tambah
$edit_mode = false;
$produk = [];

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $edit_mode = true;
    $produk_id = (int)$_GET['id'];
    
    // Ambil data produk
    $query = "SELECT * FROM products WHERE id = ?";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("i", $produk_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $produk = $result->fetch_assoc();
    } else {
        $_SESSION['error'] = "Produk tidak ditemukan!";
        header("Location: products.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Edit' : 'Tambah'; ?> Produk - Aplikasi Penjualan</title>
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
            display: flex;
            justify-content: center;
        }
        
        /* Form container */
        .form-container {
            width: 100%;
            max-width: 900px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: fadeInUp 0.8s ease;
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
        
        /* Form header */
        .form-header {
            background: linear-gradient(135deg, var(--admin-color), var(--admin-secondary));
            color: white;
            padding: 25px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .form-header:before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            transform: rotate(45deg);
        }
        
        .form-title {
            font-size: 1.8rem;
            font-weight: 800;
            margin: 0;
            position: relative;
            z-index: 1;
        }
        
        .form-subtitle {
            font-weight: 500;
            font-size: 0.95rem;
            margin: 8px 0 0;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        /* Form body */
        .form-body {
            padding: 30px;
        }
        
        /* Form sections */
        .form-section {
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid #f1f3f5;
        }
        
        .form-section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--admin-color);
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
            font-size: 1.4rem;
        }
        
        /* Form elements */
        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
        }
        
        .required {
            color: var(--danger-color);
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
        
        .input-group {
            margin-bottom: 15px;
        }
        
        .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-right: none;
            color: var(--admin-color);
            border-radius: 10px 0 0 10px;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        
        /* Image preview */
        .image-preview-container {
            margin-top: 15px;
            text-align: center;
        }
        
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            object-fit: cover;
        }
        
        .image-hint {
            margin-top: 10px;
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        /* Form actions */
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .btn-back {
            background: #e9ecef;
            border: none;
            border-radius: 10px;
            color: var(--dark-color);
            font-weight: 600;
            padding: 12px 20px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        .btn-back i {
            margin-right: 8px;
        }
        
        .btn-back:hover {
            background: #dee2e6;
            transform: translateY(-2px);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--admin-color), var(--admin-secondary));
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            padding: 12px 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(106, 0, 173, 0.3);
            display: flex;
            align-items: center;
        }
        
        .btn-submit i {
            margin-right: 8px;
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(106, 0, 173, 0.4);
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
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .form-container {
                margin: 0 15px;
            }
            
            .form-body {
                padding: 20px;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 15px;
            }
            
            .btn-back, .btn-submit {
                width: 100%;
                justify-content: center;
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
        <div class="form-container">
            <div class="form-header">
                <h2 class="form-title"><?php echo $edit_mode ? 'Edit' : 'Tambah'; ?> Produk</h2>
                <p class="form-subtitle"><?php echo $edit_mode ? 'Perbarui informasi produk yang ada' : 'Tambahkan produk baru ke katalog'; ?></p>
            </div>
            <div class="form-body">
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

                <form method="POST" action="process_product.php" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $edit_mode ? $produk['id'] : ''; ?>">
                    
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="bi bi-info-circle"></i> Informasi Dasar
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="sku" class="form-label">SKU <span class="required">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                                    <input type="text" class="form-control" id="sku" name="sku" required
                                           value="<?php echo $edit_mode ? htmlspecialchars($produk['sku']) : ''; ?>"
                                           placeholder="Contoh: PRD-001">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="nama" class="form-label">Nama Produk <span class="required">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-tag"></i></span>
                                    <input type="text" class="form-control" id="nama" name="nama" required
                                           value="<?php echo $edit_mode ? htmlspecialchars($produk['nama']) : ''; ?>"
                                           placeholder="Nama produk">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="kategori" class="form-label">Kategori</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-folder"></i></span>
                                <select class="form-select" id="kategori" name="kategori">
                                    <option value="">Pilih Kategori</option>
                                    <?php while ($kategori = $kategori_result->fetch_assoc()): ?>
                                        <option value="<?php echo $kategori['id']; ?>" 
                                                <?php echo ($edit_mode && $produk['category_id'] == $kategori['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($kategori['nama']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" 
                                          placeholder="Deskripsi produk"><?php echo $edit_mode ? htmlspecialchars($produk['deskripsi']) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="bi bi-currency-dollar"></i> Harga & Stok
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="harga_jual" class="form-label">Harga Jual <span class="required">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="harga_jual" name="harga_jual" required
                                           value="<?php echo $edit_mode ? $produk['harga_jual'] : ''; ?>"
                                           placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="harga_beli" class="form-label">Harga Beli</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="harga_beli" name="harga_beli"
                                           value="<?php echo $edit_mode ? $produk['harga_beli'] : ''; ?>"
                                           placeholder="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="stok" class="form-label">Stok <span class="required">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-box-seam"></i></span>
                                    <input type="number" class="form-control" id="stok" name="stok" required
                                           value="<?php echo $edit_mode ? $produk['stok'] : '0'; ?>"
                                           placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="gambar" class="form-label">Gambar Produk</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-image"></i></span>
                                    <input type="file" class="form-control" id="gambar" name="gambar" accept="image/jpeg, image/png">
                                </div>
                                <div class="image-hint">Format: JPG/PNG, Max: 2MB</div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($edit_mode && !empty($produk['gambar_path'])): ?>
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="bi bi-image"></i> Gambar Saat Ini
                            </h3>
                            <div class="image-preview-container">
                                <img src="<?php echo htmlspecialchars($produk['gambar_path']); ?>" 
                                     class="image-preview" alt="Product Image">
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-actions">
                        <a href="products.php" class="btn-back">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                        <button type="submit" class="btn-submit">
                            <i class="bi bi-save"></i> <?php echo $edit_mode ? 'Update' : 'Simpan'; ?> Produk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>