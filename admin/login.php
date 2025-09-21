<?php
session_start();
require_once '../config/database.php';

// Zaten giriş yapmışsa dashboard'a yönlendir
if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin') {
    header('Location: /admin/dashboard.php');
    exit;
}

$error = '';
$success = '';

// URL'den mesajları al
if(isset($_GET['message'])) {
    $success = htmlspecialchars($_GET['message']);
}
if(isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}

// Beni Hatırla Çerez Kontrolü
if(isset($_COOKIE['remember_admin']) && !isset($_SESSION['user_id'])) {
    $remember_token = $_COOKIE['remember_admin'];
    
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE remember_token = ? AND role = 'admin' AND remember_token IS NOT NULL");
        $stmt->execute([$remember_token]);
        $user = $stmt->fetch();
        
        if($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['phone'] = $user['phone'];
            header('Location: /admin/dashboard.php');
            exit;
        }
    } catch(PDOException $e) {
        // Hata varsa sessizce devam et
    }
}

// Giriş İşlemi
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validasyon
    if(empty($phone)) {
        $error = 'Telefon numarası gerekli!';
    } elseif(!preg_match('/^5[0-9]{9}$/', $phone)) {
        $error = 'Telefon numarası 10 haneli olmalı ve 5 ile başlamalı!';
    } elseif(empty($password)) {
        $error = 'Şifre gerekli!';
    } elseif(!preg_match('/^[0-9]{6}$/', $password)) {
        $error = 'Şifre 6 haneli ve sadece rakam olmalı!';
    } else {
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE phone = ? AND role = 'admin'");
            $stmt->execute([$phone]);
            $user = $stmt->fetch();
            
            if($user && password_verify($password, $user['password'])) {
                // Session ayarla
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['phone'] = $user['phone'];
                
                // Beni Hatırla
                if($remember) {
                    $remember_token = bin2hex(random_bytes(32));
                    
                    $stmt = $db->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                    $stmt->execute([$remember_token, $user['id']]);
                    
                    setcookie('remember_admin', $remember_token, [
                        'expires' => time() + (30 * 24 * 60 * 60),
                        'path' => '/',
                        'secure' => isset($_SERVER['HTTPS']),
                        'httponly' => true,
                        'samesite' => 'Strict'
                    ]);
                }
                
                header('Location: /admin/dashboard.php');
                exit;
            } else {
                $error = 'Telefon numarası veya şifre hatalı!';
            }
        } catch(PDOException $e) {
            $error = 'Sistem hatası oluştu. Lütfen tekrar deneyin.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi - Okul Kantini</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.2/dist/full.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="login-card w-full max-w-md rounded-2xl shadow-2xl p-8">
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-r from-red-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Admin Girişi</h1>
            <p class="text-gray-600">Okul Kantini Yönetim Paneli</p>
        </div>

        <?php if($error): ?>
        <div class="alert alert-error mb-6">
            <svg class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span><?= $error ?></span>
        </div>
        <?php endif; ?>

        <?php if($success): ?>
        <div class="alert alert-success mb-6">
            <svg class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span><?= $success ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <div>
                <label class="label">
                    <span class="label-text font-semibold text-gray-700">Admin Telefon</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <input type="tel" 
                           name="phone" 
                           id="phone"
                           placeholder="5551234567" 
                           maxlength="10" 
                           pattern="5[0-9]{9}" 
                           inputmode="numeric" 
                           class="input input-bordered w-full pl-10" 
                           required
                           autocomplete="tel">
                </div>
                <div class="label">
                    <span class="label-text-alt text-gray-500">10 haneli, 5 ile başlamalı</span>
                </div>
            </div>

            <div>
                <label class="label">
                    <span class="label-text font-semibold text-gray-700">Admin Şifre</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <input type="password" 
                           name="password" 
                           id="password"
                           placeholder="••••••" 
                           maxlength="6" 
                           pattern="[0-9]{6}" 
                           inputmode="numeric" 
                           class="input input-bordered w-full pl-10" 
                           required
                           autocomplete="current-password">
                </div>
                <div class="label">
                    <span class="label-text-alt text-gray-500">6 haneli sadece rakam</span>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="form-control">
                    <label class="label cursor-pointer justify-start">
                        <input type="checkbox" name="remember" class="checkbox checkbox-primary checkbox-sm mr-2">
                        <span class="label-text text-sm">Beni Hatırla</span>
                    </label>
                </div>
                <a href="/admin/forgot-password.php" class="link link-primary text-sm">Şifremi Unuttum</a>
            </div>

            <button type="submit" name="login" class="btn btn-error w-full btn-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                Admin Girişi Yap
            </button>
        </form>

        <div class="text-center mt-6">
            <a href="/student/login.php" class="link link-secondary text-sm">Öğrenci misiniz? Buraya tıklayın</a>
        </div>

        <div class="text-center mt-8 pt-6 border-t border-gray-200">
            <p class="text-sm text-gray-500">© 2024 Okul Kantini Admin. Tüm hakları saklıdır.</p>
        </div>
    </div>

    <script>
    // Telefon inputu için sadece rakam ve 5 ile başlama kontrolü
    document.getElementById('phone').addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^0-9]/g, '');
        if(value.length > 0 && value[0] !== '5') {
            value = '5';
        }
        e.target.value = value.slice(0, 10);
    });

    // Şifre inputu için sadece rakam kontrolü
    document.getElementById('password').addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/[^0-9]/g, '').slice(0, 6);
    });
    </script>
</body>
</html>
