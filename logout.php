<?php
session_start();

require_once 'config/database.php';

// Remember token'ı temizle
if(isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

// Çerezleri temizle
if(isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/');
}

// Session'ı tamamen temizle
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

header('Location: /login.php?message=Başarıyla çıkış yaptınız');
exit;
?>
