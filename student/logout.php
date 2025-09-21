<?php
session_start();
require_once '../config/database.php';

// Öğrenci kontrolü ve token temizleme
if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'student') {
    try {
        // Remember token'ı veritabanından temizle
        $stmt = $db->prepare("UPDATE users SET remember_token = NULL WHERE id = ? AND role = 'student'");
        $stmt->execute([$_SESSION['user_id']]);
    } catch(PDOException $e) {
        // Hata olsa bile çıkış işlemine devam et
    }
}

// Öğrenci çerezini temizle
if(isset($_COOKIE['remember_student'])) {
    setcookie('remember_student', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

// Session'ı tamamen temizle
$_SESSION = array();

// Session cookie'sini de sil
if(ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Session'ı yok et
session_destroy();

// Login sayfasına yönlendir
header('Location: /student/login.php?message=Başarıyla çıkış yaptınız');
exit;
?>1~<?php
session_start();
require_once '../config/database.php';

// Öğrenci kontrolü ve token temizleme
if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'student') {
    try {
        // Remember token'ı veritabanından temizle
        $stmt = $db->prepare("UPDATE users SET remember_token = NULL WHERE id = ? AND role = 'student'");
        $stmt->execute([$_SESSION['user_id']]);
    } catch(PDOException $e) {
        // Hata olsa bile çıkış işlemine devam et
    }
}

// Öğrenci çerezini temizle
if(isset($_COOKIE['remember_student'])) {
    setcookie('remember_student', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

// Session'ı tamamen temizle
$_SESSION = array();

// Session cookie'sini de sil
if(ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Session'ı yok et
session_destroy();

// Login sayfasına yönlendir
header('Location: /student/login.php?message=Başarıyla çıkış yaptınız');
exit;
?>
