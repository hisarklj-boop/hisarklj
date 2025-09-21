<?php
session_start();
require_once '../config/database.php';

// Admin kontrolü
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: /admin/login.php');
    exit;
}

// İstatistikleri çek
try {
    // Toplam öğrenci
    $stmt = $db->query("SELECT COUNT(*) as total FROM students");
    $total_students = $stmt->fetch()['total'];
    
    // Toplam ürün
    $stmt = $db->query("SELECT COUNT(*) as total FROM products WHERE is_active = 1");
    $total_products = $stmt->fetch()['total'];
    
    // Toplam sipariş
    $stmt = $db->query("SELECT COUNT(*) as total FROM orders");
    $total_orders = $stmt->fetch()['total'];
    
    // Bekleyen siparişler
    $stmt = $db->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
    $pending_orders = $stmt->fetch()['total'];
    
    // Son 5 sipariş
    $stmt = $db->query("
        SELECT o.*, s.name, s.surname 
        FROM orders o 
        LEFT JOIN students s ON o.student_id = s.user_id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $recent_orders = $stmt->fetchAll();
    
} catch(PDOException $e) {
    // Hata durumunda varsayılan değerler
    $total_students = 0;
    $total_products = 0;
    $total_orders = 0;
    $pending_orders = 0;
    $recent_orders = [];
}
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Okul Kantini</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.2/dist/full.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .sidebar { background: #1e293b; }
        .stat-card { 
            background: white;
            border-left: 4px solid;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); }
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
                <a href="/admin/dashboard.php" class="flex items-center px-6 py-3 bg-white/10 border-l-4 border-white">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Dashboard
                </a>
                <a href="/admin/products.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-white/10">
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
                <a href="/admin/logout.php" class="flex items-center text-red-400 hover:text-red-300">
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
                    <h2 class="text-2xl font-bold text-gray-800">Dashboard</h2>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-600">Hoşgeldin, Admin (<?= htmlspecialchars($_SESSION['phone']) ?>)</span>
                        <div class="avatar">
                            <div class="w-10 rounded-full bg-gradient-to-r from-red-500 to-purple-600">
                                <span class="flex items-center justify-center h-full text-white font-bold">A</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- İstatistikler -->
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <!-- Toplam Öğrenci -->
                    <div class="stat-card p-6 border-blue-500">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-gray-500 text-sm">Toplam Öğrenci</h3>
                                <p class="text-2xl font-bold text-gray-800"><?= $total_students ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Toplam Ürün -->
                    <div class="stat-card p-6 border-green-500">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-gray-500 text-sm">Aktif Ürün</h3>
                                <p class="text-2xl font-bold text-gray-800"><?= $total_products ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Toplam Sipariş -->
                    <div class="stat-card p-6 border-purple-500">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-gray-500 text-sm">Toplam Sipariş</h3>
                                <p class="text-2xl font-bold text-gray-800"><?= $total_orders ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Bekleyen Sipariş -->
                    <div class="stat-card p-6 border-yellow-500">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-yellow-100 rounded-lg p-3">
                                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-gray-500 text-sm">Bekleyen Sipariş</h3>
                                <p class="text-2xl font-bold text-gray-800"><?= $pending_orders ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Son Siparişler -->
                <div class="card bg-white">
                    <div class="card-body">
                        <h3 class="text-xl font-bold mb-4">Son Siparişler</h3>
                        <div class="overflow-x-auto">
                            <table class="table table-zebra">
                                <thead>
                                    <tr>
                                        <th>Sipariş No</th>
                                        <th>Öğrenci</th>
                                        <th>Tutar</th>
                                        <th>Durum</th>
                                        <th>Tarih</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($recent_orders && count($recent_orders) > 0): ?>
                                        <?php foreach($recent_orders as $order): ?>
                                            <tr>
                                                <td class="font-mono"><?= htmlspecialchars($order['order_number'] ?? '#' . $order['id']) ?></td>
                                                <td><?= htmlspecialchars(($order['name'] ?? 'Bilinmeyen') . ' ' . ($order['surname'] ?? '')) ?></td>
                                                <td class="font-bold"><?= number_format($order['total_amount'], 2) ?> ₺</td>
                                                <td>
                                                    <?php 
                                                    $status = $order['status'] ?? 'pending';
                                                    $badges = [
                                                        'pending' => '<span class="badge badge-warning">Bekliyor</span>',
                                                        'confirmed' => '<span class="badge badge-info">Onaylandı</span>',
                                                        'preparing' => '<span class="badge badge-primary">Hazırlanıyor</span>',
                                                        'ready' => '<span class="badge badge-success">Hazır</span>',
                                                        'delivered' => '<span class="badge badge-success">Teslim Edildi</span>',
                                                        'cancelled' => '<span class="badge badge-error">İptal</span>'
                                                    ];
                                                    echo $badges[$status] ?? '<span class="badge">Bilinmeyen</span>';
                                                    ?>
                                                </td>
                                                <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                                                <td>
                                                    <a href="/admin/orders.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-primary">Detay</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-gray-500">Henüz sipariş bulunmuyor</td>
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
