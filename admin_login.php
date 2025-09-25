<?php
require_once 'koneksi.php';

// Jika admin sudah login, redirect ke dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

// Proses login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $koneksi->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    // Query untuk mencari admin berdasarkan email
    $query = "SELECT * FROM admins WHERE email = ?";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $admin = $result->fetch_assoc();
        
        // Verifikasi password
        if (password_verify($password, $admin['password_hash'])) {
            // Set session
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_nama'] = $admin['nama'];
            
            // Redirect ke dashboard
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Email tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Aplikasi Penjualan</title>
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
            --admin-color: #6a0dad;
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
        
        /* Admin login container */
        .admin-login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }
        
        /* Admin login card */
        .admin-login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.18);
            overflow: hidden;
            animation: fadeInUp 0.8s ease;
            position: relative;
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
        
        /* Admin badge */
        .admin-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, var(--admin-color), #8a2be2);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 1px;
            box-shadow: 0 4px 10px rgba(106, 0, 173, 0.3);
            z-index: 10;
        }
        
        /* Card header */
        .card-header-custom {
            background: linear-gradient(135deg, var(--admin-color), #8a2be2);
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-bottom: none;
            position: relative;
            overflow: hidden;
        }
        
        /* Background pattern */
        .card-header-custom:before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            transform: rotate(45deg);
        }
        
        .admin-icon {
            font-size: 3.5rem;
            margin-bottom: 15px;
            animation: pulse 2s infinite;
            position: relative;
            z-index: 1;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .admin-title {
            font-weight: 800;
            font-size: 1.8rem;
            margin: 0;
            position: relative;
            z-index: 1;
        }
        
        .admin-subtitle {
            font-weight: 500;
            font-size: 0.95rem;
            margin: 10px 0 0;
            opacity: 0.9;
            position: relative;
            z-index: 1;
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
        
        .input-group {
            margin-bottom: 20px;
        }
        
        .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-right: none;
            color: var(--admin-color);
            border-radius: 12px 0 0 12px;
        }
        
        .form-control {
            border: 1px solid #e9ecef;
            border-left: none;
            border-radius: 0 12px 12px 0;
            padding: 12px 15px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--admin-color);
            box-shadow: 0 0 0 0.25rem rgba(106, 0, 173, 0.15);
        }
        
        /* Login button */
        .btn-admin-login {
            background: linear-gradient(135deg, var(--admin-color), #8a2be2);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            padding: 12px;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(106, 0, 173, 0.3);
            margin-top: 10px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-admin-login:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-admin-login:hover:before {
            left: 100%;
        }
        
        .btn-admin-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(106, 0, 173, 0.4);
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
        .admin-login-links {
            text-align: center;
            margin-top: 25px;
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
            color: var(--admin-color);
        }
        
        .back-link i {
            margin-right: 5px;
        }
        
        /* Security features badge */
        .security-features {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .security-feature {
            text-align: center;
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .security-feature i {
            font-size: 1.5rem;
            color: var(--admin-color);
            margin-bottom: 5px;
            display: block;
        }
        
        /* Floating particles */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        
        .particle {
            position: absolute;
            background-color: rgba(106, 0, 173, 0.1);
            border-radius: 50%;
        }
        
        /* Responsive adjustments */
        @media (max-width: 576px) {
            .admin-login-container {
                padding: 15px;
            }
            
            .card-header-custom {
                padding: 25px 15px;
            }
            
            .card-body-custom {
                padding: 25px 20px;
            }
            
            .security-features {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="card admin-login-card">
            <div class="admin-badge">ADMIN</div>
            <div class="card-header-custom">
                <div class="particles">
                    <div class="particle" style="width: 10px; height: 10px; top: 20%; left: 20%;"></div>
                    <div class="particle" style="width: 15px; height: 15px; top: 60%; left: 80%;"></div>
                    <div class="particle" style="width: 8px; height: 8px; top: 30%; left: 70%;"></div>
                    <div class="particle" style="width: 12px; height: 12px; top: 80%; left: 30%;"></div>
                </div>
                <div class="admin-icon">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <h3 class="admin-title">Admin Login</h3>
                <p class="admin-subtitle">Masuk ke Dashboard Admin</p>
            </div>
            <div class="card-body-custom">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="admin@example.com" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-admin-login">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Login
                        </button>
                    </div>
                </form>
                
                <div class="security-features">
                    <div class="security-feature">
                        <i class="bi bi-shield-check"></i>
                        <span>Aman</span>
                    </div>
                    <div class="security-feature">
                        <i class="bi bi-fingerprint"></i>
                        <span>Terlindungi</span>
                    </div>
                    <div class="security-feature">
                        <i class="bi bi-lock-fill"></i>
                        <span>Enkripsi</span>
                    </div>
                </div>
                
                <div class="admin-login-links">
                    <a href="index.php" class="back-link">
                        <i class="bi bi-arrow-left"></i> Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animate particles
        document.addEventListener('DOMContentLoaded', function() {
            const particles = document.querySelectorAll('.particle');
            
            particles.forEach(particle => {
                // Random movement
                const moveParticle = () => {
                    const size = parseFloat(particle.style.width);
                    const posX = parseFloat(particle.style.left);
                    const posY = parseFloat(particle.style.top);
                    
                    // Random direction
                    const dirX = (Math.random() - 0.5) * 2;
                    const dirY = (Math.random() - 0.5) * 2;
                    
                    // Apply movement
                    particle.style.left = `${Math.max(0, Math.min(100, posX + dirX))}%`;
                    particle.style.top = `${Math.max(0, Math.min(100, posY + dirY))}%`;
                };
                
                // Move particle every 2 seconds
                setInterval(moveParticle, 2000);
            });
        });
    </script>
</body>
</html>