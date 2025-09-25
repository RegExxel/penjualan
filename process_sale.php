<?php
require_once 'koneksi.php';

// Cek jika user belum login
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    header("Location: user_login.php");
    exit();
}

// Cek jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Cek jika keranjang kosong
    if (empty($_SESSION['cart'])) {
        $_SESSION['error'] = "Keranjang belanja kosong!";
        header("Location: pos.php");
        exit();
    }
    
    // Ambil data dari form
    if (isset($_SESSION['admin_id'])) {
        $pelanggan_id = !empty($_POST['pelanggan']) ? (int)$_POST['pelanggan'] : null;
    } else {
        $pelanggan_id = (int)$_POST['pelanggan']; // Untuk user biasa, pelanggan_id adalah user_id
    }
    
    $pembayaran_method = $koneksi->real_escape_string($_POST['pembayaran']);
    $total_amount = (float)$_POST['total_amount'];
    $total_items = (int)$_POST['total_items'];
    
    // Generate nomor invoice
    $date = date('Ymd');
    $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));
    $invoice_no = "INV-$date-$random";
    
    // Mulai transaksi
    $koneksi->begin_transaction();
    
    try {
        // Insert data ke tabel sales
        $insert_sales_query = "INSERT INTO sales (invoice_no, customer_id, total_amount, total_items, pembayaran_method) 
                              VALUES (?, ?, ?, ?, ?)";
        $insert_sales_stmt = $koneksi->prepare($insert_sales_query);
        $insert_sales_stmt->bind_param("sidis", $invoice_no, $pelanggan_id, $total_amount, $total_items, $pembayaran_method);
        $insert_sales_stmt->execute();
        
        // Get ID dari sales yang baru diinsert
        $sale_id = $koneksi->insert_id;
        
        // Insert data ke tabel sale_items dan update stok produk
        foreach ($_SESSION['cart'] as $item) {
            // Insert ke sale_items
            $subtotal = $item['harga'] * $item['qty'];
            $insert_items_query = "INSERT INTO sale_items (sale_id, product_id, qty, price, subtotal) 
                                  VALUES (?, ?, ?, ?, ?)";
            $insert_items_stmt = $koneksi->prepare($insert_items_query);
            $insert_items_stmt->bind_param("iiidd", $sale_id, $item['id'], $item['qty'], $item['harga'], $subtotal);
            $insert_items_stmt->execute();
            
            // Update stok produk
            $update_stok_query = "UPDATE products SET stok = stok - ? WHERE id = ?";
            $update_stok_stmt = $koneksi->prepare($update_stok_query);
            $update_stok_stmt->bind_param("ii", $item['qty'], $item['id']);
            $update_stok_stmt->execute();
        }
        
        // Commit transaksi
        $koneksi->commit();
        
        // Kosongkan keranjang
        $_SESSION['cart'] = [];
        
        // Set success message
        $_SESSION['success'] = "Transaksi berhasil! Nomor invoice: $invoice_no";
        
        // Redirect ke halaman yang sesuai
        if (isset($_SESSION['admin_id'])) {
            header("Location: sales_list.php");
        } else {
            header("Location: index.php");
        }
        exit();
        
    } catch (Exception $e) {
        // Rollback transaksi
        $koneksi->rollback();
        
        // Set error message
        $_SESSION['error'] = "Gagal memproses transaksi: " . $e->getMessage();
        
        // Redirect ke halaman kasir
        header("Location: pos.php");
        exit();
    }
} else {
    // Jika bukan POST request
    header("Location: pos.php");
    exit();
}
?>