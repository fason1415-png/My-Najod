<?php
declare(strict_types=1);
$currentPage = $currentPage ?? '';
$menu = [
    'dashboard.php' => ['icon' => '▦', 'label' => 'Dashboard'],
    'sos-device.php' => ['icon' => '🆘', 'label' => 'Bemor SOS demo'],
    'users.php' => ['icon' => '👥', 'label' => 'Foydalanuvchilar'],
    'devices.php' => ['icon' => '⌚', 'label' => 'Qurilmalar'],
    'alerts.php' => ['icon' => '⚠', 'label' => 'SOS alertlar'],
    'services.php' => ['icon' => '☎', 'label' => 'Xizmatlar'],
    'reports.php' => ['icon' => '📄', 'label' => 'Hisobotlar'],
    'finance.php' => ['icon' => '💹', 'label' => 'Moliyaviy tahlil'],
    'settings.php' => ['icon' => '⚙', 'label' => 'Sozlamalar'],
];
?>
<aside class="sidebar glass">
    <div class="brand">
        <div class="brand-logo">NL</div>
        <div>
            <h1>MY NajotLink</h1>
            <p>Emergency AI Hub</p>
        </div>
    </div>
    <nav>
        <?php foreach ($menu as $url => $item): ?>
            <a class="menu-link <?= $currentPage === $url ? 'active' : '' ?>" href="<?= h($url) ?>">
                <span class="icon"><?= $item['icon'] ?></span>
                <span><?= h($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <a class="menu-link danger-link" href="logout.php">
        <span class="icon">⇥</span>
        <span>Chiqish</span>
    </a>
</aside>
