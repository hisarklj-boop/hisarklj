<?php
require_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: /login.php');
    exit;
}

$product_id = $_GET['id'] ?? 0;

$stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if(!$product) {
    header('Location: /student/dashboard.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC");
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
        .sidebar { background: #1e293b; }
        .main-image { width: 100%; height: 400px; object-fit: cover; border-radius: 12px; cursor: pointer; }
        .thumbnail-container { position: relative; margin-top: 1rem; overflow: hidden; }
        .thumbnail-wrapper { display: flex; gap: 0.5rem; transition: transform 0.3s; }
        .thumbnail { flex-shrink: 0; width: 80px; height: 80px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid #e5e7eb; transition: all 0.3s; }
        .thumbnail:hover { border-color: #3b82f6; }
        .thumbnail.active { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2); }
        .nav-btn { position: absolute; top: 50%; transform: translateY(-50%); width: 30px; height: 30px; background: white; border: 1px solid #ddd; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 10; }
        .nav-btn.prev { left: -15px; }
        .nav-btn.next { right: -15px; }
        .nav-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        
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
            border-color: #3b82f6;
        }
        
        .variation-btn.selected {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
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
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
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
                    <span id="cart-count" class="badge badge-primary badge-sm ml-2">0</span>
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
                    <a href="/student/dashboard.php" class="btn btn-ghost btn-sm mr-4">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Geri
                    </a>
                    <h2 class="text-2xl font-bold text-gray-800">Ürün Detayı</h2>
                </div>
            </div>

            <div class="p-6">
                <div class="max-w-6xl mx-auto">
                    <div class="grid lg:grid-cols-2 gap-8">
                        <div class="space-y-4">
                            <div class="bg-white p-4 rounded-lg shadow">
                                <?php if($images && $images[0]['image_path']): ?>
                                    <img id="mainImage" src="/uploads/products/<?= htmlspecialchars($images[0]['image_path']) ?>" alt="<?= htmlspecialchars($product['title']) ?>" class="main-image">
                                <?php else: ?>
                                    <div class="main-image bg-gray-200 flex items-center justify-center">
                                        <svg class="w-24 h-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                <?php endif; ?>

                                <?php if(count($images) > 1): ?>
                                    <div class="thumbnail-container">
                                        <?php if(count($images) > 6): ?>
                                            <button class="nav-btn prev" onclick="slide(-1)">‹</button>
                                            <button class="nav-btn next" onclick="slide(1)">›</button>
                                        <?php endif; ?>
                                        
                                        <div class="thumbnail-wrapper" id="thumbnailWrapper">
                                            <?php foreach($images as $key => $image): ?>
                                                <img src="/uploads/products/<?= htmlspecialchars($image['image_path']) ?>" alt="<?= htmlspecialchars($product['title']) ?>" class="thumbnail <?= $key === 0 ? 'active' : '' ?>" onclick="changeImage('<?= htmlspecialchars($image['image_path']) ?>', this)">
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="card bg-white shadow">
                                <div class="card-body">
                                    <h1 class="text-3xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($product['title']) ?></h1>
                                    <p class="text-gray-600 mb-4">Ürün Kodu: <span class="badge badge-outline"><?= htmlspecialchars($product['code']) ?></span></p>

                                    <div class="text-4xl font-bold text-blue-600 mb-6"><?= number_format($product['price'], 2) ?> ₺</div>

                                    <?php if($product['description']): ?>
                                        <div class="mb-6">
                                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Açıklama</h3>
                                            <p class="text-gray-600"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
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

                                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-600">Stok:</span>
                                            <span class="<?= $product['stock'] > 0 ? 'text-green-600' : 'text-red-600' ?> font-semibold">
                                                <?= $product['stock'] > 0 ? $product['stock'] . ' adet' : 'Stokta yok' ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        <div class="flex items-center space-x-4">
                                            <label class="text-gray-600 font-medium">Adet:</label>
                                            <div class="flex items-center">
                                                <button type="button" class="btn btn-outline btn-sm" onclick="changeQty(-1)">-</button>
                                                <input type="number" id="quantity" class="input input-bordered w-20 text-center mx-2" value="1" min="1" max="<?= $product['stock'] ?>">
                                                <button type="button" class="btn btn-outline btn-sm" onclick="changeQty(1)">+</button>
                                            </div>
                                        </div>

                                        <?php if($product['stock'] > 0): ?>
                                            <button class="btn btn-primary w-full" onclick="addCart()">
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5M17 13v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6"/></svg>
                                                Sepete Ekle
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-disabled w-full">Stokta Yok</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <dialog id="modal" class="modal">
        <div class="modal-box max-w-4xl">
            <form method="dialog">
                <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
            </form>
            <img id="modalImg" src="" alt="" class="w-full">
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <script>
        let slideIndex = 0;
        const maxVisible = 6;
        let selectedSize = null;
        let selectedColor = null;
        
        function changeImage(src, thumb) {
            document.getElementById('mainImage').src = '/uploads/products/' + src;
            document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
            thumb.classList.add('active');
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
        
        function slide(direction) {
            const wrapper = document.getElementById('thumbnailWrapper');
            const thumbs = document.querySelectorAll('.thumbnail');
            const maxSlide = Math.max(0, thumbs.length - maxVisible);
            
            slideIndex += direction;
            if(slideIndex < 0) slideIndex = 0;
            if(slideIndex > maxSlide) slideIndex = maxSlide;
            
            wrapper.style.transform = `translateX(-${slideIndex * 88}px)`;
            
            document.querySelector('.nav-btn.prev').disabled = slideIndex === 0;
            document.querySelector('.nav-btn.next').disabled = slideIndex === maxSlide;
        }
        
        function changeQty(change) {
            const input = document.getElementById('quantity');
            let value = parseInt(input.value) + change;
            const max = parseInt(input.max);
            
            if(value < 1) value = 1;
            if(value > max) value = max;
            
            input.value = value;
        }
        
        function addCart() {
            const qty = document.getElementById('quantity').value;
            
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
                quantity: qty,
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
                alert('Sepete eklenemedi!');
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
        
        document.getElementById('mainImage').onclick = function() {
            document.getElementById('modalImg').src = this.src;
            document.getElementById('modal').showModal();
        };
        
        document.addEventListener('DOMContentLoaded', function() {
            const thumbs = document.querySelectorAll('.thumbnail');
            if(thumbs.length <= maxVisible) {
                document.querySelectorAll('.nav-btn').forEach(btn => btn.style.display = 'none');
            } else {
                document.querySelector('.nav-btn.prev').disabled = true;
            }
            
            updateCartCount();
        });
    </script>
</body>
</html>
