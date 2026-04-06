<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
log_event('auth', 'Administrator tizimdan chiqdi');
auth_logout();
header('Location: login.php');
exit;
