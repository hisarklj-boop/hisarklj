<?php
require_once '../config/database.php';

if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin') {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if(preg_match('/^[0-9]{10}$/', $phone) && preg_match('/^[0-9]{6}$/', $password)) {
        $stmt = $db->prepare("SELECT id, password, role FROM users WHERE phone = ? AND role = 'admin' AND is_active = 1");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();
        
        if($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'admin';
            $_SESSION['phone'] = $phone;
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Telefon veya şifre hatalı!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.2/dist/full.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900">
    <div class="min-h-screen flex items-center justify-center">
        <div class="card w-96 bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="text-2xl font-bold text-center">Admin Girişi</h2>
                <?php if($error): ?>
                    <div class="alert alert-error"><?= $error ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="tel" name="phone" class="input input-bordered w-full mb-4" placeholder="Telefon" maxlength="10" required>
                    <input type="password" name="password" class="input input-bordered w-full mb-4" placeholder="Şifre" maxlength="6" required>
                    <button class="btn btn-primary w-full">Giriş Yap</button>
                </form>
                <a href="/" class="link text-center mt-4">Öğrenci Girişi</a>
            </div>
        </div>
    </div>
</body>
</html>
