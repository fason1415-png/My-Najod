<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

if (isset($_GET['delete']) && $_GET['delete'] !== '') {
    db_delete('users', (string)$_GET['delete']);
    header('Location: users.php');
    exit;
}

$users = db_read('users');
$q = trim((string)($_GET['q'] ?? ''));
if ($q !== '') {
    $users = array_values(array_filter($users, function (array $u) use ($q): bool {
        return str_contains(mb_strtolower(implode(' ', $u), 'UTF-8'), mb_strtolower($q, 'UTF-8'));
    }));
}

$pageTitle = 'Foydalanuvchilar';
$currentPage = 'users.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<main class="main-wrap">
<?php include __DIR__ . '/includes/navbar.php'; ?>
<section class="card">
<div class="toolbar"><form method="get" class="actions" style="flex:1;"><input class="input" name="q" value="<?= h($q) ?>" placeholder="Qidiruv..."></form><a class="btn primary" href="user-form.php">+ Yangi foydalanuvchi</a></div>
<div class="table-wrap" style="margin-top:12px;"><table><thead><tr><th>ID</th><th>F.I.Sh</th><th>Telefon</th><th>Toifa</th><th>Status</th><th>Amal</th></tr></thead><tbody>
<?php foreach ($users as $u): ?><tr><td><?= h($u['id'] ?? '-') ?></td><td><?= h($u['full_name'] ?? '-') ?></td><td><?= h($u['phone'] ?? '-') ?></td><td><?= h($u['category'] ?? '-') ?></td><td><span class="<?= badge_class($u['status'] ?? 'Yangi') ?>"><?= h($u['status'] ?? '-') ?></span></td><td><a class="btn secondary" href="user-form.php?id=<?= urlencode((string)$u['id']) ?>">Tahrirlash</a> <a class="btn danger" href="users.php?delete=<?= urlencode((string)$u['id']) ?>" onclick="return confirm('O‘chirilsinmi?')">O‘chirish</a></td></tr><?php endforeach; ?>
</tbody></table></div>
</section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
