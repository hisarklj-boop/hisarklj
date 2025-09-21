<?php
require_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: /login.php');
    exit;
}

$stmt = $db->prepare("SELECT s.*, u.phone FROM students s JOIN users u ON s.user_id = u.id WHERE s.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

// Sepet kontrolü
if(!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: /student/cart.php');
    exit;
}

// Sepet ürünlerini getir
$cart_items = [];
$total = 0;
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

// Ödeme işlemi
if(isset($_POST['process_payment'])) {
    $payment_method = $_POST['payment_method'];
    
    // KVKK ve şartlar kontrolü
    if(!isset($_POST['kvkk_accept']) || !isset($_POST['terms_accept'])) {
        $error = "KVKK ve Kullanıcı Şartlarını kabul etmelisiniz!";
    } else {
        // Kredi kartı ise ayrı sayfaya yönlendir
        if($payment_method == 'card') {
            $_SESSION['checkout_data'] = [
                'cart_items' => $cart_items,
                'total' => $total,
                'student' => $student
            ];
            header('Location: /student/credit-card.php');
            exit;
        }
        
        // EFT için sipariş oluştur
        try {
            $db->beginTransaction();
            
            $student_id = $_SESSION['user_id'];
            
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
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme - Okul Kantini</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.2/dist/full.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .sidebar { background: #1e293b; }
        .payment-option {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .payment-option:hover {
            border-color: #3b82f6;
        }
        .payment-option.selected {
            border-color: #3b82f6;
            background: #eff6ff;
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
                <div class="flex items-center">
                    <a href="/student/cart.php" class="btn btn-ghost btn-sm mr-4">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Sepete Dön
                    </a>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Ödeme</h2>
                        <p class="text-gray-600">Sipariş bilgilerinizi kontrol edin ve ödeme yapın</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <?php if(isset($error)): ?>
                <div class="alert alert-error mb-6">
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="grid lg:grid-cols-2 gap-8">
                        <!-- Sol Taraf - Bilgiler ve Ödeme -->
                        <div class="space-y-6">
                            <!-- Öğrenci Bilgileri -->
                            <div class="card bg-white shadow">
                                <div class="card-body">
                                    <h3 class="text-lg font-bold mb-4">Öğrenci Bilgileri</h3>
                                    
                                    <div class="space-y-4">
                                        <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                                            <div class="avatar mr-4">
                                                <div class="w-12 rounded-full">
                                                    <?php if($student['photo']): ?>
                                                        <img src="/uploads/profiles/<?= htmlspecialchars($student['photo']) ?>" alt="Profil">
                                                    <?php else: ?>
                                                        <div class="bg-blue-500 w-12 h-12 rounded-full flex items-center justify-center text-white font-bold">
                                                            <?= strtoupper(substr($student['name'],0,1)) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="font-bold"><?= htmlspecialchars($student['name'] . ' ' . $student['surname']) ?></div>
                                                <div class="text-sm text-gray-600"><?= htmlspecialchars($student['class']) ?></div>
                                                <div class="text-sm text-gray-600"><?= htmlspecialchars($student['phone']) ?></div>
                                            </div>
                                        </div>
                                        
                                        <?php if($student['address']): ?>
                                        <div>
                                            <label class="label">
                                                <span class="label-text font-semibold">Teslimat Adresi</span>
                                            </label>
                                            <textarea class="textarea textarea-bordered w-full" readonly><?= htmlspecialchars($student['address']) ?></textarea>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Ödeme Yöntemi -->
                            <div class="card bg-white shadow">
                                <div class="card-body">
                                    <h3 class="text-lg font-bold mb-4">Ödeme Yöntemi</h3>
                                    
                                    <div class="space-y-4">
                                        <!-- Havale/EFT -->
                                        <div class="payment-option selected" onclick="selectPayment('eft')">
                                            <label class="cursor-pointer">
                                                <input type="radio" name="payment_method" value="eft" class="radio radio-primary mr-3" checked>
                                                <span class="flex items-center">
                                                    <svg class="w-6 h-6 mr-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                                    </svg>
                                                    <div>
                                                        <div class="font-semibold">Havale / EFT</div>
                                                        <div class="text-sm text-gray-600">Banka hesabımıza havale yapın</div>
                                                    </div>
                                                </span>
                                            </label>
                                        </div>

                                        <!-- Kredi Kartı -->
                                        <div class="payment-option" onclick="selectPayment('card')">
                                            <label class="cursor-pointer">
                                                <input type="radio" name="payment_method" value="card" class="radio radio-primary mr-3">
                                                <span class="flex items-center">
                                                    <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                                    </svg>
                                                    <div>
                                                        <div class="font-semibold">Kredi Kartı</div>
                                                        <div class="text-sm text-gray-600">Kredi kartı ile ödeme yapın</div>
                                                    </div>
                                                </span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Havale Bilgileri -->
                                    <div id="eftDetails" class="mt-6 p-4 bg-green-50 rounded-lg">
                                        <h4 class="font-semibold text-green-800 mb-2">💳 Banka Hesap Bilgileri</h4>
                                        <div class="text-sm space-y-1">
                                            <div><strong>Banka:</strong> Türkiye İş Bankası</div>
                                            <div><strong>Hesap Adı:</strong> Okul Kantini Ltd. Şti.</div>
                                            <div><strong>IBAN:</strong> TR33 0006 4000 0011 2345 6789 01</div>
                                            <div><strong>Tutar:</strong> <?= number_format($total, 2) ?> ₺</div>
                                        </div>
                                        <div class="mt-2 text-xs text-green-700">
                                            ⚠️ Havale yaparken açıklama kısmına sipariş numaranızı yazacağız.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- KVKK ve Şartlar -->
                            <div class="card bg-white shadow">
                                <div class="card-body">
                                    <h3 class="text-lg font-bold mb-4">Sözleşmeler ve Onaylar</h3>
                                    
                                    <div class="space-y-4">
                                        <div class="form-control">
                                            <label class="label cursor-pointer justify-start">
                                                <input type="checkbox" name="kvkk_accept" class="checkbox checkbox-primary mr-3" required>
                                                <span class="label-text">
                                                    <a href="#" onclick="showKVKK()" class="link link-primary">KVKK Aydınlatma Metni</a>'ni okudum ve kabul ediyorum
                                                </span>
                                            </label>
                                        </div>
                                        
                                        <div class="form-control">
                                            <label class="label cursor-pointer justify-start">
                                                <input type="checkbox" name="terms_accept" class="checkbox checkbox-primary mr-3" required>
                                                <span class="label-text">
                                                    <a href="#" onclick="showTerms()" class="link link-primary">Kullanıcı Şartları</a>'nı okudum ve kabul ediyorum
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sağ Taraf - Sipariş Özeti -->
                        <div>
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
                                    
                                    <button type="submit" name="process_payment" class="btn btn-primary w-full mt-6">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                        Ödemeyi Tamamla
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- KVKK Modal -->
    <dialog id="kvkkModal" class="modal">
        <div class="modal-box max-w-2xl">
            <h3 class="font-bold text-lg mb-4">KVKK Aydınlatma Metni</h3>
            <div class="space-y-4 text-sm">
                <p><strong>1. Veri Sorumlusu:</strong> Okul Kantini Ltd. Şti. olarak kişisel verilerinizi işlemekteyiz.</p>
                <p><strong>2. Kişisel Verilerin İşlenme Amacı:</strong> Sipariş süreçlerinizin yürütülmesi, müşteri hizmetleri ve yasal yükümlülüklerin yerine getirilmesi amacıyla işlenmektedir.</p>
                <p><strong>3. İşlenen Kişisel Veriler:</strong> Ad, soyad, telefon, e-posta, adres bilgileri işlenmektedir.</p>
                <p><strong>4. Kişisel Verilerin Aktarıldığı Üçüncü Kişiler:</strong> Verileriniz yalnızca hizmet sağlayıcılarımızla paylaşılmaktadır.</p>
                <p><strong>5. Haklarınız:</strong> Verilerinize erişim, düzeltme, silme ve işlemeye itiraz etme haklarınız bulunmaktadır.</p>
            </div>
            <div class="modal-action">
                <button type="button" onclick="document.getElementById('kvkkModal').close()" class="btn">Kapat</button>
            </div>
        </div>
    </dialog>

    <!-- Kullanıcı Şartları Modal -->
    <dialog id="termsModal" class="modal">
        <div class="modal-box max-w-2xl">
            <h3 class="font-bold text-lg mb-4">Kullanıcı Şartları</h3>
            <div class="space-y-4 text-sm">
                <p><strong>1. Genel Şartlar:</strong> Bu platformu kullanarak aşağıdaki şartları kabul etmiş sayılırsınız.</p>
                <p><strong>2. Sipariş Koşulları:</strong> Verilen siparişler onay sonrası kesinleşir ve iptal edilemez.</p>
                <p><strong>3. Ödeme Şartları:</strong> Ödemeler güvenli yöntemlerle alınır, kredi kartı bilgileri saklanmaz.</p>
                <p><strong>4. Teslimat:</strong> Ürünler okul içerisinde teslim edilir, kargo ücreti alınmaz.</p>
                <p><strong>5. Sorumluluk:</strong> Platform kullanımından doğan sorumluluk kullanıcıya aittir.</p>
            </div>
            <div class="modal-action">
                <button type="button" onclick="document.getElementById('termsModal').close()" class="btn">Kapat</button>
            </div>
        </div>
    </dialog>

    <script>
    function selectPayment(type) {
        const options = document.querySelectorAll('.payment-option');
        options.forEach(option => option.classList.remove('selected'));
        
        if(type === 'eft') {
            document.querySelector('input[value="eft"]').checked = true;
            document.querySelector('input[value="eft"]').closest('.payment-option').classList.add('selected');
            document.getElementById('eftDetails').style.display = 'block';
        } else {
            document.querySelector('input[value="card"]').checked = true;
            document.querySelector('input[value="card"]').closest('.payment-option').classList.add('selected');
            document.getElementById('eftDetails').style.display = 'none';
        }
    }

    function showKVKK() {
        document.getElementById('kvkkModal').showModal();
    }

    function showTerms() {
        document.getElementById('termsModal').showModal();
    }
    </script>
</body>
</html>
