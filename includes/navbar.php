<?php
declare(strict_types=1);
$user = current_user();
?>
<header class="topbar glass">
    <div>
        <h2><?= h($pageTitle ?? 'Dashboard') ?></h2>
        <p class="muted"><?= date('Y-m-d H:i') ?> • Onlayn monitoring rejimi</p>
    </div>
    <div class="top-actions">
        <div class="status-dot pulse"></div>
        <span class="muted">Live</span>
        <div class="user-chip">
            <span class="avatar"><?= mb_substr($user['name'] ?? 'A', 0, 1) ?></span>
            <div>
                <strong><?= h($user['name'] ?? 'Admin') ?></strong>
                <small><?= h($user['role'] ?? 'Operator') ?></small>
            </div>
        </div>
    </div>
</header>
