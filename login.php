<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if (auth_login($login, $password)) {
        log_event('auth', 'Administrator tizimga kirdi');
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Login yoki parol noto‘g‘ri.';
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | MY NajotLink</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-bg"></div>
<main class="login-page">
    <section class="login-panel">
        <div class="hero-card">
            <h1 style="margin-top:0;font-size:34px;">MY NajotLink</h1>
            <p class="muted" style="max-width:520px;">Sun’iy intellekt asosida ishonch telefonlariga ulanuvchi shoshilinch yordam ekotizimi.</p>
            <div class="hero-grid">
                <div class="hero-stat"><small class="muted">Faol qurilmalar</small><div style="font-size:22px;font-weight:800;">120+</div></div>
                <div class="hero-stat"><small class="muted">Kunlik alert</small><div style="font-size:22px;font-weight:800;">36</div></div>
                <div class="hero-stat"><small class="muted">AI aniqlik</small><div style="font-size:22px;font-weight:800;">93%</div></div>
            </div>
        </div>
        <form method="post" class="glass login-form" autocomplete="off">
            <h3>Tizimga kirish</h3>
            <?php if ($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?>
            <label class="muted">Login</label>
            <input class="input" name="login" placeholder="Login kiriting" required>
            <div style="height:10px"></div>
            <label class="muted">Parol</label>
            <input class="input" type="password" name="password" placeholder="••••••••" required>
            <div style="height:18px"></div>
            <button type="submit" class="btn primary" style="width:100%;padding:12px 16px;font-size:16px;">Kirish</button>
            <p class="muted" style="font-size:13px;margin-top:14px;">Platforma: Administrator, operator va monitoring bo‘limlari uchun.</p>
        </form>
    </section>
</main>
<script src="assets/js/app.js"></script>
</body>
</html>
