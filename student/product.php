<?php
require_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: /login.php');
    exit;
}

$product_id = $_GET['id'] ?? 0;

// Ürün bilgilerini çek
$stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if(!$product) {
    header('Location: /student/dashboard.php');
    exit;
}

// Ürün resimlerini çek
$stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC");
$stmt->execute([$product_id]);
$images = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['title']) ?> - Okul Kantini</title>
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
        <!-- Sidebar -->
        <div class="sidebar w-64 text-white flex-shrink-0">
            <div class="p-6">
                <h1 class="text-2xl font-bold">Öğrenci Paneli</h1>
                <p class="text-gray-400 text-sm">Okul Kantini</p>
            </div>
            
            <nav class="mt-6">
                <a href="/student/dashboard.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-white/10">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Ana Sayfa
                </a>
                <a href="/student/cart.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-white/10">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5M17 13v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6"/>
                    </svg>
                    Sepetim
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
                <a href="/logout.php" class="flex items-center text-red-400 hover:text-red-300">
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
                <div class="flex items-center">
                    <a href="/student/dashboard.php" class="btn btn-ghost btn-sm mr-4">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Geri
                    </a>
                    <h2 class="text-2xl font-bold text-gray-800">Ürün Detayı</h2>
                </div>
            </div>

            <!-- Ürün Detayı -->
            <div class="p-6">
                <div class="grid lg:grid-cols-2 gap-8">
                    <!-- Sol: Ürün Resimleri -->
                    <div class="space-y-4">
                        <!-- Ana Resim -->
                        <div class="aspect-square bg-gray-200 rounded-lg flex items-center justify-center">
                            <?php if($images && $images[0]['image_path']): ?>
                                <img src="/uploads/products/<?= htmlspecialchars($images[0]['image_path']) ?>" 
                                     alt="<?= htmlspecialchars($product['title']) ?>" 
                                     class="w-full h-full object-cover rounded-lg">
                            <?php else: ?>
                                <svg class="w-24 h-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            <?php endif; ?>
                        </div>

                        <!-- Küçük Resimler -->
                        <?php if(count($images) > 1): ?>
                            <div class="grid grid-cols-4 gap-2">
                                <?php foreach($images as $key => $image): ?>
                                    <div class="aspect-square bg-gray-200 rounded cursor-pointer">
                                        <img src="/uploads/products/<?= htmlspecialchars($image['image_path']) ?>" 
                                             alt="<?= htmlspecialchars($product['title']) ?>" 
                                             class="w-full h-full object-cover rounded">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sağ: Ürün Bilgileri -->
                    <div class="space-y-6">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($product['title']) ?></h1>
                            <p class="text-gray-600 mt-2">Ürün Kodu: <?= htmlspecialchars($product['code']) ?></p>
                        </div>

                        <div class="text-4xl font-bold text-blue-600">
                            <?= number_format($product['price'], 2) ?> ₺
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Açıklama</h3>
                            <p class="text-gray-600"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Stok Durumu:</span>
                                <span class="<?= $product['stock'] > 0 ? 'text-green-600' : 'text-red-600' ?> font-semibold">
                                    <?= $product['stock'] > 0 ? $product['stock'] . ' adet' : 'Stokta yok' ?>
                                </span>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center space-x-4">
                                <label class="text-gray-600">Adet:</label>
                                <div class="flex items-center space-x-2">
                                    <button class="btn btn-outline btn-sm">-</button>
                                    <input type="number" class="input input-bordered input-sm w-20 text-center" value="1" min="1" max="<?= $product['stock'] ?>">
                                    <button class="btn btn-outline btn-sm">+</button>
                                </div>
                            </div>

                            <?php if($product['stock'] > 0): ?>
                                <button class="btn btn-primary w-full">Sepete Ekle</button>
                            <?php else: ?>
                                <button class="btn btn-disabled w-full">Stokta Yok</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
