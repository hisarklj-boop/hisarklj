<?php
require_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: /login.php');
    exit;
}

$stmt = $db->prepare("SELECT s.*, u.phone FROM students s JOIN users u ON s.user_id = u.id WHERE s.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

// Sepet işlemleri
if(isset($_POST['action'])) {
    if($_POST['action'] == 'add') {
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        
        if(!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if(isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
        
        header('Location: /student/cart.php?added=1');
        exit;
    }
    
    if($_POST['action'] == 'remove') {
        $product_id = intval($_POST['product_id']);
        unset($_SESSION['cart'][$product_id]);
        header('Location: /student/cart.php?removed=1');
        exit;
    }
    
    if($_POST['action'] == 'update') {
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        
        if($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
        
        header('Location: /student/cart.php?updated=1');
        exit;
    }
}

// Sepet ürünlerini getir
$cart_items = [];
$total = 0;

if(isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    $stmt = $db->prepare("
        SELECT p.*, 
               (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as main_image
        FROM products p 
        WHERE p.id IN ($placeholders) AND p.is_active = 1
    ");
    $stmt->execute($product_ids);
    
    while($product = $stmt->fetch()) {
        $quantity = $_SESSION['cart'][$product['id']];
        $subtotal = $product['price'] * $quantity;
        $total += $subtotal;
        
        $cart_items[] = [
            'product' => $product,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sepetim - Okul Kantini</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.2/dist/full.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .sidebar { background: #1e293b; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <div class="sidebar w-64 text-white flex-shrink-0">
            <div class="p-6">
                <h1 class="text-2xl font-bold">Öğrenci Paneli</h1>
                <p class="text-gray-400 text-sm">Okul Kantini</p>
            </div>
            <nav class="mt-6">
                <a href="/student/dashboard.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-white/10">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Ana Sayfa
                </a>
                <a href="/student/cart.php" class="flex items-center px-6 py-3 bg-white/10 border-l-4 border-white">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5M17 13v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6"/></svg>
                    Sepetim
                    <span class="badge badge-primary badge-sm ml-2"><?= count($cart_items) ?></span>
                </a>
                <a href="/student/orders.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-white/10">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    Siparişlerim
                </a>
                <a href="/student/profile.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-white/10">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Profilim
                </a>
            </nav>
            <div class="absolute bottom-0 w-64 p-6">
                <a href="/logout.php" class="flex items-center text-red-400 hover:text-red-300">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Çıkış Yap
                </a>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto">
            <div class="bg-white shadow-sm px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Sepetim</h2>
                        <p class="text-gray-600"><?= count($cart_items) ?> ürün</p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-500">Toplam Tutar</div>
                        <div class="text-2xl font-bold text-blue-600"><?= number_format($total, 2) ?> ₺</div>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <?php if(isset($_GET['added'])): ?>
                <div class="alert alert-success mb-6">
                    <span>✅ Ürün sepete eklendi!</span>
                </div>
                <?php endif; ?>

                <?php if(isset($_GET['removed'])): ?>
                <div class="alert alert-info mb-6">
                    <span>🗑️ Ürün sepetten çıkarıldı!</span>
                </div>
                <?php endif; ?>

                <?php if(isset($_GET['updated'])): ?>
                <div class="alert alert-success mb-6">
                    <span>✅ Sepet güncellendi!</span>
                </div>
                <?php endif; ?>

                <?php if(!empty($cart_items)): ?>
                <div class="grid lg:grid-cols-3 gap-6">
                    <!-- Sepet Ürünleri -->
                    <div class="lg:col-span-2">
                        <div class="card bg-white shadow">
                            <div class="card-body">
                                <h3 class="text-lg font-bold mb-4">Sepet Ürünleri</h3>
                                
                                <div class="space-y-4">
                                    <?php foreach($cart_items as $item): ?>
                                    <div class="flex items-center p-4 border rounded-lg">
                                        <div class="w-20 h-20 bg-gray-200 rounded-lg flex-shrink-0">
                                            <?php if($item['product']['main_image']): ?>
                                                <img src="/uploads/products/<?= htmlspecialchars($item['product']['main_image']) ?>" alt="" class="w-full h-full object-cover rounded-lg">
                                            <?php else: ?>
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="ml-4 flex-1">
                                            <h4 class="font-semibold"><?= htmlspecialchars($item['product']['title']) ?></h4>
                                            <p class="text-sm text-gray-600"><?= number_format($item['product']['price'], 2) ?> ₺</p>
                                        </div>
                                        
                                        <div class="flex items-center space-x-2">
                                            <form method="POST" class="flex items-center space-x-2">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="product_id" value="<?= $item['product']['id'] ?>">
                                                <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['product']['stock'] ?>" class="input input-bordered input-sm w-20">
                                                <button type="submit" class="btn btn-sm btn-primary">Güncelle</button>
                                            </form>
                                            
                                            <form method="POST">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="product_id" value="<?= $item['product']['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-error">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                        
                                        <div class="ml-4 text-right">
                                            <div class="font-bold text-lg"><?= number_format($item['subtotal'], 2) ?> ₺</div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sepet Özeti -->
                    <div>
                        <div class="card bg-white shadow sticky top-6">
                            <div class="card-body">
                                <h3 class="text-lg font-bold mb-4">Sipariş Özeti</h3>
                                
                                <div class="space-y-2 mb-4">
                                    <div class="flex justify-between">
                                        <span>Ürün Sayısı:</span>
                                        <span><?= count($cart_items) ?> adet</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Ara Toplam:</span>
                                        <span><?= number_format($total, 2) ?> ₺</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Kargo:</span>
                                        <span class="text-green-600">Ücretsiz</span>
                                    </div>
                                    <hr>
                                    <div class="flex justify-between text-lg font-bold">
                                        <span>Toplam:</span>
                                        <span class="text-blue-600"><?= number_format($total, 2) ?> ₺</span>
                                    </div>
                                </div>
                                
                                <a href="/student/checkout.php" class="btn btn-primary w-full">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                    Ödeme Yap
                                </a>
                                
                                <a href="/student/dashboard.php" class="btn btn-outline w-full mt-2">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                    Alışverişe Devam Et
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center py-16">
                    <div class="mx-auto w-32 h-32 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5M17 13v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-600 mb-3">Sepetiniz Boş</h3>
                    <p class="text-gray-500 mb-6">Henüz sepetinize ürün eklemediniz.</p>
                    <a href="/student/dashboard.php" class="btn btn-primary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        Alışverişe Başla
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
