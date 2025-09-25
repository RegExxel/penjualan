<?php
require_once 'koneksi.php';

// Jika user sudah login, redirect ke index
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Proses registrasi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $koneksi->real_escape_string($_POST['nama']);
    $email = $koneksi->real_escape_string($_POST['email']);
    $telepon = $koneksi->real_escape_string($_POST['telepon']);
    $alamat = $koneksi->real_escape_string($_POST['alamat']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi password
    if ($password != $confirm_password) {
        $error = "Password dan konfirmasi password tidak cocok!";
    } else {
        // Cek email sudah terdaftar atau belum
        $query = "SELECT id FROM customers WHERE email = ?";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email sudah terdaftar!";
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert data customer
            $insert_query = "INSERT INTO customers (nama, email, telepon, alamat, password_hash, is_registered) 
                            VALUES (?, ?, ?, ?, ?, 1)";
            $insert_stmt = $koneksi->prepare($insert_query);
            $insert_stmt->bind_param("sssss", $nama, $email, $telepon, $alamat, $password_hash);
            
            if ($insert_stmt->execute()) {
                $_SESSION['success'] = "Registrasi berhasil! Silakan login.";
                header("Location: user_login.php");
                exit();
            } else {
                $error = "Registrasi gagal!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Pelanggan - Aplikasi Penjualan</title>
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
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(120deg, #f5f7ff 50%, #eef2ff 50%);
            background-size: 200% 200%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark-color);
        }
        
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Register container */
        .register-container {
            width: 100%;
            max-width: 550px;
            padding: 20px;
        }
        
        /* Register card */
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.18);
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
        
        /* Card header */
        .card-header-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-bottom: none;
        }
        
        .register-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .register-title {
            font-weight: 800;
            font-size: 1.8rem;
            margin: 0;
        }
        
        .register-subtitle {
            font-weight: 500;
            font-size: 0.95rem;
            margin: 10px 0 0;
            opacity: 0.9;
        }
        
        /* Card body */
        .card-body-custom {
            padding: 30px;
        }
        
        /* Form elements */
        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 12px 15px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }
        
        .form-row {
            margin-bottom: 15px;
        }
        
        /* Required field indicator */
        .required {
            color: var(--accent-color);
        }
        
        /* Register button */
        .btn-register {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            padding: 12px;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
            margin-top: 10px;
        }
        
        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(67, 97, 238, 0.4);
        }
        
        /* Alert styling */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px;
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
        
        /* Links */
        .register-links {
            text-align: center;
            margin-top: 25px;
        }
        
        .register-links p {
            margin-bottom: 10px;
            color: #6c757d;
        }
        
        .register-links a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .register-links a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            color: #6c757d;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            color: var(--primary-color);
        }
        
        .back-link i {
            margin-right: 5px;
        }
        
        /* Form progress indicator */
        .form-progress {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-progress:before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #e9ecef;
            z-index: 1;
        }
        
        .progress-step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.85rem;
            position: relative;
            z-index: 2;
        }
        
        .progress-step.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .progress-label {
            position: absolute;
            top: 35px;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        /* Responsive adjustments */
        @media (max-width: 576px) {
            .register-container {
                padding: 15px;
            }
            
            .card-header-custom {
                padding: 25px 15px;
            }
            
            .card-body-custom {
                padding: 25px 20px;
            }
            
            .form-progress {
                margin-bottom: 20px;
            }
            
            .progress-label {
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="card register-card">
            <div class="card-header-custom">
                <div class="register-icon">
                    <i class="bi bi-person-plus"></i>
                </div>
                <h3 class="register-title">Registrasi Pelanggan</h3>
                <p class="register-subtitle">Buat akun baru Anda</p>
            </div>
            <div class="card-body-custom">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="form-progress">
                    <div class="progress-step active">
                        1
                        <span class="progress-label">Data Pribadi</span>
                    </div>
                    <div class="progress-step">
                        2
                        <span class="progress-label">Kontak</span>
                    </div>
                    <div class="progress-step">
                        3
                        <span class="progress-label">Keamanan</span>
                    </div>
                </div>
                
                <form method="POST" action="" id="registerForm">
                    <div class="form-row">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nama" class="form-label">Nama Lengkap <span class="required">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="nama" name="nama" placeholder="John Doe" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email <span class="required">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="nama@email.com" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="telepon" class="form-label">Telepon</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="text" class="form-control" id="telepon" name="telepon" placeholder="08123456789">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="alamat" class="form-label">Alamat</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                    <input type="text" class="form-control" id="alamat" name="alamat" placeholder="Jl. Contoh No. 123">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password <span class="required">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 6 karakter" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Konfirmasi Password <span class="required">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Ulangi password" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-register">
                            <i class="bi bi-person-check me-2"></i> Daftar Sekarang
                        </button>
                    </div>
                </form>
                
                <div class="register-links">
                    <p>Sudah punya akun? <a href="user_login.php">Login</a></p>
                    <a href="index.php" class="back-link">
                        <i class="bi bi-arrow-left"></i> Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form progress animation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const steps = document.querySelectorAll('.progress-step');
            const inputs = form.querySelectorAll('input[required]');
            
            function updateProgress() {
                let completedSteps = 0;
                
                // Check if personal info is filled
                if (document.getElementById('nama').value && document.getElementById('email').value) {
                    steps[0].classList.add('active');
                    completedSteps++;
                } else {
                    steps[0].classList.remove('active');
                }
                
                // Check if contact info is filled
                if (document.getElementById('telepon').value || document.getElementById('alamat').value) {
                    steps[1].classList.add('active');
                    completedSteps++;
                } else {
                    steps[1].classList.remove('active');
                }
                
                // Check if security info is filled
                if (document.getElementById('password').value && document.getElementById('confirm_password').value) {
                    steps[2].classList.add('active');
                    completedSteps++;
                } else {
                    steps[2].classList.remove('active');
                }
            }
            
            // Add event listeners to all inputs
            inputs.forEach(input => {
                input.addEventListener('input', updateProgress);
            });
            
            // Initial check
            updateProgress();
        });
    </script>
</body>
</html>