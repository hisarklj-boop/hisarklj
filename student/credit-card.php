<?php
require_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: /login.php');
    exit;
}

// Checkout data kontrolü
if(!isset($_SESSION['checkout_data'])) {
    header('Location: /student/cart.php');
    exit;
}

$checkout_data = $_SESSION['checkout_data'];
$cart_items = $checkout_data['cart_items'];
$total = $checkout_data['total'];
$student = $checkout_data['student'];

// Kredi kartı ödeme işlemi
if(isset($_POST['process_card_payment'])) {
    try {
        $db->beginTransaction();
        
        $student_id = $_SESSION['user_id'];
        
        // Sipariş numarası oluştur
        $order_number = 'ORD' . date('Ymd') . rand(1000, 9999);
        
        // Siparişi veritabanına kaydet
        $stmt = $db->prepare("
            INSERT INTO orders (student_id, order_number, total_amount, payment_method, status, created_at) 
            VALUES (?, ?, ?, 'card', 'pending', NOW())
        ");
        $stmt->execute([$student_id, $order_number, $total]);
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
        
        // Sepeti ve checkout data'yı temizle
        unset($_SESSION['cart']);
        unset($_SESSION['checkout_data']);
        
        // Sonuç sayfasına yönlendir
        header('Location: /student/order-success.php?order=' . $order_number);
        exit;
        
    } catch(Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kredi Kartı Ödeme - Okul Kantini</title>
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
                <a href="/student/cart.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-white/10">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5M17 13v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6"/></svg>
                    Sepetim
                </a>
                <a href="/student/orders.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-white/10">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    Siparişlerim
                </a>
            </nav>
        </div>

        <div class="flex-1 overflow-y-auto">
            <div class="bg-white shadow-sm px-6 py-4">
                <div class="flex items-center">
                    <a href="/student/checkout.php" class="btn btn-ghost btn-sm mr-4">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Geri Dön
                    </a>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Kredi Kartı ile Ödeme</h2>
                        <p class="text-gray-600">Kart bilgilerinizi güvenle girebilirsiniz</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <?php if(isset($error)): ?>
                <div class="alert alert-error mb-6">
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
                <?php endif; ?>

                <div class="grid lg:grid-cols-2 gap-8">
                    <!-- Kredi Kartı Formu -->
                    <div class="card bg-white shadow-xl">
                        <div class="card-body">
                            <h3 class="text-lg font-bold mb-4 flex items-center">
                                <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                                Kart Bilgileri
                            </h3>

                            <form method="POST" class="space-y-4">
                                <div>
                                    <label class="label">
                                        <span class="label-text font-semibold">Kart Numarası</span>
                                    </label>
                                    <input type="text" name="card_number" placeholder="1234 5678 9012 3456" class="input input-bordered w-full" maxlength="19" required>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="label">
                                            <span class="label-text font-semibold">Son Kullanma</span>
                                        </label>
                                        <input type="text" name="expiry" placeholder="MM/YY" class="input input-bordered w-full" maxlength="5" required>
                                    </div>
                                    
                                    <div>
                                        <label class="label">
                                            <span class="label-text font-semibold">CVV</span>
                                        </label>
                                        <input type="text" name="cvv" placeholder="123" class="input input-bordered w-full" maxlength="3" required>
                                    </div>
                                </div>

                                <div>
                                    <label class="label">
                                        <span class="label-text font-semibold">Kart Sahibinin Adı</span>
                                    </label>
                                    <input type="text" name="card_name" placeholder="AD SOYAD" class="input input-bordered w-full" required>
                                </div>

                                <div class="alert alert-info">
                                    <svg class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span>Kart bilgileriniz SSL ile şifrelenerek güvenle işlenir. Bilgileriniz saklanmaz.</span>
                                </div>

                                <button type="submit" name="process_card_payment" class="btn btn-success w-full btn-lg">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    <?= number_format($total, 2) ?> ₺ Ödeme Yap
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Sipariş Özeti -->
                    <div class="card bg-white shadow sticky top-6">
                        <div class="card-body">
                            <h3 class="text-lg font-bold mb-4">Sipariş Özeti</h3>
                            
                            <div class="space-y-3 mb-4">
                                <?php foreach($cart_items as $item): ?>
                                <div class="flex items-center justify-between py-2 border-b">
                                    <div class="flex items-center">
                                        <div class="w-12 h-12 bg-gray-200 rounded mr-3">
                                            <?php if($item['product']['main_image']): ?>
                                                <img src="/uploads/products/<?= htmlspecialchars($item['product']['main_image']) ?>" alt="" class="w-full h-full object-cover rounded">
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="font-medium text-sm"><?= htmlspecialchars($item['product']['title']) ?></div>
                                            <div class="text-xs text-gray-600"><?= $item['quantity'] ?> x <?= number_format($item['product']['price'], 2) ?> ₺</div>
                                        </div>
                                    </div>
                                    <div class="font-semibold"><?= number_format($item['subtotal'], 2) ?> ₺</div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="space-y-2 border-t pt-4">
                                <div class="flex justify-between">
                                    <span>Ara Toplam:</span>
                                    <span><?= number_format($total, 2) ?> ₺</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Kargo:</span>
                                    <span class="text-green-600">Ücretsiz</span>
                                </div>
                                <div class="flex justify-between text-lg font-bold border-t pt-2">
                                    <span>Toplam:</span>
                                    <span class="text-blue-600"><?= number_format($total, 2) ?> ₺</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Kart numarası formatlama
    document.querySelector('input[name="card_number"]').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
        e.target.value = formattedValue;
    });

    // Expiry formatlama
    document.querySelector('input[name="expiry"]').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        e.target.value = value;
    });

    // CVV sadece rakam
    document.querySelector('input[name="cvv"]').addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/[^0-9]/g, '');
    });
    </script>
</body>
</html>
