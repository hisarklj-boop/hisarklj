<?php
require_once 'config/database.php';

// Zaten giriş yapmışsa yönlendir
if(isset($_SESSION['user_id'])) {
    if($_SESSION['role'] == 'admin') {
        header('Location: /admin/dashboard.php');
    } else {
        header('Location: /student/dashboard.php');
    }
    exit;
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Telefon ve şifre kontrolü
    if(preg_match('/^[0-9]{10}$/', $phone) && preg_match('/^[0-9]{6}$/', $password)) {
        try {
            // Önce admin mi kontrol et
            $stmt = $db->prepare("SELECT id, password, role FROM users WHERE phone = ? AND role = 'admin' AND is_active = 1");
            $stmt->execute([$phone]);
            $user = $stmt->fetch();
            
            // Admin değilse öğrenci olarak kontrol et
            if(!$user) {
                $stmt = $db->prepare("SELECT id, password, role FROM users WHERE phone = ? AND role = 'student' AND is_active = 1");
                $stmt->execute([$phone]);
                $user = $stmt->fetch();
            }
            
            if($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['phone'] = $phone;
                
                // Role göre yönlendir
                if($user['role'] == 'admin') {
                    header('Location: /admin/dashboard.php');
                } else {
                    header('Location: /student/dashboard.php');
                }
                exit;
            } else {
                $error = 'Telefon numarası veya şifre hatalı!';
            }
        } catch(PDOException $e) {
            $error = 'Sistem hatası: ' . $e->getMessage();
        }
    } else {
        $error = 'Telefon 10 haneli, şifre 6 haneli olmalı!';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş - Okul Kantini</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.2/dist/full.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center">
        <div class="card w-96 bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="text-2xl font-bold text-center mb-4">Okul Kantini Giriş</h2>
                
                <?php if($error): ?>
                    <div class="alert alert-error mb-4">
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-control mb-4">
                        <label class="label">
                            <span class="label-text">Telefon Numarası</span>
                        </label>
                        <input type="tel" name="phone" class="input input-bordered" 
                               placeholder="5551234567" maxlength="10" pattern="[0-9]{10}" required>
                    </div>
                    
                    <div class="form-control mb-4">
                        <label class="label">
                            <span class="label-text">Şifre (6 haneli)</span>
                        </label>
                        <input type="password" name="password" class="input input-bordered" 
                               placeholder="123456" maxlength="6" pattern="[0-9]{6}" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-full">Giriş Yap</button>
                </form>
                
                <div class="divider">VEYA</div>
                
                <a href="/admin/login.php" class="btn btn-outline btn-sm">Admin Girişi</a>
            </div>
        </div>
    </div>
</body>
</html>
