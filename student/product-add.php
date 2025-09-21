<?php
require_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: /admin/login.php');
    exit;
}

$message = '';
$error = '';

// Ürün ekleme işlemi
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    
    if($title && $code && $price > 0) {
        try {
            $db->beginTransaction();
            
            // Ürün ekle
            $stmt = $db->prepare("INSERT INTO products (title, code, description, price, stock) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $code, $description, $price, $stock]);
            $product_id = $db->lastInsertId();
            
            // Resimleri yükle
            if(isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
                $imageCount = count($_FILES['images']['name']);
                for($i = 0; $i < min($imageCount, 20); $i++) {
                    if($_FILES['images']['error'][$i] == 0) {
                        $ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
                        if(in_array($ext, ['jpg','jpeg','png','gif'])) {
                            $filename = 'product_'.$product_id.'_'.($i+1).'_'.time().'.'.$ext;
                            $uploadPath = '../uploads/products/'.$filename;
                            
                            if(move_uploaded_file($_FILES['images']['tmp_name'][$i], $uploadPath)) {
                                $stmt = $db->prepare("INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES (?, ?, ?, ?)");
                                $stmt->execute([$product_id, $filename, ($i == 0 ? 1 : 0), $i]);
                            }
                        }
                    }
                }
            }
            
            $db->commit();
            header('Location: /admin/products.php?success=1');
            exit;
        } catch(Exception $e) {
            $db->rollBack();
            $error = 'Ürün eklenirken hata oluştu!';
        }
    } else {
        $error = 'Tüm zorunlu alanları doldurun!';
    }
}
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Ürün Ekle - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.2/dist/full.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .sidebar { background: #1e293b; }
        .preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
            max-height: 300px;
            overflow-y: auto;
        }
        .preview-item {
            position: relative;
            aspect-ratio: 1;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            overflow: hidden;
            background: #f9fafb;
            transition: all 0.3s;
        }
        .preview-item:hover {
            border-color: #3b82f6;
        }
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .preview-item .remove-btn {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 24px;
            height: 24px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            transition: all 0.2s;
        }
        .preview-item .remove-btn:hover {
            background: #dc2626;
            transform: scale(1.1);
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
            <!-- Header -->
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
                <!-- Mesajlar -->
                <?php if($error): ?>
                    <div class="alert alert-error mb-6">
                        <svg class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><?= $error ?></span>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <div class="card bg-white shadow">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" class="space-y-6">
                            <div class="grid md:grid-cols-2 gap-6">
                                <!-- Ürün Bilgileri -->
                                <div class="space-y-4">
                                    <div>
                                        <label class="label">
                                            <span class="label-text font-semibold">Ürün Adı *</span>
                                        </label>
                                        <input type="text" name="title" class="input input-bordered w-full" required>
                                    </div>

                                    <div>
                                        <label class="label">
                                            <span class="label-text font-semibold">Ürün Kodu *</span>
                                        </label>
                                        <input type="text" name="code" class="input input-bordered w-full" required>
                                    </div>

                                    <div>
                                        <label class="label">
                                            <span class="label-text font-semibold">Fiyat (₺) *</span>
                                        </label>
                                        <input type="number" name="price" step="0.01" min="0" class="input input-bordered w-full" required>
                                    </div>

                                    <div>
                                        <label class="label">
                                            <span class="label-text font-semibold">Stok Adedi</span>
                                        </label>
                                        <input type="number" name="stock" min="0" class="input input-bordered w-full" value="0">
                                    </div>
                                </div>

                                <!-- Ürün Açıklaması -->
                                <div>
                                    <label class="label">
                                        <span class="label-text font-semibold">Açıklama</span>
                                    </label>
                                    <textarea name="description" rows="10" class="textarea textarea-bordered w-full h-full" placeholder="Ürün açıklaması..."></textarea>
                                </div>
                            </div>

                            <!-- Resim Yükleme -->
                            <div>
                                <label class="label">
                                    <span class="label-text font-semibold">Ürün Resimleri (Maksimum 20 adet)</span>
                                </label>
                                <input type="file" id="imageInput" name="images[]" multiple accept="image/*" class="file-input file-input-bordered w-full" onchange="previewImages(this)">
                                <div class="label">
                                    <span class="label-text-alt">JPG, JPEG, PNG, GIF formatları desteklenir</span>
                                </div>
                                <div id="imagePreview" class="preview-container"></div>
                            </div>

                            <!-- Gönder Butonu -->
                            <div class="card-actions justify-end">
                                <button type="submit" class="btn btn-primary">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Ürünü Ekle
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    let selectedFiles = [];

    function previewImages(input) {
        const previewContainer = document.getElementById('imagePreview');
        previewContainer.innerHTML = '';
        selectedFiles = Array.from(input.files);

        selectedFiles.forEach((file, index) => {
            if (file.type.startsWith('image/') && index < 20) {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-item';

                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.onload = () => URL.revokeObjectURL(img.src);

                const removeBtn = document.createElement('button');
                removeBtn.className = 'remove-btn';
                removeBtn.innerHTML = '×';
                removeBtn.type = 'button';
                removeBtn.onclick = (e) => {
                    e.preventDefault();
                    removeImage(index);
                };

                previewItem.appendChild(img);
                previewItem.appendChild(removeBtn);
                previewContainer.appendChild(previewItem);
            }
        });
    }

    function removeImage(index) {
        selectedFiles.splice(index, 1);
        
        const input = document.getElementById('imageInput');
        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        input.files = dt.files;
        
        previewImages(input);
    }
    </script>
</body>
</html>
