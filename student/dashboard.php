<?php
require_once '../config/database.php';

// Giriş kontrolü
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: /login.php');
    exit;
}

// Öğrenci bilgilerini çek
$stmt = $db->prepare("SELECT s.*, u.phone FROM students s JOIN users u ON s.user_id = u.id WHERE s.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Öğrenci Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.2/dist/full.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <div class="navbar bg-base-100">
        <div class="flex-1">
            <a class="btn btn-ghost text-xl">Öğrenci Paneli</a>
        </div>
        <div class="flex-none">
            <span class="mr-4">Hoşgeldin <?= $student ? $student['name'] : 'Öğrenci' ?></span>
            <a href="/logout.php" class="btn btn-error btn-sm">Çıkış</a>
        </div>
    </div>
    
    <div class="container mx-auto p-4">
        <div class="alert alert-success">
            <span>Giriş başarılı! Telefon: <?= $_SESSION['phone'] ?></span>
        </div>
        
        <?php if(!$student): ?>
            <div class="alert alert-warning mt-4">
                <span>Öğrenci bilgilerin henüz eklenmemiş. Admin ile iletişime geç.</span>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
