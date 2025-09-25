<?php
require_once 'koneksi.php';

// Cek jika admin belum login
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Cek jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $sku = $koneksi->real_escape_string($_POST['sku']);
    $nama = $koneksi->real_escape_string($_POST['nama']);
    $kategori_id = !empty($_POST['kategori']) ? (int)$_POST['kategori'] : null;
    $deskripsi = $koneksi->real_escape_string($_POST['deskripsi']);
    $harga_jual = (float)$_POST['harga_jual'];
    $harga_beli = !empty($_POST['harga_beli']) ? (float)$_POST['harga_beli'] : null;
    $stok = (int)$_POST['stok'];
    
    // Cek mode edit atau tambah
    $edit_mode = ($id > 0);
    
    // Validasi SKU unik
    $sku_check_query = "SELECT id FROM products WHERE sku = ?";
    $sku_check_stmt = $koneksi->prepare($sku_check_query);
    $sku_check_stmt->bind_param("s", $sku);
    $sku_check_stmt->execute();
    $sku_check_result = $sku_check_stmt->get_result();
    
    if ($sku_check_result->num_rows > 0 && (!$edit_mode || $sku_check_result->fetch_assoc()['id'] != $id)) {
        $_SESSION['error'] = "SKU sudah digunakan oleh produk lain!";
        header("Location: " . ($edit_mode ? "product_form.php?id=$id" : "product_form.php"));
        exit();
    }
    
    // Proses upload gambar
    $gambar_path = "";
    $upload_error = "";
    
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        // Cek tipe file
        if (!in_array($_FILES['gambar']['type'], $allowed_types)) {
            $upload_error = "Hanya file JPG/PNG yang diperbolehkan!";
        }
        
        // Cek ukuran file
        if ($_FILES['gambar']['size'] > $max_size) {
            $upload_error = "Ukuran file maksimal 2MB!";
        }
        
        // Jika tidak ada error, upload file
        if (empty($upload_error)) {
            // Buat folder uploads jika belum ada
            $upload_dir = 'assets/uploads/products/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate nama file unik
            $file_ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid() . '.' . $file_ext;
            $gambar_path = $upload_dir . $file_name;
            
            // Upload file
            if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $gambar_path)) {
                $upload_error = "Gagal mengupload gambar!";
                $gambar_path = "";
            }
        }
    }
    
    // Jika ada error upload
    if (!empty($upload_error)) {
        $_SESSION['error'] = $upload_error;
        header("Location: " . ($edit_mode ? "product_form.php?id=$id" : "product_form.php"));
        exit();
    }
    
    // Jika mode edit
    if ($edit_mode) {
        // Ambil data produk lama
        $query = "SELECT gambar_path FROM products WHERE id = ?";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $produk_lama = $result->fetch_assoc();
        
        // Update produk
        if (!empty($gambar_path)) {
            // Jika ada gambar baru, hapus gambar lama
            if (!empty($produk_lama['gambar_path']) && file_exists($produk_lama['gambar_path'])) {
                unlink($produk_lama['gambar_path']);
            }
            
            $update_query = "UPDATE products SET sku = ?, nama = ?, category_id = ?, deskripsi = ?, 
                            harga_jual = ?, harga_beli = ?, stok = ?, gambar_path = ?, updated_at = NOW() 
                            WHERE id = ?";
            $update_stmt = $koneksi->prepare($update_query);
            $update_stmt->bind_param("ssisddisi", $sku, $nama, $kategori_id, $deskripsi, 
                                    $harga_jual, $harga_beli, $stok, $gambar_path, $id);
        } else {
            // Jika tidak ada gambar baru, gunakan gambar lama
            $update_query = "UPDATE products SET sku = ?, nama = ?, category_id = ?, deskripsi = ?, 
                            harga_jual = ?, harga_beli = ?, stok = ?, updated_at = NOW() 
                            WHERE id = ?";
            $update_stmt = $koneksi->prepare($update_query);
            $update_stmt->bind_param("ssisddii", $sku, $nama, $kategori_id, $deskripsi, 
                                    $harga_jual, $harga_beli, $stok, $id);
        }
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Produk berhasil diperbarui!";
        } else {
            $_SESSION['error'] = "Gagal memperbarui produk!";
            
            // Hapus gambar yang baru diupload jika ada
            if (!empty($gambar_path) && file_exists($gambar_path)) {
                unlink($gambar_path);
            }
        }
    } else {
        // Mode tambah
        $insert_query = "INSERT INTO products (sku, nama, category_id, deskripsi, harga_jual, harga_beli, stok, gambar_path) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $koneksi->prepare($insert_query);
        $insert_stmt->bind_param("ssisddis", $sku, $nama, $kategori_id, $deskripsi, 
                                $harga_jual, $harga_beli, $stok, $gambar_path);
        
        if ($insert_stmt->execute()) {
            $_SESSION['success'] = "Produk berhasil ditambahkan!";
        } else {
            $_SESSION['error'] = "Gagal menambahkan produk!";
            
            // Hapus gambar yang baru diupload jika ada
            if (!empty($gambar_path) && file_exists($gambar_path)) {
                unlink($gambar_path);
            }
        }
    }
    
    // Redirect ke halaman produk
    header("Location: products.php");
    exit();
} else {
    // Jika bukan POST request
    header("Location: products.php");
    exit();
}
?>