<?php
require_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: /login.php');
    exit;
}

$stmt = $db->prepare("SELECT s.*, u.phone FROM students s JOIN users u ON s.user_id = u.id WHERE s.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

// Siparişleri getir
$stmt = $db->prepare("
    SELECT o.*, 
           COUNT(oi.id) as item_count,
           GROUP_CONCAT(p.title SEPARATOR ', ') as product_names
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    LEFT JOIN products p ON oi.product_id = p.id 
    WHERE o.student_id = ? 
    GROUP BY o.id 
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Sipariş detayı getir (modal için)
if(isset($_GET['detail'])) {
    $order_id = $_GET['detail'];
    
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND student_id = ?");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order_detail = $stmt->fetch();
    
    if($order_detail) {
        $stmt = $db->prepare("
            SELECT oi.*, p.title, p.code,
                   (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as main_image
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siparişlerim - Okul Kantini</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.2/dist/full.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .sidebar { background: #1e293b; }
        .order-card {
            transition: all 0.3s ease;
        }
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
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
                    <span class="badge badge-primary badge-sm ml-2"><?= count($orders) ?></span>
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
                        <h2 class="text-2xl font-bold text-gray-800">Siparişlerim</h2>
                        <p class="text-gray-600">Tüm siparişlerinizi buradan takip edebilirsiniz</p>
                    </div>
                    <div class="stats shadow">
                        <div class="stat">
                            <div class="stat-title">Toplam Sipariş</div>
                            <div class="stat-value text-primary"><?= count($orders) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <?php if(!empty($orders)): ?>
                <div class="space-y-6">
                    <?php foreach($orders as $order): ?>
                    <div class="order-card card bg-white shadow">
                        <div class="card-body">
                            <div class="flex flex-col lg:flex-row lg:items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-4 mb-4 lg:mb-0">
                                        <div class="bg-blue-100 p-3 rounded-full">
                                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-bold text-gray-800">
                                                Sipariş #<?= htmlspecialchars($order['order_number']) ?>
                                            </h3>
                                            <p class="text-sm text-gray-600">
                                                <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex flex-col lg:flex-row lg:items-center space-y-2 lg:space-y-0 lg:space-x-6">
                                    <div class="text-center lg:text-right">
                                        <div class="text-sm text-gray-600">Ürün Sayısı</div>
                                        <div class="font-semibold"><?= $order['item_count'] ?> adet</div>
                                    </div>
                                    
                                    <div class="text-center lg:text-right">
                                        <div class="text-sm text-gray-600">Toplam Tutar</div>
                                        <div class="text-lg font-bold text-green-600"><?= number_format($order['total_amount'], 2) ?> ₺</div>
                                    </div>
                                    
                                    <div class="text-center lg:text-right">
                                        <div class="text-sm text-gray-600 mb-1">Durum</div>
                                        <?php
                                        $status_colors = [
                                            'pending' => 'badge-warning',
                                            'confirmed' => 'badge-info', 
                                            'preparing' => 'badge-primary',
                                            'ready' => 'badge-success',
                                            'delivered' => 'badge-success',
                                            'cancelled' => 'badge-error'
                                        ];
                                        $status_texts = [
                                            'pending' => 'Beklemede',
                                            'confirmed' => 'Onaylandı',
                                            'preparing' => 'Hazırlanıyor',
                                            'ready' => 'Teslim Edilebilir',
                                            'delivered' => 'Teslim Edildi',
                                            'cancelled' => 'İptal Edildi'
                                        ];
                                        ?>
                                        <div class="badge <?= $status_colors[$order['status']] ?> badge-lg">
                                            <?= $status_texts[$order['status']] ?>
                                        </div>
                                    </div>
                                    
                                    <div class="flex space-x-2">
                                        <a href="?detail=<?= $order['id'] ?>" class="btn btn-sm btn-outline">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            Detay
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                                <div class="text-sm text-gray-600">Ürünler:</div>
                                <div class="text-sm font-medium truncate"><?= htmlspecialchars($order['product_names']) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-16">
                    <div class="mx-auto w-32 h-32 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-600 mb-3">Henüz Siparişiniz Yok</h3>
                    <p class="text-gray-500 mb-6">İlk siparişinizi oluşturmak için alışverişe başlayın.</p>
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

    <!-- Sipariş Detay Modal -->
    <?php if(isset($order_detail)): ?>
    <dialog id="orderDetailModal" class="modal modal-open">
        <div class="modal-box max-w-4xl">
            <h3 class="font-bold text-lg mb-4">Sipariş Detayı - #<?= htmlspecialchars($order_detail['order_number']) ?></h3>
            
            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h4 class="font-semibold mb-2">Sipariş Bilgileri</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>Tarih:</span>
                            <span><?= date('d.m.Y H:i', strtotime($order_detail['created_at'])) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Durum:</span>
                            <span class="badge <?= $status_colors[$order_detail['status']] ?>">
                                <?= $status_texts[$order_detail['status']] ?>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span>Ödeme:</span>
                            <span><?= $order_detail['payment_method'] == 'eft' ? 'Havale/EFT' : 'Kredi Kartı' ?></span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-semibold mb-2">Tutar Bilgileri</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>Ara Toplam:</span>
                            <span><?= number_format($order_detail['total_amount'], 2) ?> ₺</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Kargo:</span>
                            <span class="text-green-600">Ücretsiz</span>
                        </div>
                        <div class="flex justify-between font-bold border-t pt-2">
                            <span>Toplam:</span>
                            <span><?= number_format($order_detail['total_amount'], 2) ?> ₺</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <h4 class="font-semibold mb-4">Sipariş Ürünleri</h4>
            <div class="overflow-x-auto">
                <table class="table table-zebra">
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
                            <td><span class="badge badge-ghost"><?= $item['quantity'] ?></span></td>
                            <td class="font-bold"><?= number_format($item['subtotal'], 2) ?> ₺</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="modal-action">
                <a href="/student/orders.php" class="btn">Kapat</a>
            </div>
        </div>
    </dialog>
    <?php endif; ?>
</body>
</html>
