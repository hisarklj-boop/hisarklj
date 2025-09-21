<?php
require_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: /login.php');
    exit;
}

if($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: /student/cart.php');
    exit;
}

// Sepet kontrolü
if(!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: /student/cart.php');
    exit;
}

try {
    $db->beginTransaction();
    
    $student_id = $_SESSION['user_id'];
    $payment_method = $_POST['payment_method'];
    
    // Öğrenci bilgilerini al
    $stmt = $db->prepare("SELECT s.*, u.phone FROM students s JOIN users u ON s.user_id = u.id WHERE s.user_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    // Sepet ürünlerini getir ve toplam hesapla
    $cart_items = [];
    $total = 0;
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    $stmt = $db->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND is_active = 1");
    $stmt->execute($product_ids);
    
    while($product = $stmt->fetch()) {
        $quantity = $_SESSION['cart'][$product['id']];
        
        // Stok kontrolü
        if($product['stock'] < $quantity) {
            throw new Exception($product['title'] . ' ürünü için yeterli stok yok!');
        }
        
        $subtotal = $product['price'] * $quantity;
        $total += $subtotal;
        
        $cart_items[] = [
            'product' => $product,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }
    
    // Sipariş numarası oluştur
    $order_number = 'ORD' . date('Ymd') . rand(1000, 9999);
    
    // Siparişi veritabanına kaydet
    $stmt = $db->prepare("
        INSERT INTO orders (student_id, order_number, total_amount, payment_method, status, created_at) 
        VALUES (?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$student_id, $order_number, $total, $payment_method]);
    $order_id = $db->lastInsertId();
    
    // Sipariş ürünlerini kaydet
    foreach($cart_items as $item) {
        $stmt = $db->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $order_id,
            $item['product']['id'],
            $item['quantity'],
            $item['product']['price'],
            $item['subtotal']
        ]);
        
        // Stok azalt
        $stmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt->execute([$item['quantity'], $item['product']['id']]);
    }
    
    $db->commit();
    
    // Sepeti temizle
    unset($_SESSION['cart']);
    
    // Sonuç sayfasına yönlendir
    header('Location: /student/order-success.php?order=' . $order_number);
    exit;
    
} catch(Exception $e) {
    $db->rollBack();
    $error = $e->getMessage();
    header('Location: /student/checkout.php?error=' . urlencode($error));
    exit;
}
?>
