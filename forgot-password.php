<?php
require_once 'config/database.php';

$error = '';
$success = '';
$step = 'phone';

if(isset($_SESSION['forgot_step'])) {
    $step = $_SESSION['forgot_step'];
}

// Telefon numarası gönderme
if(isset($_POST['send_otp'])) {
    $phone = trim($_POST['phone']);
    
    if(empty($phone)) {
        $error = 'Telefon numarası gerekli!';
    } elseif(strlen($phone) != 10) {
        $error = 'Telefon numarası 10 haneli olmalı!';
    } elseif(!preg_match('/^5[0-9]{9}$/', $phone)) {
        $error = 'Telefon numarası 5 ile başlamalı ve sadece rakam içermeli!';
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();
        
        if($user) {
            $otp = '111112';
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+1 minute'));
            
            $stmt = $db->prepare("UPDATE users SET reset_otp = ?, otp_expiry = ? WHERE phone = ?");
            $stmt->execute([$otp, $otp_expiry, $phone]);
            
            $_SESSION['forgot_phone'] = $phone;
            $_SESSION['forgot_step'] = 'otp';
            $_SESSION['otp_start_time'] = time();
            $step = 'otp';
            $success = 'OTP kodunuz: 111112 (1 dakika geçerli)';
        } else {
            $error = 'Bu telefon numarası kayıtlı değil!';
        }
    }
}

// OTP doğrulama
if(isset($_POST['verify_otp'])) {
    $otp = trim($_POST['otp']);
    $phone = $_SESSION['forgot_phone'] ?? '';
    
    if(empty($otp)) {
        $error = 'OTP kodu gerekli!';
    } elseif(strlen($otp) != 6) {
        $error = 'OTP kodu 6 haneli olmalı!';
    } elseif(!preg_match('/^[0-9]{6}$/', $otp)) {
        $error = 'OTP kodu sadece rakam içermeli!';
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE phone = ? AND reset_otp = ? AND otp_expiry > NOW()");
        $stmt->execute([$phone, $otp]);
        $user = $stmt->fetch();
        
        if($user) {
            $_SESSION['forgot_step'] = 'newpassword';
            $_SESSION['forgot_user_id'] = $user['id'];
            $step = 'newpassword';
            $success = 'OTP doğrulandı! Yeni şifrenizi belirleyin.';
        } else {
            $error = 'Geçersiz veya süresi dolmuş OTP kodu!';
        }
    }
}

