<?php
require_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: /admin/login.php');
    exit;
}

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
        
        // Varyasyonlar
        $variations = [];
        if(isset($_POST['sizes']) && is_array($_POST['sizes'])) {
            $variations['sizes'] = $_POST['sizes'];
        }
        if(isset($_POST['colors']) && is_array($_POST['colors'])) {
            $variations['colors'] = $_POST['colors'];
        }
        $variations_json = !empty($variations) ? json_encode($variations, JSON_UNESCAPED_UNICODE) : null;
        
        // Sınıflar - basit string formatında
        $allowed_classes = [];
        if(isset($_POST['classes']) && is_array($_POST['classes'])) {
            $allowed_classes = $_POST['classes'];
        }
        $classes_json = !empty($allowed_classes) ? json_encode($allowed_classes, JSON_UNESCAPED_UNICODE) : null;
        
        $sql = "INSERT INTO products (code, title, description, price, stock, is_active, variations, allowed_classes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$code, $title, $description, $price, $stock, $is_active, $variations_json, $classes_json]);
        $product_id = $db->lastInsertId();
        
        // Resim yükleme
        if(!empty($_FILES['final_images']['name'][0])) {
            $upload_dir = '../uploads/products/';
            if(!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $count = 0;
            foreach($_FILES['final_images']['name'] as $key => $name) {
                if($count >= 30) break;
                if(empty($name)) continue;
                if($_FILES['final_images']['error'][$key] != 0) continue;
                
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if(!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) continue;
                
                $new_name = 'p' . $product_id . '_' . uniqid() . '.' . $ext;
                $target = $upload_dir . $new_name;
                
                if(move_uploaded_file($_FILES['final_images']['tmp_name'][$key], $target)) {
                    $is_primary = ($count == 0) ? 1 : 0;
                    $db->prepare("INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES (?, ?, ?, ?)")
                       ->execute([$product_id, $new_name, $is_primary, $count]);
                    $count++;
                }
            }
        }
        
        $db->commit();
        header('Location: /admin/products.php?success=1');
        exit;
        
    } catch(Exception $e) {
        $db->rollBack();
        $error = "Hata: " . $e->getMessage();
    }
}

