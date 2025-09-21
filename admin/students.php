<?php
require_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: /admin/login.php');
    exit;
}

// Öğrenci ekleme
if(isset($_POST['add_student'])) {
    try {
        $db->beginTransaction();
        
        $phone = trim($_POST['phone']);
        $name = trim($_POST['name']);
        $surname = trim($_POST['surname']);
        $class = trim($_POST['class']);
        $address = trim($_POST['address']);
        $password = trim($_POST['password']);
        
        if(!preg_match('/^[0-9]{10}$/', $phone)) {
            throw new Exception('Telefon numarası 10 haneli olmalı!');
        }
        
        if(!preg_match('/^[0-9]{6}$/', $password)) {
            throw new Exception('Şifre 6 haneli olmalı!');
        }
        
        // Telefon benzersiz mi kontrol et
        $stmt = $db->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        if($stmt->fetch()) {
            throw new Exception('Bu telefon numarası zaten kullanılıyor!');
        }
        
        // User oluştur
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (phone, password, role, created_at) VALUES (?, ?, 'student', NOW())");
        $stmt->execute([$phone, $hash]);
        $user_id = $db->lastInsertId();
        
        // Student oluştur
        $stmt = $db->prepare("INSERT INTO students (user_id, name, surname, class, address, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $name, $surname, $class, $address]);
        
        $db->commit();
        header('Location: /admin/students.php?success=1');
        exit;
        
    } catch(Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}

// Öğrenci silme
if(isset($_GET['delete'])) {
    try {
        $db->beginTransaction();
        
        $student_id = $_GET['delete'];
        
        $stmt = $db->prepare("SELECT user_id, photo FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        
        if($student) {
            if($student['photo'] && file_exists('../uploads/profiles/' . $student['photo'])) {
                unlink('../uploads/profiles/' . $student['photo']);
            }
            
            $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$student['user_id']]);
        }
        
        $db->commit();
        header('Location: /admin/students.php?deleted=1');
        exit;
        
    } catch(Exception $e) {
        $db->rollBack();
        $delete_error = $e->getMessage();
    }
}

// Öğrencileri listele
$stmt = $db->prepare("
    SELECT s.*, u.phone, u.created_at as register_date 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    ORDER BY s.created_at DESC
");
$stmt->execute();
$students = $stmt->fetchAll();

// Sınıf listesi - TAM LİSTE
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
    <title>Öğrenciler - Admin Panel</title>
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
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Öğrenciler</h2>
                        <p class="text-gray-600">Toplam <?= count($students) ?> öğrenci kayıtlı</p>
                    </div>
                    <button onclick="addStudentModal.showModal()" class="btn btn-primary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Yeni Öğrenci Ekle
                    </button>
                </div>
            </div>

            <div class="p-6">
                <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success mb-6">
                    <svg class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Öğrenci başarıyla eklendi!</span>
                </div>
                <?php endif; ?>

                <?php if(isset($_GET['deleted'])): ?>
                <div class="alert alert-info mb-6">
                    <svg class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Öğrenci silindi!</span>
                </div>
                <?php endif; ?>

                <?php if(isset($error)): ?>
                <div class="alert alert-error mb-6">
                    <svg class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
                <?php endif; ?>

                <?php if(isset($delete_error)): ?>
                <div class="alert alert-error mb-6">
                    <svg class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Silme hatası: <?= htmlspecialchars($delete_error) ?></span>
                </div>
                <?php endif; ?>

                <!-- Sınıflara Göre İstatistik -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
                    <?php 
                    $class_counts = [];
                    foreach($students as $student) {
                        $class = $student['class'];
                        $class_counts[$class] = ($class_counts[$class] ?? 0) + 1;
                    }
                    
                    foreach($all_classes as $class): 
                        $count = $class_counts[$class] ?? 0;
                    ?>
                    <div class="stat bg-white shadow rounded-lg">
                        <div class="stat-title text-xs"><?= htmlspecialchars($class) ?></div>
                        <div class="stat-value text-lg <?= $count > 0 ? 'text-blue-600' : 'text-gray-400' ?>"><?= $count ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Öğrenci Listesi -->
                <div class="card bg-white shadow">
                    <div class="card-body">
                        <div class="overflow-x-auto">
                            <?php if($students): ?>
                            <table class="table table-zebra">
                                <thead>
                                    <tr>
                                        <th>Foto</th>
                                        <th>Ad Soyad</th>
                                        <th>Telefon</th>
                                        <th>Sınıf</th>
                                        <th>Kayıt Tarihi</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($students as $student): ?>
                                    <tr>
                                        <td>
                                            <div class="avatar">
                                                <div class="w-12 rounded-full">
                                                    <?php if($student['photo']): ?>
                                                        <img src="/uploads/profiles/<?= htmlspecialchars($student['photo']) ?>" alt="Profil">
                                                    <?php else: ?>
                                                        <div class="bg-blue-500 w-12 h-12 rounded-full flex items-center justify-center text-white font-bold">
                                                            <?= strtoupper(substr($student['name'],0,1)) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="font-bold"><?= htmlspecialchars($student['name'] . ' ' . $student['surname']) ?></div>
                                            <?php if($student['address']): ?>
                                            <div class="text-sm opacity-50"><?= htmlspecialchars(substr($student['address'], 0, 30)) ?>...</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="font-mono text-sm"><?= htmlspecialchars($student['phone']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-primary badge-lg"><?= htmlspecialchars($student['class']) ?></span>
                                        </td>
                                        <td>
                                            <span class="text-sm"><?= date('d.m.Y', strtotime($student['register_date'])) ?></span>
                                        </td>
                                        <td>
                                            <div class="flex gap-2">
                                                <a href="/admin/student-edit.php?id=<?= $student['id'] ?>" class="btn btn-sm btn-outline" title="Düzenle">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </a>
                                                <button onclick="confirmDelete(<?= $student['id'] ?>, '<?= htmlspecialchars($student['name'] . ' ' . $student['surname']) ?>')" class="btn btn-sm btn-error" title="Sil">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <h3 class="text-lg font-semibold text-gray-600 mb-2">Henüz öğrenci eklenmemiş</h3>
                                <p class="text-sm text-gray-500 mb-4">Yeni öğrenci eklemek için yukarıdaki butonu kullanın</p>
                                <button onclick="addStudentModal.showModal()" class="btn btn-primary">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    İlk Öğrenciyi Ekle
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Öğrenci Ekleme Modal -->
    <dialog id="addStudentModal" class="modal">
        <div class="modal-box max-w-lg">
            <h3 class="font-bold text-lg mb-4">
                <svg class="w-6 h-6 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Yeni Öğrenci Ekle
            </h3>
            
            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Ad *</span>
                        </label>
                        <input type="text" name="name" class="input input-bordered input-primary" required>
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Soyad *</span>
                        </label>
                        <input type="text" name="surname" class="input input-bordered input-primary" required>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Telefon (10 haneli) *</span>
                        </label>
                        <input type="tel" name="phone" class="input input-bordered input-primary" pattern="[0-9]{10}" maxlength="10" placeholder="5551234567" required>
                    </div>
                    
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">Şifre (6 haneli) *</span>
                        </label>
                        <input type="password" name="password" class="input input-bordered input-primary" pattern="[0-9]{6}" maxlength="6" placeholder="123456" required>
                    </div>
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Sınıf *</span>
                    </label>
                    <select name="class" class="select select-bordered select-primary" required>
                        <option value="">Sınıf Seçiniz</option>
                        <?php foreach($all_classes as $class): ?>
                        <option value="<?= htmlspecialchars($class) ?>"><?= htmlspecialchars($class) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-semibold">Adres</span>
                    </label>
                    <textarea name="address" class="textarea textarea-bordered textarea-primary" rows="3" placeholder="Öğrenci adresi (isteğe bağlı)"></textarea>
                </div>
                
                <div class="modal-action">
                    <button type="button" class="btn btn-outline" onclick="addStudentModal.close()">İptal</button>
                    <button type="submit" name="add_student" class="btn btn-primary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Kaydet
                    </button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    <!-- Silme Onay Modal -->
    <dialog id="deleteModal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg text-red-600">
                <svg class="w-6 h-6 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.081 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                Öğrenci Sil
            </h3>
            <p class="py-4" id="deleteMessage"></p>
            <div class="modal-action">
                <button class="btn btn-outline" onclick="deleteModal.close()">İptal</button>
                <a id="deleteLink" href="#" class="btn btn-error">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Sil
                </a>
            </div>
        </div>
    </dialog>

    <script>
    function confirmDelete(studentId, studentName) {
        document.getElementById('deleteMessage').textContent = `"${studentName}" adlı öğrenciyi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz ve öğrencinin tüm verileri silinecektir.`;
        document.getElementById('deleteLink').href = `?delete=${studentId}`;
        deleteModal.showModal();
    }
    </script>
</body>
</html>
