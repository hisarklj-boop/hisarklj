<?php
require_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: /admin/login.php');
    exit;
}

// Ürünleri çek
$stmt = $db->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürünler - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.2/dist/full.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .sidebar { background: #1e293b; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="sidebar w-64 text-white flex-shrink-0">
            <div class="p-6">
                <h1 class="text-2xl font-bold">Admin Panel</h1>
                <p class="text-gray-400 text-sm">Okul Kantini Yönetimi</p>
            </div>
            
            <nav class="mt-6">
                <a href="/admin/dashboard.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-white/10">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Dashboard
                </a>
                <a href="/admin/products.php" class="flex items-center px-6 py-3 bg-white/10 border-l-4 border-white">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    Ürünler
                </a>
                <a href="/admin/students.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-white/10">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Öğrenciler
                </a>
                <a href="/admin/orders.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-white/10">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    Siparişler
                </a>
                <a href="/admin/profile.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-white/10">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Profil
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
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-gray-800">Ürün Yönetimi</h2>
                    <a href="/admin/product-add.php" class="btn btn-primary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Yeni Ürün Ekle
                    </a>
                </div>
            </div>

            <div class="p-6">
                <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success mb-4">
                    <span>Ürün başarıyla eklendi!</span>
                </div>
                <?php endif; ?>
                
                <?php if(isset($_GET['deleted'])): ?>
                <div class="alert alert-warning mb-4">
                    <span>Ürün silindi!</span>
                </div>
                <?php endif; ?>

                <!-- Ürün Listesi -->
                <div class="card bg-white shadow">
                    <div class="card-body">
                        <div class="overflow-x-auto">
                            <table class="table table-zebra">
                                <thead>
                                    <tr>
                                        <th>Resim</th>
                                        <th>Kod</th>
                                        <th>Ürün Adı</th>
                                        <th>Fiyat</th>
                                        <th>Stok</th>
                                        <th>Durum</th>
                                        <th>Tarih</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($products): ?>
                                        <?php foreach($products as $product): ?>
                                            <?php 
                                            // Ürün resmini çek
                                            $img_stmt = $db->prepare("SELECT image_path FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1");
                                            $img_stmt->execute([$product['id']]);
                                            $primary_img = $img_stmt->fetch();
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="avatar">
                                                        <div class="w-12 h-12 rounded">
                                                            <?php if($primary_img): ?>
                                                                <img src="/uploads/products/<?= htmlspecialchars($primary_img['image_path']) ?>" alt="">
                                                            <?php else: ?>
                                                                <div class="bg-gray-200 w-12 h-12 flex items-center justify-center rounded">
                                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                                    </svg>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class="badge badge-outline"><?= htmlspecialchars($product['code']) ?></span></td>
                                                <td>
                                                    <div>
                                                        <div class="font-bold"><?= htmlspecialchars($product['title']) ?></div>
                                                        <div class="text-sm opacity-50"><?= htmlspecialchars(substr($product['description'], 0, 50)) ?>...</div>
                                                    </div>
                                                </td>
                                                <td><span class="font-bold text-blue-600"><?= number_format($product['price'], 2) ?> ₺</span></td>
                                                <td>
                                                    <span class="<?= $product['stock'] > 0 ? 'text-green-600' : 'text-red-600' ?>">
                                                        <?= $product['stock'] ?> adet
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if($product['is_active']): ?>
                                                        <span class="badge badge-success">Aktif</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-error">Pasif</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('d.m.Y', strtotime($product['created_at'])) ?></td>
                                                <td>
                                                    <div class="flex space-x-2">
                                                        <a href="/admin/product-edit.php?id=<?= $product['id'] ?>" 
                                                           class="btn btn-ghost btn-sm" title="Düzenle">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                            </svg>
                                                        </a>
                                                        <a href="/admin/product-delete.php?id=<?= $product['id'] ?>" 
                                                           onclick="return confirm('Bu ürünü ve tüm resimlerini silmek istediğinize emin misiniz?')" 
                                                           class="btn btn-ghost btn-sm text-red-600" title="Sil">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-8">
                                                <div class="flex flex-col items-center">
                                                    <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                    </svg>
                                                    <p class="text-gray-500 mb-4">Henüz ürün eklenmemiş</p>
                                                    <a href="/admin/product-add.php" class="btn btn-primary btn-sm">İlk Ürünü Ekle</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