// Sınıf listesi - tam liste
$all_classes = [
    'Anaokulu', 'Kreş',
    '1. Sınıf', '2. Sınıf', '3. Sınıf', '4. Sınıf', '5. Sınıf',
    '6. Sınıf', '7. Sınıf', '8. Sınıf', '9. Sınıf', '10. Sınıf',
    '11. Sınıf', '12. Sınıf'
];
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Ürün Ekle - Admin Panel</title>
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
            gap: 0.75rem;
        }
        
        .class-item {
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .class-item:hover {
            border-color: #10b981;
            background: #f0fdf4;
        }
        
        .class-item.selected {
            background: #10b981;
            color: white;
            border-color: #10b981;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);
        }
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
            <div class="bg-white shadow-sm px-6 py-4">
                <div class="flex items-center">
                    <a href="/admin/products.php" class="btn btn-ghost btn-sm mr-4">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Geri
                    </a>
                    <h2 class="text-2xl font-bold text-gray-800">Yeni Ürün Ekle</h2>
                </div>
            </div>

            <div class="p-6">
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
                                        <span class="label-text">Ürün Kodu *</span>
                                    </label>
                                    <input type="text" name="code" class="input input-bordered" required>
                                </div>

                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">Ürün Adı *</span>
                                    </label>
                                    <input type="text" name="title" class="input input-bordered" required>
                                </div>

                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">Fiyat (₺) *</span>
                                    </label>
                                    <input type="number" name="price" class="input input-bordered" step="0.01" min="0" max="99999999" required>
                                </div>

                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">Stok *</span>
                                    </label>
                                    <input type="number" name="stock" class="input input-bordered" min="0" required>
                                </div>
                            </div>

                            <div class="form-control mt-4">
                                <label class="label">
                                    <span class="label-text">Açıklama</span>
                                </label>
                                <textarea name="description" class="textarea textarea-bordered" rows="3"></textarea>
                            </div>

                            <div class="form-control mt-4">
                                <label class="cursor-pointer label justify-start">
                                    <input type="checkbox" name="is_active" class="checkbox checkbox-primary" checked>
                                    <span class="label-text ml-3">Ürün aktif</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Sınıf Seçimi -->
                    <div class="card bg-white shadow mb-6">
                        <div class="card-body">
                            <h3 class="text-lg font-bold mb-4 text-green-600">
                                🎓 Hangi Sınıflara Gösterilsin?
                            </h3>
                            <p class="text-sm text-gray-500 mb-6">
                                Ürünün görüntüleneceği sınıfları seçin. 
                                <strong>Hiçbiri seçilmezse tüm sınıflara gösterilir.</strong>
                            </p>
                            
                            <div class="class-grid">
                                <?php foreach($all_classes as $class): ?>
                                <div class="class-item" data-class="<?= htmlspecialchars($class) ?>" onclick="toggleClass(this)">
                                    <?= htmlspecialchars($class) ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="mt-4">
                                <button type="button" onclick="selectAll()" class="btn btn-sm btn-outline mr-2">Tümünü Seç</button>
                                <button type="button" onclick="clearAll()" class="btn btn-sm btn-outline">Hiçbirini Seçme</button>
                            </div>
                        </div>
                    </div>

                    <!-- Varyasyonlar -->
                    <div class="card bg-white shadow mb-6">
                        <div class="card-body">
                            <h3 class="text-lg font-bold mb-4">Varyasyonlar (İsteğe Bağlı)</h3>
                            
                            <!-- Beden -->
                            <div class="mb-4">
                                <label class="label">
                                    <span class="label-text font-semibold">Beden Seçenekleri</span>
                                </label>
                                <div class="variation-grid">
                                    <?php for($i = 35; $i <= 46; $i++): ?>
                                    <div class="variation-item" data-type="sizes" data-value="<?= $i ?>">
                                        <?= $i ?>
                                    </div>
                                    <?php endfor; ?>
                                    
                                    <?php foreach(['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL'] as $size): ?>
                                    <div class="variation-item" data-type="sizes" data-value="<?= $size ?>">
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
                                    <div class="variation-item" data-type="colors" data-value="<?= $name ?>">
                                        <div style="width: 20px; height: 20px; background: <?= $hex ?>; border: 1px solid #e5e7eb; border-radius: 4px; margin: 0 auto 4px;"></div>
                                        <small><?= $name ?></small>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resimler -->
                    <div class="card bg-white shadow mb-6">
                        <div class="card-body">
                            <h3 class="text-lg font-bold mb-4">Ürün Resimleri</h3>
                            <p class="text-sm text-gray-500 mb-4">Resimleri sürükleyerek sıralayabilirsiniz</p>
                            
                            <input type="file" id="tempFileInput" multiple accept="image/*" class="file-input file-input-bordered w-full mb-4">
                            <p class="text-xs text-gray-500 mb-4">Maksimum 30 resim, her biri 15MB'a kadar</p>
                            
                            <div id="imageGrid" class="image-grid" style="display: none;">
                                <!-- Resimler buraya gelecek -->
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="/admin/products.php" class="btn btn-outline">İptal</a>
                        <button type="submit" name="submit" class="btn btn-primary">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Ürünü Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    const masterDataTransfer = new DataTransfer();
    let fileIdCounter = 0;
    const fileMap = new Map();
    let displayOrder = [];
    
    // Sınıf seçimi
    function toggleClass(element) {
        element.classList.toggle('selected');
        updateClassInputs();
    }
    
    function selectAll() {
        document.querySelectorAll('.class-item').forEach(item => {
            item.classList.add('selected');
        });
        updateClassInputs();
    }
    
    function clearAll() {
        document.querySelectorAll('.class-item').forEach(item => {
            item.classList.remove('selected');
        });
        updateClassInputs();
    }
    
    function updateClassInputs() {
        const form = document.getElementById('productForm');
        
        // Mevcut class inputlarını temizle
        form.querySelectorAll('input[name="classes[]"]').forEach(input => input.remove());
        
        // Seçili sınıfları ekle
        document.querySelectorAll('.class-item.selected').forEach(item => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'classes[]';
            input.value = item.dataset.class;
            form.appendChild(input);
        });
    }
    
    // Varyasyon seçimi
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
    
    // Dosya seçici
    document.getElementById('tempFileInput').addEventListener('change', function(e) {
        const newFiles = Array.from(e.target.files);
        
        newFiles.forEach(file => {
            if(fileMap.size >= 30) return;
            
            const fileId = fileIdCounter++;
            fileMap.set(fileId, {file: file, order: displayOrder.length});
            displayOrder.push(fileId);
            masterDataTransfer.items.add(file);
        });
        
        e.target.value = '';
        document.getElementById('finalImages').files = masterDataTransfer.files;
        renderPreview();
    });
    
    // Önizleme oluştur
    function renderPreview() {
        const grid = document.getElementById('imageGrid');
        
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
                    ${index === 0 ? '<div class="primary-badge"></div>' : ''}
                    <div class="delete-btn" onclick="deleteImage(${fileId})">
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
        
        setTimeout(initSortable, 100);
    }
    
    // Resim sil
    function deleteImage(fileId) {
        const element = document.querySelector(`[data-file-id="${fileId}"]`);
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
                updateBadges();
                
                if(displayOrder.length === 0) {
                    document.getElementById('imageGrid').style.display = 'none';
                }
            }, 300);
        }
    }
    
    // Sortable
    function initSortable() {
        const grid = document.getElementById('imageGrid');
        if(!grid || !window.Sortable) return;
        
        Sortable.create(grid, {
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
                    updateBadges();
                }
            }
        });
    }
    
    function updateBadges() {
        document.querySelectorAll('.image-item').forEach((item, index) => {
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
