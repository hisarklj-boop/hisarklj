<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: /admin/login.php');
    exit;
}

$product_id = $_GET['id'] ?? 0;

if($product_id > 0) {
    try {
        // Resimleri bul
        $stmt = $db->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $images = $stmt->fetchAll();
        
        // Fiziksel dosyaları sil
        foreach($images as $img) {
            $file_path = '../uploads/products/' . $img['image_path'];
            if(file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Veritabanından resimleri sil
        $stmt = $db->prepare("DELETE FROM product_images WHERE product_id = ?");
        $stmt->execute([$product_id]);
        
        // Ürünü sil
        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        
        header('Location: /admin/products.php?deleted=1');
        exit;
    } catch(Exception $e) {
        die("Hata: " . $e->getMessage());
    }
} else {
    header('Location: /admin/products.php');
    exit;
}
?>
