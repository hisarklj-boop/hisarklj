<?php
require_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: /admin/login.php');
    exit;
}

$product_id = $_GET['id'] ?? 0;

$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if(!$product) {
    header('Location: /admin/products.php');
    exit;
}

// Resimleri çek
$stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC");
$stmt->execute([$product_id]);
$images = $stmt->fetchAll();

// Varyasyonları çöz
$variations = json_decode($product['variations'] ?? '{}', true);
$selected_sizes = $variations['sizes'] ?? [];
$selected_colors = $variations['colors'] ?? [];

// Sınıfları çöz
$allowed_classes = json_decode($product['allowed_classes'] ?? '[]', true);

// Sınıf listesi
$all_classes = [
    'Anaokulu', 'Kreş',
    '1. Sınıf', '2. Sınıf', '3. Sınıf', '4. Sınıf', '5. Sınıf',
    '6. Sınıf', '7. Sınıf', '8. Sınıf', '9. Sınıf', '10. Sınıf',
    '11. Sınıf', '12. Sınıf'
];

// AJAX - Resim sırası güncelleme (SWAP)
if(isset($_POST['ajax_swap'])) {
    header('Content-Type: application/json');
    
    try {
        $img1_id = $_POST['img1_id'];
        $img2_id = $_POST['img2_id'];
        
        $stmt = $db->prepare("SELECT id, sort_order FROM product_images WHERE id IN (?, ?) AND product_id = ?");
        $stmt->execute([$img1_id, $img2_id, $product_id]);
        $swap_images = [];
        while($row = $stmt->fetch()) {
            $swap_images[$row['id']] = $row['sort_order'];
        }
        
        if(count($swap_images) == 2) {
            $db->beginTransaction();
            
            $order1 = $swap_images[$img1_id];
            $order2 = $swap_images[$img2_id];
            
            $db->prepare("UPDATE product_images SET sort_order = ? WHERE id = ?")->execute([$order2, $img1_id]);
            $db->prepare("UPDATE product_images SET sort_order = ? WHERE id = ?")->execute([$order1, $img2_id]);
            
            $db->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?")->execute([$product_id]);
            $db->prepare("UPDATE product_images SET is_primary = 1 WHERE product_id = ? ORDER BY sort_order ASC LIMIT 1")->execute([$product_id]);
            
            $db->commit();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Resimler bulunamadı']);
        }
    } catch(Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Resim silme
if(isset($_GET['delete_img'])) {
    try {
        $db->beginTransaction();
        
        $img_id = $_GET['delete_img'];
        
        $stmt = $db->prepare("SELECT image_path FROM product_images WHERE id = ? AND product_id = ?");
        $stmt->execute([$img_id, $product_id]);
        $img = $stmt->fetch();
        
        if($img) {
            $file = '../uploads/products/' . $img['image_path'];
            if(file_exists($file)) {
                unlink($file);
            }
            
            $stmt = $db->prepare("DELETE FROM product_images WHERE id = ?");
            $stmt->execute([$img_id]);
            
            $stmt = $db->prepare("SELECT id FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
            $stmt->execute([$product_id]);
            $remaining = $stmt->fetchAll();
            
            foreach($remaining as $index => $row) {
                $is_primary = ($index == 0) ? 1 : 0;
                $db->prepare("UPDATE product_images SET sort_order = ?, is_primary = ? WHERE id = ?")
                   ->execute([$index, $is_primary, $row['id']]);
            }
        }
        
        $db->commit();
        header('Location: /admin/product-edit.php?id=' . $product_id);
        exit;
        
    } catch(Exception $e) {
        $db->rollBack();
        $error = "Silme hatası: " . $e->getMessage();
    }
}

// Form submit
if(isset($_POST['submit'])) {
    try {
        $db->beginTransaction();
        
        $code = trim($_POST['code']);
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if($price > 99999999.99) {
            throw new Exception("Fiyat çok yüksek!");
        }
        
        $variations = [];
        if(isset($_POST['sizes']) && is_array($_POST['sizes'])) {
            $variations['sizes'] = $_POST['sizes'];
        }
        if(isset($_POST['colors']) && is_array($_POST['colors'])) {
            $variations['colors'] = $_POST['colors'];
        }
        $variations_json = !empty($variations) ? json_encode($variations, JSON_UNESCAPED_UNICODE) : null;
        
        // Sınıflar
        $classes = [];
        if(isset($_POST['classes']) && is_array($_POST['classes'])) {
            $classes = $_POST['classes'];
        }
        $classes_json = !empty($classes) ? json_encode($classes, JSON_UNESCAPED_UNICODE) : null;
        
        $sql = "UPDATE products SET code = ?, title = ?, description = ?, price = ?, stock = ?, is_active = ?, variations = ?, allowed_classes = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$code, $title, $description, $price, $stock, $is_active, $variations_json, $classes_json, $product_id]);
        
        // Mevcut resim sayısı
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM product_images WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $current_count = $stmt->fetch()['count'];
        
        // Yeni resimler
        if(!empty($_FILES['final_images']['name'][0])) {
            $upload_dir = '../uploads/products/';
            
            foreach($_FILES['final_images']['name'] as $key => $name) {
                if($current_count >= 30) break;
                if(empty($name)) continue;
                if($_FILES['final_images']['error'][$key] != 0) continue;
                
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if(!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) continue;
                
                $new_name = 'p' . $product_id . '_' . uniqid() . '.' . $ext;
                $target = $upload_dir . $new_name;
                
                if(move_uploaded_file($_FILES['final_images']['tmp_name'][$key], $target)) {
                    $is_primary = ($current_count == 0) ? 1 : 0;
                    $db->prepare("INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES (?, ?, ?, ?)")
                       ->execute([$product_id, $new_name, $is_primary, $current_count]);
                    $current_count++;
                }
            }
        }
        
        $db->commit();
        header('Location: /admin/product-edit.php?id=' . $product_id . '&success=1');
        exit;
        
    } catch(Exception $e) {
        $db->rollBack();
        $error = "Güncelleme hatası: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ürün Düzenle - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.2/dist/full.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .sidebar { background: #1e293b; }
        
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 8px;
            min-height: 150px;
        }
        
        .image-item {
            position: relative;
            aspect-ratio: 1;
            background: #f3f4f6;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            cursor: move;
            transition: all 0.2s ease;
        }
        
        .image-item.removing {
            animation: fadeOut 0.3s ease forwards;
        }
        
        @keyframes fadeOut {
            to {
                opacity: 0;
                transform: scale(0.8);
            }
        }
        
        .image-item:hover {
            border-color: #3b82f6;
            transform: scale(1.05);
        }
        
        .image-item.sortable-swap-highlight {
            background: #dbeafe;
            border-color: #2563eb;
        }
        
        .image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            pointer-events: none;
            user-select: none;
            opacity: 0;
            animation: fadeIn 0.4s ease forwards;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        .image-item .delete-btn {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.2s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-decoration: none;
        }
        
        .image-item:hover .delete-btn {
            opacity: 1;
        }
        
        .delete-btn:hover {
            background: rgba(255, 255, 255, 1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .delete-btn svg {
            width: 20px;
            height: 20px;
        }
        
        .image-item .primary-badge {
            position: absolute;
            bottom: 5px;
            left: 5px;
            width: 10px;
            height: 10px;
            background: #ef4444;
            border-radius: 50%;
            border: 2px solid white;
        }
        
        .variation-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 0.5rem;
        }
        
        .variation-item {
            padding: 0.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .variation-item:hover {
            border-color: #3b82f6;
        }
        
        .variation-item.selected {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        .class-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 0.5rem;
        }
        
        .class-item {
            padding: 0.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
        }
        
        .class-item:hover {
            border-color: #10b981;
        }
        
        .class-item.selected {
            background: #10b981;
            color: white;
            border-color: #10b981;
        }
        
        #toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: none;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Toast Mesaj -->
    <div id="toast" class="alert alert-success shadow-lg">
        <span id="toastMessage">İşlem başarılı!</span>
    </div>
    
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
            <div class="bg-white shadow-sm px-6 py-4">
                <div class="flex items-center">
                    <a href="/admin/products.php" class="btn btn-ghost btn-sm mr-4">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Geri
                    </a>
                    <h2 class="text-2xl font-bold text-gray-800">Ürün Düzenle</h2>
                </div>
            </div>

            <div class="p-6">
                <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success mb-4">
                    <span>Ürün güncellendi!</span>
                </div>
                <?php endif; ?>
                
                <?php if(isset($error)): ?>
                <div class="alert alert-error mb-4">
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="max-w-6xl mx-auto" id="productForm">
                    <!-- GİZLİ DOSYA INPUT -->
                    <input type="file" name="final_images[]" id="finalImages" multiple accept="image/*" style="display:none;">
                    
                    <!-- Ürün Bilgileri -->
                    <div class="card bg-white shadow mb-6">
                        <div class="card-body">
                            <h3 class="text-lg font-bold mb-4">Ürün Bilgileri</h3>
                            
                            <div class="grid lg:grid-cols-2 gap-4">
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">Ürün Kodu</span>
                                    </label>
                                    <input type="text" name="code" value="<?= htmlspecialchars($product['code']) ?>" class="input input-bordered" required>
                                </div>

                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">Ürün Adı</span>
                                    </label>
                                    <input type="text" name="title" value="<?= htmlspecialchars($product['title']) ?>" class="input input-bordered" required>
                                </div>

                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">Fiyat (₺)</span>
                                    </label>
                                    <input type="number" name="price" value="<?= $product['price'] ?>" class="input input-bordered" step="0.01" max="99999999" required>
                                </div>

                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">Stok</span>
                                    </label>
                                    <input type="number" name="stock" value="<?= $product['stock'] ?>" class="input input-bordered" required>
                                </div>
                            </div>

                            <div class="form-control mt-4">
                                <label class="label">
                                    <span class="label-text">Açıklama</span>
                                </label>
                                <textarea name="description" class="textarea textarea-bordered" rows="3"><?= htmlspecialchars($product['description']) ?></textarea>
                            </div>

                            <div class="form-control mt-4">
                                <label class="cursor-pointer label justify-start">
                                    <input type="checkbox" name="is_active" class="checkbox checkbox-primary" <?= $product['is_active'] ? 'checked' : '' ?>>
                                    <span class="label-text ml-3">Ürün aktif</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Sınıf Seçimi -->
                    <div class="card bg-white shadow mb-6">
                        <div class="card-body">
                            <h3 class="text-lg font-bold mb-4 text-green-600">🎓 Hangi Sınıflara Gösterilsin?</h3>
                            <p class="text-sm text-gray-500 mb-4">Mevcut seçim: 
                                <span class="font-semibold">
                                    <?= !empty($allowed_classes) ? implode(', ', $allowed_classes) : 'Tüm sınıflar' ?>
                                </span>
                            </p>
                            
                            <div class="class-grid">
                                <?php foreach($all_classes as $class): ?>
                                <div class="class-item <?= in_array($class, $allowed_classes) ? 'selected' : '' ?>" 
                                     data-class="<?= htmlspecialchars($class) ?>" onclick="toggleClass(this)">
                                    <?= htmlspecialchars($class) ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Varyasyonlar -->
                    <div class="card bg-white shadow mb-6">
                        <div class="card-body">
                            <h3 class="text-lg font-bold mb-4">Varyasyonlar</h3>
                            
                            <?php if(!empty($selected_sizes) || !empty($selected_colors)): ?>
                            <div class="alert alert-info mb-4">
                                <div>
                                    <small>
                                        <strong>Mevcut Seçimler:</strong><br>
                                        <?php if(!empty($selected_sizes)): ?>
                                        Bedenler: <?= implode(', ', $selected_sizes) ?><br>
                                        <?php endif; ?>
                                        <?php if(!empty($selected_colors)): ?>
                                        Renkler: <?= implode(', ', $selected_colors) ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Beden -->
                            <div class="mb-4">
                                <label class="label">
                                    <span class="label-text font-semibold">Beden Seçenekleri</span>
                                </label>
                                <div class="variation-grid">
                                    <?php for($i = 35; $i <= 46; $i++): ?>
                                    <div class="variation-item <?= in_array((string)$i, $selected_sizes) ? 'selected' : '' ?>" 
                                         data-type="sizes" data-value="<?= $i ?>">
                                        <?= $i ?>
                                    </div>
                                    <?php endfor; ?>
                                    
                                    <?php foreach(['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL'] as $size): ?>
                                    <div class="variation-item <?= in_array($size, $selected_sizes) ? 'selected' : '' ?>"
                                         data-type="sizes" data-value="<?= $size ?>">
                                        <?= $size ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Renkler -->
                            <div>
                                <label class="label">
                                    <span class="label-text font-semibold">Renk Seçenekleri</span>
                                </label>
                                <div class="variation-grid">
                                    <?php 
                                    $colors = [
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
                                    foreach($colors as $name => $hex): 
                                    ?>
                                    <div class="variation-item <?= in_array($name, $selected_colors) ? 'selected' : '' ?>"
                                         data-type="colors" data-value="<?= $name ?>">
                                        <div style="width: 20px; height: 20px; background: <?= $hex ?>; border: 1px solid #e5e7eb; border-radius: 4px; margin: 0 auto 4px;"></div>
                                        <small><?= $name ?></small>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mevcut Resimler -->
                    <div class="card bg-white shadow mb-6">
                        <div class="card-body">
                            <h3 class="text-lg font-bold mb-4">
                                Mevcut Resimler 
                                <span class="text-sm text-gray-500">(<?= count($images) ?>/30) - Sürükleyerek sırayı değiştirin</span>
                            </h3>
                            
                            <?php if($images && count($images) > 0): ?>
                            <div id="existingImages" class="image-grid">
                                <?php foreach($images as $img): ?>
                                <div class="image-item" data-id="<?= $img['id'] ?>">
                                    <img src="/uploads/products/<?= htmlspecialchars($img['image_path']) ?>" alt="">
                                    <?php if($img['is_primary'] == 1): ?>
                                    <div class="primary-badge"></div>
                                    <?php endif; ?>
                                    <a href="#" onclick="deleteExistingImage(event, <?= $img['id'] ?>)" class="delete-btn">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M18 6V18C18 19.1046 17.1046 20 16 20H8C6.89543 20 6 19.1046 6 18V6M18 6H15M18 6H20M6 6H4M6 6H9M15 6V5C15 3.89543 14.1046 3 13 3H11C9.89543 3 9 3.89543 9 5V6M15 6H9" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <p class="text-gray-500 mb-4">Henüz resim eklenmemiş</p>
                            <?php endif; ?>
                            
                            <?php if(count($images) < 30): ?>
                            <div class="mt-6">
                                <label class="label">
                                    <span class="label-text">Yeni Resimler Ekle (Kalan: <?= 30 - count($images) ?>)</span>
                                </label>
                                <input type="file" id="tempFileInput" multiple accept="image/*" class="file-input file-input-bordered w-full">
                            </div>
                            
                            <div id="newImageGrid" class="image-grid mt-4" style="display: none;">
                                <!-- Yeni resimler buraya gelecek -->
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="/admin/products.php" class="btn btn-outline">İptal</a>
                        <button type="submit" name="submit" class="btn btn-primary">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Güncelle
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Yeni dosyalar için DataTransfer
    const masterDataTransfer = new DataTransfer();
    let fileIdCounter = 0;
    const fileMap = new Map();
    let displayOrder = [];
    
    // Sınıf seçimi
    function toggleClass(element) {
        element.classList.toggle('selected');
        
        const form = document.getElementById('productForm');
        const className = element.dataset.class;
        const inputId = 'class_' + className.replace(/[^a-zA-Z0-9]/g, '_');
        
        if(element.classList.contains('selected')) {
            if(!document.getElementById(inputId)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'classes[]';
                input.value = className;
                input.id = inputId;
                form.appendChild(input);
            }
        } else {
            const input = document.getElementById(inputId);
            if(input) input.remove();
        }
    }
    
    // Varyasyon yönetimi
    document.querySelectorAll('.variation-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            this.classList.toggle('selected');
            
            const form = document.getElementById('productForm');
            const type = this.dataset.type;
            const value = this.dataset.value;
            const inputName = type + '[]';
            const inputId = type + '_' + value.replace(/\s+/g, '_');
            
            if(this.classList.contains('selected')) {
                if(!document.getElementById(inputId)) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = inputName;
                    input.value = value;
                    input.id = inputId;
                    form.appendChild(input);
                }
            } else {
                const input = document.getElementById(inputId);
                if(input) input.remove();
            }
        });
    });
    
    // Mevcut varyasyonları ve sınıfları ekle
    window.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('productForm');
        
        <?php foreach($selected_sizes as $size): ?>
        if(!document.getElementById('sizes_<?= str_replace(' ', '_', $size) ?>')) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'sizes[]';
            input.value = '<?= $size ?>';
            input.id = 'sizes_<?= str_replace(' ', '_', $size) ?>';
            form.appendChild(input);
        }
        <?php endforeach; ?>
        
        <?php foreach($selected_colors as $color): ?>
        if(!document.getElementById('colors_<?= str_replace(' ', '_', $color) ?>')) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'colors[]';
            input.value = '<?= $color ?>';
            input.id = 'colors_<?= str_replace(' ', '_', $color) ?>';
            form.appendChild(input);
        }
        <?php endforeach; ?>
        
        <?php foreach($allowed_classes as $class): ?>
        var classInputId = 'class_<?= str_replace([' ', '.'], '_', $class) ?>';
        if(!document.getElementById(classInputId)) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'classes[]';
            input.value = '<?= $class ?>';
            input.id = classInputId;
            form.appendChild(input);
        }
        <?php endforeach; ?>
    });
    
    // Toast mesaj
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toastMessage');
        
        toast.className = `alert alert-${type} shadow-lg`;
        toastMessage.textContent = message;
        toast.style.display = 'block';
        
        setTimeout(() => {
            toast.style.display = 'none';
        }, 3000);
    }
    
    // Mevcut resim silme
    function deleteExistingImage(event, imgId) {
        event.preventDefault();
        const element = event.target.closest('.image-item');
        
        element.classList.add('removing');
        
        setTimeout(() => {
            window.location.href = `?id=<?= $product_id ?>&delete_img=${imgId}`;
        }, 300);
    }
    
    // Yeni dosya ekleme
    document.getElementById('tempFileInput')?.addEventListener('change', function(e) {
        const newFiles = Array.from(e.target.files);
        const currentImageCount = <?= count($images) ?>;
        const remainingSlots = 30 - currentImageCount - fileMap.size;
        
        newFiles.slice(0, remainingSlots).forEach(file => {
            const fileId = fileIdCounter++;
            fileMap.set(fileId, {file: file, order: displayOrder.length});
            displayOrder.push(fileId);
            masterDataTransfer.items.add(file);
        });
        
        e.target.value = '';
        document.getElementById('finalImages').files = masterDataTransfer.files;
        renderNewImages();
    });
    
    // Yeni resimleri göster
    function renderNewImages() {
        const grid = document.getElementById('newImageGrid');
        
        if(displayOrder.length === 0) {
            grid.style.display = 'none';
            return;
        }
        
        grid.style.display = 'grid';
        
        const existingIds = new Set();
        grid.querySelectorAll('.image-item').forEach(item => {
            const id = parseInt(item.dataset.fileId);
            if(!displayOrder.includes(id)) {
                item.remove();
            } else {
                existingIds.add(id);
            }
        });
        
        displayOrder.forEach((fileId, index) => {
            if(existingIds.has(fileId)) return;
            
            const fileData = fileMap.get(fileId);
            if(!fileData) return;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'image-item';
                div.dataset.fileId = fileId;
                div.innerHTML = `
                    <img src="${e.target.result}" alt="">
                    <div class="delete-btn" onclick="deleteNewImage(${fileId})">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 6V18C18 19.1046 17.1046 20 16 20H8C6.89543 20 6 19.1046 6 18V6M18 6H15M18 6H20M6 6H4M6 6H9M15 6V5C15 3.89543 14.1046 3 13 3H11C9.89543 3 9 3.89543 9 5V6M15 6H9" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                `;
                
                const allItems = Array.from(grid.children);
                if(index >= allItems.length) {
                    grid.appendChild(div);
                } else {
                    grid.insertBefore(div, allItems[index]);
                }
            };
            reader.readAsDataURL(fileData.file);
        });
        
        setTimeout(initNewImagesSortable, 100);
    }
    
    // Yeni resim sil
    function deleteNewImage(fileId) {
        const element = document.querySelector(`#newImageGrid [data-file-id="${fileId}"]`);
        if(element) {
            element.classList.add('removing');
            
            setTimeout(() => {
                const fileData = fileMap.get(fileId);
                if(!fileData) return;
                
                fileMap.delete(fileId);
                
                const index = displayOrder.indexOf(fileId);
                if(index > -1) {
                    displayOrder.splice(index, 1);
                }
                
                const newDataTransfer = new DataTransfer();
                displayOrder.forEach(id => {
                    const data = fileMap.get(id);
                    if(data) {
                        newDataTransfer.items.add(data.file);
                    }
                });
                
                masterDataTransfer.items.clear();
                Array.from(newDataTransfer.files).forEach(file => {
                    masterDataTransfer.items.add(file);
                });
                
                document.getElementById('finalImages').files = masterDataTransfer.files;
                
                element.remove();
                
                if(displayOrder.length === 0) {
                    document.getElementById('newImageGrid').style.display = 'none';
                }
            }, 300);
        }
    }
    
    // Mevcut resimler için Sortable
    const existingImages = document.getElementById('existingImages');
    if(existingImages && typeof Sortable !== 'undefined') {
        Sortable.create(existingImages, {
            animation: 150,
            swap: true,
            swapClass: 'sortable-swap-highlight',
            onEnd: function(evt) {
                if(evt.oldIndex === evt.newIndex) return;
                
                const items = existingImages.querySelectorAll('.image-item');
                const img1_id = items[evt.oldIndex].dataset.id;
                const img2_id = items[evt.newIndex].dataset.id;
                
                // AJAX ile swap
                fetch('?id=<?= $product_id ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `ajax_swap=1&img1_id=${img1_id}&img2_id=${img2_id}`
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        updatePrimaryBadge();
                        showToast('Resim sırası güncellendi');
                    } else {
                        showToast('Hata: ' + (data.error || 'Bilinmeyen hata'), 'error');
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Swap hatası:', error);
                    showToast('Bağlantı hatası', 'error');
                });
            }
        });
    }
    
    // Yeni resimler için Sortable
    function initNewImagesSortable() {
        const newGrid = document.getElementById('newImageGrid');
        if(!newGrid || !window.Sortable) return;
        
        Sortable.create(newGrid, {
            animation: 150,
            swap: true,
            swapClass: 'sortable-swap-highlight',
            onEnd: function(evt) {
                if(evt.oldIndex !== evt.newIndex) {
                    const temp = displayOrder[evt.oldIndex];
                    displayOrder[evt.oldIndex] = displayOrder[evt.newIndex];
                    displayOrder[evt.newIndex] = temp;
                    
                    const newDataTransfer = new DataTransfer();
                    displayOrder.forEach(id => {
                        const data = fileMap.get(id);
                        if(data) {
                            newDataTransfer.items.add(data.file);
                        }
                    });
                    
                    masterDataTransfer.items.clear();
                    Array.from(newDataTransfer.files).forEach(file => {
                        masterDataTransfer.items.add(file);
                    });
                    
                    document.getElementById('finalImages').files = masterDataTransfer.files;
                }
            }
        });
    }
    
    function updatePrimaryBadge() {
        const items = document.querySelectorAll('#existingImages .image-item');
        items.forEach((item, index) => {
            const badge = item.querySelector('.primary-badge');
            if(index === 0) {
                if(!badge) {
                    const newBadge = document.createElement('div');
                    newBadge.className = 'primary-badge';
                    item.appendChild(newBadge);
                }
            } else {
                if(badge) badge.remove();
            }
        });
    }
    </script>
</body>
</html>
