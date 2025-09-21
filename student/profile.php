<?php
require_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: /login.php');
    exit;
}

$message = '';

// Öğrenci ve veli bilgilerini çek
$stmt = $db->prepare("
    SELECT s.*, u.phone, p.name as parent_name, p.surname as parent_surname, 
           p.phone as parent_phone, p.address as parent_address
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    LEFT JOIN parents p ON p.student_id = s.id
    WHERE s.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

// Profil fotoğrafı yükleme
if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    if(in_array($ext, ['jpg','jpeg','png','gif'])) {
        $newname = 'profile_'.$_SESSION['user_id'].'_'.time().'.'.$ext;
        if(move_uploaded_file($_FILES['photo']['tmp_name'], '../uploads/profiles/'.$newname)) {
            // Eski fotoğrafı sil
            if($student['photo'] && file_exists('../uploads/profiles/'.$student['photo'])) {
                unlink('../uploads/profiles/'.$student['photo']);
            }
            $db->prepare("UPDATE students SET photo = ? WHERE user_id = ?")->execute([$newname, $_SESSION['user_id']]);
            header("Location: profile.php?success=photo");
            exit;
        }
    }
}

// Adres güncelleme
if(isset($_POST['update_address'])) {
    $db->prepare("UPDATE students SET address = ? WHERE user_id = ?")->execute([$_POST['address'], $_SESSION['user_id']]);
    header("Location: profile.php?success=address");
    exit;
}

// Şifre değiştirme
if(isset($_POST['change_password'])) {
    $old = $_POST['old_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    if($new === $confirm && preg_match('/^[0-9]{6}$/', $new)) {
        $check = $db->prepare("SELECT password FROM users WHERE id = ?");
        $check->execute([$_SESSION['user_id']]);
        $user = $check->fetch();
        
        if(password_verify($old, $user['password'])) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $_SESSION['user_id']]);
            $message = 'Şifre başarıyla değiştirildi!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim - Okul Kantini</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.2/dist/full.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card { 
            background: white;
            border: 1px solid rgba(30, 41, 59, 0.1);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .navbar {
            background: #1e293b;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .upload-btn {
            transition: all 0.3s;
        }
        .upload-btn:hover {
            transform: scale(1.05);
        }
        .avatar-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 4px;
            border-radius: 50%;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <div class="navbar text-white">
        <div class="flex-1">
            <a href="/student/dashboard.php" class="btn btn-ghost text-xl">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Okul Kantini
            </a>
        </div>
        <div class="flex-none">
            <ul class="menu menu-horizontal px-1">
                <li><a href="/student/dashboard.php" class="text-white/80 hover:text-white">Ana Sayfa</a></li>
                <li><a href="/student/profile.php" class="bg-white/20 text-white">Profilim</a></li>
                <li><a href="/student/orders.php" class="text-white/80 hover:text-white">Siparişlerim</a></li>
                <li><a href="/logout.php" class="text-red-300 hover:text-red-200">Çıkış</a></li>
            </ul>
        </div>
    </div>

    <div class="container mx-auto p-6 max-w-7xl">
        <!-- Mesajlar -->
        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success mb-6 shadow">
                <svg class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>
                    <?php 
                    if($_GET['success'] == 'photo') echo 'Profil fotoğrafı güncellendi!';
                    if($_GET['success'] == 'address') echo 'Adres bilgisi güncellendi!';
                    ?>
                </span>
            </div>
        <?php endif; ?>
        
        <?php if($message): ?>
            <div class="alert alert-success mb-6 shadow">
                <span><?= $message ?></span>
            </div>
        <?php endif; ?>

        <!-- Profil Fotoğrafı - Üstte Ortada -->
        <div class="flex justify-center mb-8">
            <div class="card p-8 text-center">
                <div class="avatar-container mx-auto mb-4">
                    <div class="avatar">
                        <div class="w-40 rounded-full bg-white">
                            <?php if($student && $student['photo']): ?>
                                <img src="/uploads/profiles/<?=$student['photo']?>" alt="Profil">
                            <?php else: ?>
                                <div class="bg-gradient-to-br from-blue-500 to-purple-600 w-40 h-40 rounded-full flex items-center justify-center text-white text-5xl font-bold">
                                    <?= $student ? strtoupper(substr($student['name'],0,1)) : '?' ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <h2 class="text-2xl font-bold text-gray-800 mb-1">
                    <?= $student ? htmlspecialchars($student['name'] . ' ' . $student['surname']) : 'İsimsiz Öğrenci' ?>
                </h2>
                <p class="text-gray-600 mb-4"><?= $student ? htmlspecialchars($student['class']) : '-' ?> Sınıfı</p>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" id="photoInput" name="photo" class="hidden" accept="image/*" onchange="this.form.submit()">
                    <label for="photoInput" class="btn btn-primary btn-sm upload-btn">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Fotoğraf Değiştir
                    </label>
                </form>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Sol: Öğrenci Bilgileri -->
            <div class="card">
                <div class="card-body">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Öğrenci Bilgileri
                    </h3>
                    
                    <div class="space-y-3">
                        <div>
                            <label class="text-xs text-gray-500 uppercase tracking-wider">Telefon</label>
                            <p class="text-gray-800 font-medium"><?= $student ? htmlspecialchars($student['phone']) : '-' ?></p>
                        </div>
                        
                        <form method="POST">
                            <label class="text-xs text-gray-500 uppercase tracking-wider">Adres</label>
                            <textarea name="address" rows="3" class="textarea textarea-bordered w-full mt-1" placeholder="Adresinizi girin..."><?= $student ? htmlspecialchars($student['address']) : '' ?></textarea>
                            <button type="submit" name="update_address" class="btn btn-primary btn-sm mt-2 w-full">Adresi Güncelle</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Orta: Veli Bilgileri -->
            <div class="card">
                <div class="card-body">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Veli Bilgileri
                    </h3>
                    
                    <div class="space-y-3">
                        <div>
                            <label class="text-xs text-gray-500 uppercase tracking-wider">Veli Adı Soyadı</label>
                            <p class="text-gray-800 font-medium">
                                <?= $student ? htmlspecialchars(($student['parent_name'] ?? '-') . ' ' . ($student['parent_surname'] ?? '')) : '-' ?>
                            </p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 uppercase tracking-wider">Veli Telefonu</label>
                            <p class="text-gray-800 font-medium"><?= $student ? htmlspecialchars($student['parent_phone'] ?? '-') : '-' ?></p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 uppercase tracking-wider">Veli Adresi</label>
                            <p class="text-gray-800 font-medium text-sm"><?= $student ? htmlspecialchars($student['parent_address'] ?? '-') : '-' ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sağ: Şifre Değiştirme -->
            <div class="card">
                <div class="card-body">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        Şifre Değiştir
                    </h3>
                    
                    <form method="POST" class="space-y-3">
                        <div>
                            <label class="text-xs text-gray-500 uppercase tracking-wider">Mevcut Şifre</label>
                            <input type="password" name="old_password" class="input input-bordered w-full mt-1" maxlength="6" required>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 uppercase tracking-wider">Yeni Şifre (6 haneli)</label>
                            <input type="password" name="new_password" class="input input-bordered w-full mt-1" maxlength="6" pattern="[0-9]{6}" required>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 uppercase tracking-wider">Yeni Şifre Tekrar</label>
                            <input type="password" name="confirm_password" class="input input-bordered w-full mt-1" maxlength="6" pattern="[0-9]{6}" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-error btn-sm w-full">Şifreyi Güncelle</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
