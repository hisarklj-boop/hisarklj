<?php
require_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: /admin/login.php');
    exit;
}

$message = '';
$error = '';

// Admin bilgilerini çek
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

// Şifre değiştirme
if(isset($_POST['change_password'])) {
    $old = $_POST['old_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    if($new === $confirm && preg_match('/^[0-9]{6}$/', $new)) {
        if(password_verify($old, $admin['password'])) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $_SESSION['user_id']]);
            $message = 'Şifre başarıyla değiştirildi!';
        } else {
            $error = 'Mevcut şifre hatalı!';
        }
    } else {
        $error = 'Şifreler eşleşmiyor veya 6 haneli değil!';
    }
}

// Telefon güncelleme
if(isset($_POST['update_phone'])) {
    $phone = $_POST['phone'];
    if(preg_match('/^[0-9]{10}$/', $phone)) {
        $db->prepare("UPDATE users SET phone = ? WHERE id = ?")->execute([$phone, $_SESSION['user_id']]);
        $_SESSION['phone'] = $phone;
        $message = 'Telefon numarası güncellendi!';
        header("Location: profile.php?success=1");
        exit;
    } else {
        $error = 'Telefon numarası 10 haneli olmalı!';
    }
}
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profil - Okul Kantini</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.2/dist/full.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .sidebar { background: #1e293b; }
        .card { 
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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
                <a href="/admin/profile.php" class="flex items-center px-6 py-3 bg-white/10 border-l-4 border-white">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Profil
                </a>
                <a href="/admin/settings.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-white/10">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Ayarlar
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
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-gray-800">Admin Profili</h2>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-600">Hoşgeldin, Admin</span>
                        <div class="avatar">
                            <div class="w-10 rounded-full bg-gray-300">
                                <span class="flex items-center justify-center h-full text-white font-bold">A</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- İçerik -->
            <div class="p-6">
                <!-- Mesajlar -->
                <?php if(isset($_GET['success'])): ?>
                    <div class="alert alert-success mb-6">
                        <svg class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Bilgiler başarıyla güncellendi!</span>
                    </div>
                <?php endif; ?>

                <?php if($message): ?>
                    <div class="alert alert-success mb-6">
                        <span><?= $message ?></span>
                    </div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="alert alert-error mb-6">
                        <span><?= $error ?></span>
                    </div>
                <?php endif; ?>

                <div class="grid lg:grid-cols-2 gap-6">
                    <!-- Profil Bilgileri -->
                    <div class="card">
                        <div class="card-body">
                            <h3 class="text-lg font-bold mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Profil Bilgileri
                            </h3>

                            <form method="POST">
                                <div class="space-y-4">
                                    <div>
                                        <label class="text-sm text-gray-600">Telefon Numarası</label>
                                        <input type="tel" name="phone" value="<?= htmlspecialchars($admin['phone']) ?>" 
                                               class="input input-bordered w-full" pattern="[0-9]{10}" maxlength="10" required>
                                    </div>
                                    <div>
                                        <label class="text-sm text-gray-600">Rol</label>
                                        <input type="text" value="Yönetici" class="input input-bordered w-full" disabled>
                                    </div>
                                    <div>
                                        <label class="text-sm text-gray-600">Kayıt Tarihi</label>
                                        <input type="text" value="<?= date('d.m.Y H:i', strtotime($admin['created_at'])) ?>" 
                                               class="input input-bordered w-full" disabled>
                                    </div>
                                </div>
                                <button type="submit" name="update_phone" class="btn btn-primary mt-4 w-full">
                                    Telefonu Güncelle
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Şifre Değiştirme -->
                    <div class="card">
                        <div class="card-body">
                            <h3 class="text-lg font-bold mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                                Şifre Değiştir
                            </h3>

                            <form method="POST">
                                <div class="space-y-4">
                                    <div>
                                        <label class="text-sm text-gray-600">Mevcut Şifre</label>
                                        <input type="password" name="old_password" class="input input-bordered w-full" 
                                               maxlength="6" pattern="[0-9]{6}" required>
                                    </div>
                                    <div>
                                        <label class="text-sm text-gray-600">Yeni Şifre (6 haneli)</label>
                                        <input type="password" name="new_password" class="input input-bordered w-full" 
                                               maxlength="6" pattern="[0-9]{6}" required>
                                    </div>
                                    <div>
                                        <label class="text-sm text-gray-600">Yeni Şifre Tekrar</label>
                                        <input type="password" name="confirm_password" class="input input-bordered w-full" 
                                               maxlength="6" pattern="[0-9]{6}" required>
                                    </div>
                                </div>
                                <button type="submit" name="change_password" class="btn btn-error mt-4 w-full">
                                    Şifreyi Değiştir
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
