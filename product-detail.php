<?php
session_start();
require_once 'config/database.php';

$product_id = $_GET['id'] ?? 0;

// Ürün bilgilerini çek
$stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if(!$product) {
    header('Location: /');
    exit;
}

// Ürün resimlerini çek
$stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
$stmt->execute([$product_id]);
$images = $stmt->fetchAll();

// Varyasyonları çöz
$variations = json_decode($product['variations'], true);
$sizes = $variations['sizes'] ?? [];
$colors = $variations['colors'] ?? [];
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
        .navbar-gradient { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        
        .main-image {
            aspect-ratio: 1;
            object-fit: cover;
            width: 100%;
            height: 100%;
        }
        
        .thumb-image {
            aspect-ratio: 1;
            object-fit: cover;
            cursor: pointer;
            transition: all 0.3s;
            border: 3px solid transparent;
        }
        
        .thumb-image:hover {
            border-color: #667eea;
        }
        
        .thumb-image.active {
            border-color: #764ba2;
        }
        
        .variation-btn {
            min-width: 60px;
            padding: 8px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .variation-btn:hover {
            border-color: #667eea;
        }
        
        .variation-btn.selected {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .color-option {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 3px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        
        .color-option:hover {
            transform: scale(1.1);
        }
        
        .color-option.selected {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }
        
        .color-option.selected::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-weight: bold;
            text-shadow: 0 0 3px rgba(0,0,0,0.5);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <div class="navbar navbar-gradient text-white shadow-lg">
        <div class="container mx-auto">
            <div class="flex-1">
                <a href="/" class="btn btn-ghost text-xl">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    Okul Kantini
                </a>
            </div>
            
            <?php if(isset($_SESSION['user_id'])): ?>
            <div class="flex-none gap-4">
                <a href="/cart.php" class="btn btn-ghost btn-circle">
                    <div class="indicator">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span id="cart-count" class="badge badge-sm indicator-item bg-red-500 text-white">0</span>
                    </div>
                </a>
                
                <div class="dropdown dropdown-end">
                    <label tabindex="0" class="btn btn-ghost btn-circle avatar">
                        <div class="w-10 rounded-full bg-white/20 flex items-center justify-center">
                            <span class="text-xl font-bold"><?= substr($_SESSION['name'] ?? 'U', 0, 1) ?></span>
                        </div>
                    </label>
                    <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52 text-gray-800">
                        <li><a href="/profile.php">Profil</a></li>
                        <li><a href="/orders.php">Siparişlerim</a></li>
                        <li><a href="/logout.php">Çıkış Yap</a></li>
                    </ul>
                </div>
            </div>
            <?php else: ?>
            <div class="flex-none">
                <a href="/login.php" class="btn btn-ghost">Giriş Yap</a>
                <a href="/register.php" class="btn btn-primary">Kayıt Ol</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Breadcrumb -->
    <div class="container mx-auto px-4 py-4">
        <div class="breadcrumbs text-sm">
            <ul>
                <li><a href="/">Ana Sayfa</a></li>
                <li><?= htmlspecialchars($product['title']) ?></li>
            </ul>
        </div>
    </div>

    <!-- Ürün Detay -->
    <div class="container mx-auto px-4 pb-8">
        <div class="grid lg:grid-cols-2 gap-8">
            <!-- Sol - Resimler -->
            <div>
                <div class="card bg-white shadow-xl">
                    <div class="card-body p-4">
                        <?php if(!empty($images)): ?>
                        <div class="mb-4">
                            <img id="mainImage" src="/uploads/products/<?= htmlspecialchars($images[0]['image_path']) ?>" 
                                 alt="<?= htmlspecialchars($product['title']) ?>" 
                                 class="main-image rounded-lg">
                        </div>
                        
                        <?php if(count($images) > 1): ?>
                        <div class="grid grid-cols-4 gap-2">
                            <?php foreach($images as $index => $img): ?>
                            <img src="/uploads/products/<?= htmlspecialchars($img['image_path']) ?>" 
                                 alt="<?= htmlspecialchars($product['title']) ?>" 
                                 class="thumb-image rounded-lg <?= $index == 0 ? 'active' : '' ?>"
                                 onclick="changeImage(this)">
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <?php else: ?>
                        <div class="aspect-square bg-gray-100 rounded-lg flex items-center justify-center">
                            <svg class="w-24 h-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sağ - Ürün Bilgileri -->
            <div>
                <div class="card bg-white shadow-xl">
                    <div class="card-body">
                        <h1 class="text-3xl font-bold mb-2"><?= htmlspecialchars($product['title']) ?></h1>
                        <p class="text-gray-500 mb-4">Ürün Kodu: <?= htmlspecialchars($product['code']) ?></p>
                        
                        <div class="flex items-baseline gap-2 mb-4">
                            <span class="text-4xl font-bold text-primary">₺<?= number_format($product['price'], 2) ?></span>
                        </div>

                        <?php if(!empty($product['description'])): ?>
                        <div class="prose max-w-none mb-6">
                            <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- Varyasyonlar -->
                        <?php if(!empty($sizes)): ?>
                        <div class="mb-6">
                            <label class="label">
                                <span class="label-text font-semibold">Beden Seçiniz:</span>
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach($sizes as $size): ?>
                                <button type="button" class="variation-btn size-option" data-size="<?= htmlspecialchars($size) ?>" onclick="selectSize(this)">
                                    <?= htmlspecialchars($size) ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if(!empty($colors)): ?>
                        <div class="mb-6">
                            <label class="label">
                                <span class="label-text font-semibold">Renk Seçiniz:</span>
                            </label>
                            <div class="flex flex-wrap gap-3">
                                <?php 
                                $colorMap = [
                                    'Siyah' => '#000000',
                                    'Beyaz' => '#FFFFFF',
                                    'Kırmızı' => '#EF4444',
                                    'Mavi' => '#3B82F6',
                                    'Yeşil' => '#10B981',
                                    'Sarı' => '#F59E0B',
                                    'Gri' => '#6B7280',
                                    'Lacivert' => '#1E3A8A',
                                    'Pembe' => '#EC4899',
                                    'Kahverengi' => '#92400E'
                                ];
                                foreach($colors as $color): 
                                    $hex = $colorMap[$color] ?? '#e5e7eb';
                                ?>
                                <div class="text-center">
                                    <div class="color-option" 
                                         style="background-color: <?= $hex ?>;" 
                                         data-color="<?= htmlspecialchars($color) ?>"
                                         onclick="selectColor(this)"
                                         title="<?= htmlspecialchars($color) ?>">
                                    </div>
                                    <small class="text-xs mt-1"><?= htmlspecialchars($color) ?></small>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Stok Durumu -->
                        <div class="alert <?= $product['stock'] > 0 ? 'alert-success' : 'alert-error' ?> mb-4">
                            <?php if($product['stock'] > 0): ?>
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Stokta var (<?= $product['stock'] ?> adet)</span>
                            <?php else: ?>
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                <span>Stokta yok</span>
                            <?php endif; ?>
                        </div>

                        <!-- Adet ve Sepet -->
                        <div class="flex items-center gap-4 mb-4">
                            <label class="font-medium">Adet:</label>
                            <div class="join">
                                <button class="btn btn-sm join-item" onclick="changeQuantity(-1)">-</button>
                                <input type="number" id="quantity" value="1" min="1" max="<?= $product['stock'] ?>" 
                                       class="input input-sm input-bordered join-item w-16 text-center" readonly>
                                <button class="btn btn-sm join-item" onclick="changeQuantity(1)">+</button>
                            </div>
                        </div>
                        
                        <?php if($product['stock'] > 0): ?>
                            <?php if(isset($_SESSION['user_id'])): ?>
                            <button onclick="addToCart()" class="btn btn-primary btn-lg w-full">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                Sepete Ekle
                            </button>
                            <?php else: ?>
                            <a href="/login.php" class="btn btn-primary btn-lg w-full">
                                Giriş Yapın
                            </a>
                            <?php endif; ?>
                        <?php else: ?>
                        <button class="btn btn-disabled btn-lg w-full">Stokta Yok</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    let selectedSize = null;
    let selectedColor = null;
    
    function changeImage(img) {
        document.getElementById('mainImage').src = img.src;
        
        document.querySelectorAll('.thumb-image').forEach(i => {
            i.classList.remove('active');
        });
        img.classList.add('active');
    }
    
    function selectSize(btn) {
        document.querySelectorAll('.size-option').forEach(b => {
            b.classList.remove('selected');
        });
        btn.classList.add('selected');
        selectedSize = btn.dataset.size;
    }
    
    function selectColor(div) {
        document.querySelectorAll('.color-option').forEach(d => {
            d.classList.remove('selected');
        });
        div.classList.add('selected');
        selectedColor = div.dataset.color;
    }
    
    function changeQuantity(delta) {
        const input = document.getElementById('quantity');
        const newValue = parseInt(input.value) + delta;
        const max = parseInt(input.max);
        
        if(newValue >= 1 && newValue <= max) {
            input.value = newValue;
        }
    }
    
    function addToCart() {
        const quantity = document.getElementById('quantity').value;
        
        <?php if(!empty($sizes)): ?>
        if(!selectedSize) {
            alert('Lütfen beden seçiniz!');
            return;
        }
        <?php endif; ?>
        
        <?php if(!empty($colors)): ?>
        if(!selectedColor) {
            alert('Lütfen renk seçiniz!');
            return;
        }
        <?php endif; ?>
        
        const data = {
            product_id: <?= $product_id ?>,
            quantity: quantity,
            size: selectedSize,
            color: selectedColor
        };
        
        fetch('/api/cart.php?action=add', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                updateCartCount();
                alert('Ürün sepete eklendi!');
            } else {
                alert(data.message || 'Bir hata oluştu');
            }
        })
        .catch(error => {
            console.error('Hata:', error);
            alert('Ürün sepete eklenemedi!');
        });
    }
    
    function updateCartCount() {
        fetch('/api/cart.php?action=count')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('cart-count');
                if(badge) {
                    badge.textContent = data.count || 0;
                }
            })
            .catch(error => {
                console.error('Cart count error:', error);
            });
    }
    
    // Sayfa yüklendiğinde sepet sayısını güncelle
    if(document.getElementById('cart-count')) {
        updateCartCount();
    }
    </script>
</body>
</html>
