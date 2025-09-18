<?php
require_once 'config/database.php';

// Zaten öğrenci girişi yapmışsa yönlendir
if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'student') {
    header('Location: /student/dashboard.php');
    exit;
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if(preg_match('/^[0-9]{10}$/', $phone) && preg_match('/^[0-9]{6}$/', $password)) {
        try {
            // SADECE ÖĞRENCİ HESAPLARINI KONTROL ET
            $stmt = $db->prepare("SELECT id, password, role FROM users WHERE phone = ? AND role = 'student' AND is_active = 1");
            $stmt->execute([$phone]);
            $user = $stmt->fetch();
            
            if($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'student';
                $_SESSION['phone'] = $phone;
                header('Location: /student/dashboard.php');
                exit;
            } else {
                $error = 'Telefon numarası veya şifre hatalı!';
            }
        } catch(PDOException $e) {
            $error = 'Sistem hatası!';
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
    <title>Öğrenci Girişi - Okul Kantini</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.2/dist/full.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="card w-full max-w-md bg-base-100 shadow-2xl">
            <div class="card-body">
                <div class="text-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">🎓 Okul Kantini</h1>
                    <p class="text-gray-600 mt-2">Öğrenci Girişi</p>
                </div>
                
                <?php if($error): ?>
                    <div class="alert alert-error mb-4">
                        <svg class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-medium">📞 Telefon Numarası</span>
                        </label>
                        <input type="tel" name="phone" class="input input-bordered input-primary" 
                               placeholder="5559876543" maxlength="10" pattern="[0-9]{10}" required>
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-medium">🔐 Şifre</span>
                        </label>
                        <input type="password" name="password" class="input input-bordered input-primary" 
                               placeholder="6 haneli şifre" maxlength="6" pattern="[0-9]{6}" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-full">Giriş Yap</button>
                </form>
                
                <div class="divider">Diğer Girişler</div>
                
                <div class="text-center">
                    <a href="/admin/login.php" class="btn btn-outline btn-sm">
                        👨‍💼 Admin Girişi
                    </a>
                </div>
                
                <div class="bg-blue-50 p-4 rounded-lg text-center text-sm mt-4">
                    <p class="font-semibold text-blue-800">Demo Öğrenci Hesabı</p>
                    <p class="text-blue-600">📞 5559876543 | 🔐 123456</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
