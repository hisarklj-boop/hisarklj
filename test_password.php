<?php
// 123456 şifresi için hash oluştur
$password = '123456';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Şifre: " . $password . "\n";
echo "Hash: " . $hash . "\n";

// Test et
if(password_verify('123456', $hash)) {
    echo "Doğrulama: BAŞARILI\n";
} else {
    echo "Doğrulama: BAŞARISIZ\n";
}
?>
