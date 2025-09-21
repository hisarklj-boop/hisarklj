<?php
require_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: /login.php');
    exit;
}

$order_number = $_GET['order'] ?? '';
if(empty($order_number)) {
    header('Location: /student/dashboard.php');
    exit;
}

// Sipariş bilgilerini getir
$stmt = $db->prepare("
    SELECT o.*, s.name, s.surname, s.class, u.phone 
    FROM orders o 
    JOIN students s ON o.student_id = s.user_id 
    JOIN users u ON s.user_id = u.id 
    WHERE o.order_number = ? AND o.student_id = ?
");
$stmt->execute([$order_number, $_SESSION['user_id']]);
$order = $stmt->fetch();

if(!$order) {
    header('Location: /student/dashboard.php');
    exit;
}

// Sipariş ürünlerini getir
$stmt = $db->prepare("
    SELECT oi.*, p.title, p.code,
           (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as main_image
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order['id']]);
$order_items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Başarılı - Okul Kantini</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.2/dist/full.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .sidebar { background: #1e293b; }
        .success-animation {
            animation: successPulse 2s ease-in-out infinite;
        }
        @keyframes successPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
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
                <a href="/student/cart.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-white/10">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5M17 13v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6"/></svg>
                    Sepetim
                </a>
                <a href="/student/orders.php" class="flex items-center px-6 py-3 bg-white/10 border-l-4 border-white">
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
            <div class="min-h-full flex items-center justify-center p-6">
                <div class="max-w-4xl w-full">
                    <!-- Başarı Mesajı -->
                    <div class="text-center mb-8">
                        <div class="success-animation inline-block">
                            <div class="w-24 h-24 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </div>
                        <h1 class="text-3xl font-bold text-green-600 mb-2">Siparişiniz Alındı!</h1>
                        <p class="text-gray-600">Teşekkür ederiz. Siparişiniz başarıyla oluşturuldu.</p>
                    </div>

                    <div class="grid lg:grid-cols-2 gap-8">
                        <!-- Sipariş Bilgileri -->
                        <div class="card bg-white shadow-xl">
                            <div class="card-body">
                                <h2 class="card-title text-blue-600 mb-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Sipariş Detayları
                                </h2>
                                
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Sipariş No:</span>
                                        <span class="font-bold text-blue-600"><?= htmlspecialchars($order['order_number']) ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Tarih:</span>
                                        <span><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Durum:</span>
                                        <span class="badge badge-warning">Beklemede</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Ödeme:</span>
                                        <span class="capitalize">
                                            <?= $order['payment_method'] == 'eft' ? 'Havale/EFT' : 'Kredi Kartı' ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between text-lg font-bold border-t pt-3">
                                        <span>Toplam Tutar:</span>
                                        <span class="text-green-600"><?= number_format($order['total_amount'], 2) ?> ₺</span>
                                    </div>
                                </div>

                                <?php if($order['payment_method'] == 'eft'): ?>
                                <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <h3 class="font-semibold text-yellow-800 mb-2">💳 Ödeme Bilgileri</h3>
                                    <div class="text-sm text-yellow-700 space-y-1">
                                        <div><strong>Banka:</strong> Türkiye İş Bankası</div>
                                        <div><strong>IBAN:</strong> TR33 0006 4000 0011 2345 6789 01</div>
                                        <div><strong>Hesap Adı:</strong> Okul Kantini Ltd. Şti.</div>
                                        <div><strong>Açıklama:</strong> <?= htmlspecialchars($order['order_number']) ?></div>
                                    </div>
                                    <div class="mt-2 text-xs text-yellow-600">
                                        ⚠️ Havale yaparken mutlaka sipariş numarasını açıklama kısmına yazınız.
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Müşteri Bilgileri -->
                        <div class="card bg-white shadow-xl">
                            <div class="card-body">
                                <h2 class="card-title text-purple-600 mb-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    Öğrenci Bilgileri
                                </h2>
                                
                                <div class="space-y-3">
                                    <div>
                                        <span class="text-gray-600">Ad Soyad:</span>
                                        <div class="font-semibold"><?= htmlspecialchars($order['name'] . ' ' . $order['surname']) ?></div>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Sınıf:</span>
                                        <div class="font-semibold"><?= htmlspecialchars($order['class']) ?></div>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Telefon:</span>
                                        <div class="font-semibold"><?= htmlspecialchars($order['phone']) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sipariş Ürünleri -->
                    <div class="card bg-white shadow-xl mt-8">
                        <div class="card-body">
                            <h2 class="card-title text-orange-600 mb-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                Sipariş Edilen Ürünler
                            </h2>
                            
                            <div class="overflow-x-auto">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Ürün</th>
                                            <th>Birim Fiyat</th>
                                            <th>Adet</th>
                                            <th>Toplam</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($order_items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="flex items-center space-x-3">
                                                    <div class="avatar">
                                                        <div class="mask mask-squircle w-12 h-12">
                                                            <?php if($item['main_image']): ?>
                                                                <img src="/uploads/products/<?= htmlspecialchars($item['main_image']) ?>" alt="">
                                                            <?php else: ?>
                                                                <div class="bg-gray-200 w-full h-full flex items-center justify-center">
                                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                                    </svg>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="font-bold"><?= htmlspecialchars($item['title']) ?></div>
                                                        <div class="text-sm opacity-50">Kod: <?= htmlspecialchars($item['code']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= number_format($item['price'], 2) ?> ₺</td>
                                            <td><span class="badge badge-ghost badge-sm"><?= $item['quantity'] ?></span></td>
                                            <td class="font-bold"><?= number_format($item['subtotal'], 2) ?> ₺</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Aksiyonlar -->
                    <div class="text-center mt-8 space-x-4">
                        <a href="/student/orders.php" class="btn btn-primary">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            Tüm Siparişlerim
                        </a>
                        <a href="/student/dashboard.php" class="btn btn-outline">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            Alışverişe Devam Et
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
