<?php
session_start();
require_once '../config/database.php';

// Öğrenci kontrolü
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: /student/login.php');
    exit;
}

// Öğrenci bilgilerini al
try {
    $stmt = $db->prepare("SELECT s.*, u.phone FROM students s JOIN users u ON s.user_id = u.id WHERE s.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch();
    
    if(!$student) {
        // Öğrenci bilgisi yoksa çıkış yap
        header('Location: /student/logout.php');
        exit;
    }
    
    // Öğrencinin sınıfına göre ürünleri getir
    $student_class = $student['class'];
    
    // Tüm ürünleri getir (sınıf filtresi JSON içinde kontrol edilecek)
    $stmt = $db->query("
        SELECT p.*, 
               (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as main_image
        FROM products p 
        WHERE p.is_active = 1 
        ORDER BY p.created_at DESC
    ");
    $all_products = $stmt->fetchAll();
    
    // Sınıfa uygun ürünleri filtrele
    $products = [];
    foreach($all_products as $product) {
        $allowed_classes = json_decode($product['allowed_classes'], true);
        
        // Eğer allowed_classes boş veya null ise tüm sınıflara göster
        if(empty($allowed_classes) || in_array($student_class, $allowed_classes)) {
            $products[] = $product;
        }
    }
    
    // Son siparişleri getir
    $stmt = $db->prepare("
        SELECT * FROM orders 
        WHERE student_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_orders = $stmt->fetchAll();
    
} catch(PDOException $e) {
    // Hata durumunda varsayılan değerler
    $student = null;
    $products = [];
    $recent_orders = [];
}

// Sepet sayısını hesapla
$cart_count = 0;
if(isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğrenci Paneli - Okul Kantini</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.2/dist/full.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .sidebar { background: #1e293b; }
        .product-card {
            transition: transform 0.2s;
        }
        .product-card:hover {
            transform: translateY(-4px);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="sidebar w-64 text-white flex-shrink-0">
            <div class="p-6">
                <h1 class="text-2xl font-bold">Öğrenci Paneli</h1>
                <p class="text-gray-400 text-sm">Okul Kantini</p>
            </div>
            
            <nav class="mt-6">
                <a href="/student/dashboard.php" class="flex items-center px-6 py-3 bg-white/10 border-l-4 border-white">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Ana Sayfa
                </a>
                <a href="/student/cart.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-white/10">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Sepetim
                    <?php if($cart_count > 0): ?>
                    <span class="badge badge-primary badge-sm ml-2"><?= $cart_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="/student/orders.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-white/10">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    Siparişlerim
                </a>
                <a href="/student/profile.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-white/10">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Profilim
                </a>
            </nav>
            
            <div class="absolute bottom-0 w-64 p-6">
                <a href="/student/logout.php" class="flex items-center text-red-400 hover:text-red-300">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Çıkış Yap
                </a>
            </div>
        </div>

        <!-- Ana İçerik -->
        <div class="flex-1 overflow-y-auto">
            <!-- Header -->
            <div class="bg-white shadow-sm px-6 py-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Hoşgeldin, <?= htmlspecialchars($student['name'] ?? 'Öğrenci') ?>!</h2>
                        <p class="text-gray-600"><?= htmlspecialchars($student['class'] ?? '') ?> - <?= htmlspecialchars($student['phone'] ?? '') ?></p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="/student/cart.php" class="btn btn-primary btn-sm">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Sepete Git
                        </a>
                        <div class="avatar">
                            <div class="w-10 rounded-full bg-gradient-to-r from-blue-500 to-green-600">
                                <span class="flex items-center justify-center h-full text-white font-bold">
                                    <?= strtoupper(substr($student['name'] ?? 'O', 0, 1)) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- İçerik -->
            <div class="p-6">
                <!-- Bilgi Kartları -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="card bg-gradient-to-r from-blue-500 to-blue-600 text-white">
                        <div class="card-body">
                            <h3 class="card-title text-white/90">Sınıfınız</h3>
                            <p class="text-3xl font-bold"><?= htmlspecialchars($student['class'] ?? '-') ?></p>
                        </div>
                    </div>
                    
                    <div class="card bg-gradient-to-r from-green-500 to-green-600 text-white">
                        <div class="card-body">
                            <h3 class="card-title text-white/90">Sepetim</h3>
                            <p class="text-3xl font-bold"><?= $cart_count ?> Ürün</p>
                        </div>
                    </div>
                    
                    <div class="card bg-gradient-to-r from-purple-500 to-purple-600 text-white">
                        <div class="card-body">
                            <h3 class="card-title text-white/90">Siparişlerim</h3>
                            <p class="text-3xl font-bold"><?= count($recent_orders) ?> Adet</p>
                        </div>
                    </div>
                </div>

                <!-- Ürünler -->
                <div class="mb-6">
                    <h3 class="text-xl font-bold mb-4">
                        <?= htmlspecialchars($student['class'] ?? 'Sizin') ?> İçin Uygun Ürünler
                    </h3>
                    
                    <?php if(count($products) > 0): ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        <?php foreach($products as $product): ?>
                        <div class="card bg-white shadow product-card">
                            <figure class="px-4 pt-4">
                                <?php if($product['main_image']): ?>
                                <img src="/uploads/products/<?= htmlspecialchars($product['main_image']) ?>" 
                                     alt="<?= htmlspecialchars($product['title']) ?>" 
                                     class="rounded-xl h-48 w-full object-cover">
                                <?php else: ?>
                                <div class="bg-gray-200 rounded-xl h-48 w-full flex items-center justify-center">
                                    <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <?php endif; ?>
                            </figure>
                            <div class="card-body">
                                <h2 class="card-title text-base">
                                    <?= htmlspecialchars($product['title']) ?>
                                    <?php if($product['stock'] <= 5 && $product['stock'] > 0): ?>
                                    <div class="badge badge-warning badge-sm">Son <?= $product['stock'] ?></div>
                                    <?php endif; ?>
                                </h2>
                                <p class="text-sm text-gray-600 line-clamp-2">
                                    <?= htmlspecialchars($product['description'] ?? 'Açıklama mevcut değil') ?>
                                </p>
                                <div class="flex justify-between items-center mt-2">
                                    <span class="text-xl font-bold text-primary"><?= number_format($product['price'], 2) ?> ₺</span>
                                    <?php if($product['stock'] > 0): ?>
                                    <span class="badge badge-success badge-sm">Stokta</span>
                                    <?php else: ?>
                                    <span class="badge badge-error badge-sm">Tükendi</span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-actions justify-end mt-2">
                                    <?php if($product['stock'] > 0): ?>
                                    <form method="POST" action="/student/cart.php">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="btn btn-primary btn-sm w-full">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                            Sepete Ekle
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <button class="btn btn-disabled btn-sm w-full">Stokta Yok</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <svg class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Sınıfınız için uygun ürün bulunmamaktadır.</span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Son Siparişler -->
                <?php if(count($recent_orders) > 0): ?>
                <div class="card bg-white shadow">
                    <div class="card-body">
                        <h3 class="card-title mb-4">Son Siparişlerim</h3>
                        <div class="overflow-x-auto">
                            <table class="table table-zebra">
                                <thead>
                                    <tr>
                                        <th>Sipariş No</th>
                                        <th>Tutar</th>
                                        <th>Durum</th>
                                        <th>Tarih</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_orders as $order): ?>
                                    <tr>
                                        <td class="font-mono text-sm"><?= htmlspecialchars($order['order_number']) ?></td>
                                        <td class="font-bold"><?= number_format($order['total_amount'], 2) ?> ₺</td>
                                        <td>
                                            <?php 
                                            $status = $order['status'];
                                            $badges = [
                                                'pending' => '<span class="badge badge-warning badge-sm">Bekliyor</span>',
                                                'confirmed' => '<span class="badge badge-info badge-sm">Onaylandı</span>',
                                                'preparing' => '<span class="badge badge-primary badge-sm">Hazırlanıyor</span>',
                                                'ready' => '<span class="badge badge-success badge-sm">Hazır</span>',
                                                'delivered' => '<span class="badge badge-success badge-sm">Teslim Edildi</span>',
                                                'cancelled' => '<span class="badge badge-error badge-sm">İptal</span>'
                                            ];
                                            echo $badges[$status] ?? '<span class="badge badge-sm">Bilinmeyen</span>';
                                            ?>
                                        </td>
                                        <td class="text-sm"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                                        <td>
                                            <a href="/student/orders.php?detail=<?= $order['id'] ?>" class="btn btn-ghost btn-xs">Detay</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
