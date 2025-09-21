<?php
require_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: /admin/login.php');
    exit;
}

$student_id = $_GET['id'] ?? 0;
$error = '';

// Öğrenci bilgilerini çek
$stmt = $db->prepare("SELECT s.*, u.phone FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if(!$student) {
    header('Location: /admin/students.php');
    exit;
}

// Sınıf listesi - TAM LİSTE
$all_classes = [
    'Anaokulu', 'Kreş',
    '1. Sınıf', '2. Sınıf', '3. Sınıf', '4. Sınıf', '5. Sınıf',
    '6. Sınıf', '7. Sınıf', '8. Sınıf', '9. Sınıf', '10. Sınıf',
    '11. Sınıf', '12. Sınıf'
];

// Güncelleme işlemi
if(isset($_POST['update_student'])) {
    try {
        $db->beginTransaction();
        
        $phone = trim($_POST['phone']);
        $name = trim($_POST['name']);
        $surname = trim($_POST['surname']);
        $class = trim($_POST['class']);
        $address = trim($_POST['address']);
        
        if(!preg_match('/^[0-9]{10}$/', $phone)) {
            throw new Exception('Telefon numarası 10 haneli olmalı!');
        }
        
        // Telefon benzersiz mi kontrol et (kendisi hariç)
        $stmt = $db->prepare("SELECT u.id FROM users u JOIN students s ON u.id = s.user_id WHERE u.phone = ? AND s.id != ?");
        $stmt->execute([$phone, $student_id]);
        if($stmt->fetch()) {
            throw new Exception('Bu telefon numarası başka bir öğrenci tarafından kullanılıyor!');
        }
        
        // Student bilgilerini güncelle
        $stmt = $db->prepare("UPDATE students SET name = ?, surname = ?, class = ?, address = ? WHERE id = ?");
        $stmt->execute([$name, $surname, $class, $address, $student_id]);
        
        // User telefon güncelle
        $stmt = $db->prepare("UPDATE users u JOIN students s ON u.id = s.user_id SET u.phone = ? WHERE s.id = ?");
        $stmt->execute([$phone, $student_id]);
        
        // Şifre değişikliği varsa
        if(!empty($_POST['password'])) {
            $password = trim($_POST['password']);
            if(!preg_match('/^[0-9]{6}$/', $password)) {
                throw new Exception('Şifre 6 haneli olmalı!');
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users u JOIN students s ON u.id = s.user_id SET u.password = ? WHERE s.id = ?");
            $stmt->execute([$hash, $student_id]);
        }
        
        $db->commit();
        header('Location: /admin/student-edit.php?id=' . $student_id . '&success=1');
        exit;
        
    } catch(Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}

// Güncel bilgileri tekrar çek
$stmt = $db->prepare("SELECT s.*, u.phone FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğrenci Düzenle - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.2/dist/full.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .sidebar { background: #1e293b; }
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
                <a href="/admin/products.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-white/10">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    Ürünler
                </a>
                <a href="/admin/students.php" class="flex items-center px-6 py-3 bg-white/10 border-l-4 border-white">
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
                    <a href="/admin/students.php" class="btn btn-ghost btn-sm mr-4">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Geri
                    </a>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Öğrenci Düzenle</h2>
                        <p class="text-gray-600"><?= htmlspecialchars($student['name'] . ' ' . $student['surname']) ?> - <?= htmlspecialchars($student['class']) ?></p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success mb-6">
                    <svg class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Öğrenci bilgileri başarıyla güncellendi!</span>
                </div>
                <?php endif; ?>

                <?php if($error): ?>
                <div class="alert alert-error mb-6">
                    <svg class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
                <?php endif; ?>

                <div class="max-w-4xl mx-auto">
                    <div class="card bg-white shadow-xl">
                        <div class="card-body">
                            <div class="flex items-center mb-6">
                                <div class="avatar mr-4">
                                    <div class="w-16 rounded-full">
                                        <?php if($student['photo']): ?>
                                            <img src="/uploads/profiles/<?= htmlspecialchars($student['photo']) ?>" alt="Profil">
                                        <?php else: ?>
                                            <div class="bg-blue-500 w-16 h-16 rounded-full flex items-center justify-center text-white font-bold text-xl">
                                                <?= strtoupper(substr($student['name'],0,1)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold">Öğrenci Bilgilerini Düzenle</h3>
                                    <p class="text-gray-600">Öğrenci bilgilerini güncelleyebilirsiniz</p>
                                </div>
                            </div>
                            
                            <form method="POST" class="space-y-6">
                                <div class="grid md:grid-cols-2 gap-6">
                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text font-semibold">Ad *</span>
                                        </label>
                                        <input type="text" name="name" value="<?= htmlspecialchars($student['name']) ?>" class="input input-bordered input-primary" required>
                                    </div>
                                    
                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text font-semibold">Soyad *</span>
                                        </label>
                                        <input type="text" name="surname" value="<?= htmlspecialchars($student['surname']) ?>" class="input input-bordered input-primary" required>
                                    </div>
                                </div>
                                
                                <div class="grid md:grid-cols-2 gap-6">
                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text font-semibold">Telefon (10 haneli) *</span>
                                        </label>
                                        <input type="tel" name="phone" value="<?= htmlspecialchars($student['phone']) ?>" class="input input-bordered input-primary" pattern="[0-9]{10}" maxlength="10" required>
                                        <label class="label">
                                            <span class="label-text-alt text-gray-500">Giriş için kullanılan telefon numarası</span>
                                        </label>
                                    </div>
                                    
                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text font-semibold">Yeni Şifre (6 haneli, isteğe bağlı)</span>
                                        </label>
                                        <input type="password" name="password" class="input input-bordered" pattern="[0-9]{6}" maxlength="6" placeholder="Değiştirmek istemiyorsanız boş bırakın">
                                        <label class="label">
                                            <span class="label-text-alt text-gray-500">Sadece rakam kullanın</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text font-semibold">Sınıf *</span>
                                    </label>
                                    <select name="class" class="select select-bordered select-primary" required>
                                        <option value="">Sınıf Seçiniz</option>
                                        <?php foreach($all_classes as $class): ?>
                                        <option value="<?= htmlspecialchars($class) ?>" <?= $student['class'] == $class ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($class) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label class="label">
                                        <span class="label-text-alt text-gray-500">Öğrencinin mevcut sınıfı: <strong><?= htmlspecialchars($student['class']) ?></strong></span>
                                    </label>
                                </div>
                                
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text font-semibold">Adres</span>
                                    </label>
                                    <textarea name="address" class="textarea textarea-bordered textarea-primary" rows="4" placeholder="Öğrenci adresi (isteğe bağlı)"><?= htmlspecialchars($student['address']) ?></textarea>
                                </div>
                                
                                <div class="card-actions justify-end pt-6">
                                    <a href="/admin/students.php" class="btn btn-outline">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        İptal
                                    </a>
                                    <button type="submit" name="update_student" class="btn btn-primary">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Güncelle
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Öğrenci İstatistikleri -->
                    <div class="grid md:grid-cols-3 gap-6 mt-6">
                        <div class="card bg-white shadow">
                            <div class="card-body text-center">
                                <div class="text-3xl font-bold text-blue-600">0</div>
                                <div class="text-sm text-gray-600">Toplam Sipariş</div>
                            </div>
                        </div>
                        <div class="card bg-white shadow">
                            <div class="card-body text-center">
                                <div class="text-3xl font-bold text-green-600">0₺</div>
                                <div class="text-sm text-gray-600">Toplam Harcama</div>
                            </div>
                        </div>
                        <div class="card bg-white shadow">
                            <div class="card-body text-center">
                                <div class="text-3xl font-bold text-purple-600"><?= date('d.m.Y', strtotime($student['created_at'] ?? 'now')) ?></div>
                                <div class="text-sm text-gray-600">Kayıt Tarihi</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