// Yeni şifre belirleme
if(isset($_POST['reset_password'])) {
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $user_id = $_SESSION['forgot_user_id'] ?? 0;
    
    if(empty($password)) {
        $error = 'Şifre gerekli!';
    } elseif(strlen($password) != 6) {
        $error = 'Şifre 6 haneli olmalı!';
    } elseif(!preg_match('/^[0-9]{6}$/', $password)) {
        $error = 'Şifre sadece rakam içermeli!';
    } elseif(empty($confirm_password)) {
        $error = 'Şifre tekrarı gerekli!';
    } elseif($password !== $confirm_password) {
        $error = 'Şifreler eşleşmiyor!';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("UPDATE users SET password = ?, reset_otp = NULL, otp_expiry = NULL WHERE id = ?");
        $stmt->execute([$hashed_password, $user_id]);
        
        unset($_SESSION['forgot_step']);
        unset($_SESSION['forgot_phone']);
        unset($_SESSION['forgot_user_id']);
        unset($_SESSION['otp_start_time']);
        
        header('Location: /login.php?success=Şifreniz başarıyla güncellendi!');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="tr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifremi Unuttum - Okul Kantini</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.2/dist/full.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .forgot-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="forgot-card w-full max-w-md rounded-2xl shadow-2xl p-8">
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-r from-red-500 to-pink-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Şifremi Unuttum</h1>
            <?php if($step == 'phone'): ?>
                <p class="text-gray-600">Telefon numaranızı girin</p>
            <?php elseif($step == 'otp'): ?>
                <p class="text-gray-600">SMS ile gelen kodu girin</p>
            <?php else: ?>
                <p class="text-gray-600">Yeni şifrenizi belirleyin</p>
            <?php endif; ?>
        </div>

        <?php if($error): ?>
        <div class="alert alert-error mb-6">
            <svg class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <?php if($success): ?>
        <div class="alert alert-success mb-6">
            <svg class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
        <?php endif; ?>

        <div class="steps steps-horizontal w-full mb-8">
            <div class="step <?= $step == 'phone' ? 'step-primary' : ($step == 'otp' || $step == 'newpassword' ? 'step-primary' : '') ?>">Telefon</div>
            <div class="step <?= $step == 'otp' ? 'step-primary' : ($step == 'newpassword' ? 'step-primary' : '') ?>">OTP</div>
            <div class="step <?= $step == 'newpassword' ? 'step-primary' : '' ?>">Yeni Şifre</div>
        </div>

        <?php if($step == 'phone'): ?>
        <form method="POST" class="space-y-6">
            <div>
                <label class="label">
                    <span class="label-text font-semibold text-gray-700">Telefon Numarası</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <input type="tel" name="phone" placeholder="5551234567" maxlength="10" pattern="[0-9]*" inputmode="numeric" class="input input-bordered w-full pl-10" required>
                </div>
                <div class="label">
                    <span class="label-text-alt text-gray-500">10 haneli, 5 ile başlamalı</span>
                </div>
            </div>

            <button type="submit" name="send_otp" class="btn btn-primary w-full btn-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                OTP Gönder
            </button>
        </form>

        <?php elseif($step == 'otp'): ?>
        <form method="POST" class="space-y-6">
            <div>
                <label class="label">
                    <span class="label-text font-semibold text-gray-700">OTP Kodu</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <input type="text" name="otp" placeholder="111112" maxlength="6" pattern="[0-9]*" inputmode="numeric" class="input input-bordered w-full pl-10 text-center text-2xl tracking-widest" required>
                </div>
                <div class="label">
                    <span class="label-text-alt text-gray-500">6 haneli kod (1 dakika geçerli)</span>
                </div>
            </div>

            <div class="text-center">
                <div class="countdown font-mono text-2xl">
                    <span style="--value:1;"></span>:
                    <span style="--value:0;" id="seconds">00</span>
                </div>
                <div class="text-sm text-gray-500">Kalan süre</div>
            </div>

            <button type="submit" name="verify_otp" class="btn btn-success w-full btn-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Kodu Doğrula
            </button>
        </form>

        <?php else: ?>
        <form method="POST" class="space-y-6">
            <div>
                <label class="label">
                    <span class="label-text font-semibold text-gray-700">Yeni Şifre</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <input type="password" name="password" placeholder="123456" maxlength="6" pattern="[0-9]*" inputmode="numeric" class="input input-bordered w-full pl-10" required>
                </div>
                <div class="label">
                    <span class="label-text-alt text-gray-500">6 haneli sadece rakam</span>
                </div>
            </div>

            <div>
                <label class="label">
                    <span class="label-text font-semibold text-gray-700">Yeni Şifre Tekrar</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <input type="password" name="confirm_password" placeholder="123456" maxlength="6" pattern="[0-9]*" inputmode="numeric" class="input input-bordered w-full pl-10" required>
                </div>
                <div class="label">
                    <span class="label-text-alt text-gray-500">Şifrenizi tekrar girin</span>
                </div>
            </div>

            <button type="submit" name="reset_password" class="btn btn-success w-full btn-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Şifremi Güncelle
            </button>
        </form>
        <?php endif; ?>

        <div class="text-center mt-6">
            <a href="/login.php" class="link link-primary">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Giriş sayfasına dön
            </a>
        </div>
    </div>

    <script>
    // Telefon inputu için sadece rakam ve 5 ile başlama kontrolü
    document.querySelector('input[name="phone"]')?.addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^0-9]/g, '');
        if(value.length > 0 && value[0] !== '5') {
            value = '';
            alert('Telefon numarası 5 ile başlamalı!');
        }
        e.target.value = value;
    });

    // OTP inputu için sadece rakam
    document.querySelector('input[name="otp"]')?.addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/[^0-9]/g, '');
    });

    // Şifre inputları için sadece rakam
    document.querySelectorAll('input[name="password"], input[name="confirm_password"]').forEach(input => {
        input.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
    });

    <?php if($step == 'otp'): ?>
    // 1 dakikalık geri sayım
    let timeLeft = 60;
    const timer = setInterval(function() {
        const seconds = document.getElementById('seconds');
        if(seconds) {
            seconds.style.setProperty('--value', timeLeft);
            timeLeft--;
            
            if(timeLeft < 0) {
                clearInterval(timer);
                alert('Süre doldu! Yeniden OTP isteyin.');
                window.location.href = '/forgot-password.php';
            }
        }
    }, 1000);
    <?php endif; ?>
    </script>
</body>
</html>
