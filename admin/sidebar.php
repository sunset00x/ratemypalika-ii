<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);

$pending_reviews_count = $pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")->fetchColumn();
$pending_palikas_count = $pdo->query("SELECT COUNT(*) FROM palikas WHERE status = 'pending'")->fetchColumn();

$menu_items = [
    'dashboard.php'  => ['label' => 'Dashboard Overview', 'icon' => '📊'],
    'palikas.php'    => ['label' => 'Municipalities', 'icon' => '🏛️'],
    'wards.php'      => ['label' => 'Ward Management', 'icon' => '📍'],
    'issues.php'     => ['label' => 'Citizen Issues', 'icon' => '🚨'],
    'reviews.php'    => ['label' => 'Review Moderation', 'icon' => '💬'],
    'analytics.php'  => ['label' => 'Analytics & Reports', 'icon' => '📈'],
    'officials.php'  => ['label' => 'Elected Officials', 'icon' => '👤'],
    'projects.php'   => ['label' => 'Projects & Budgets', 'icon' => '🏗️'],
    'categories.php' => ['label' => 'Categories & Scoring', 'icon' => '⚙️'],
    'users.php'      => ['label' => 'User & Admin Roles', 'icon' => '🔐'],
    'settings.php'   => ['label' => 'Platform Settings', 'icon' => '🛠️'],
];
?>

<aside class="admin-sidebar">
    <div>
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-brand">RateMyPalika Admin</a>
        </div>
        <ul class="sidebar-menu">
            <?php foreach ($menu_items as $file => $item): ?>
                <li>
                    <a href="<?= $file ?>" class="<?= $current_page === $file ? 'active' : '' ?>">
                        <span><?= $item['icon'] ?></span>
                        <span><?= $item['label'] ?></span>
                        <?php if ($file === 'reviews.php' && $pending_reviews_count > 0): ?>
                            <span style="background: #ef4444; color: white; font-size: 11px; padding: 2px 6px; border-radius: 10px; margin-left: auto;"><?= $pending_reviews_count ?></span>
                        <?php elseif ($file === 'palikas.php' && $pending_palikas_count > 0): ?>
                            <span style="background: #f59e0b; color: white; font-size: 11px; padding: 2px 6px; border-radius: 10px; margin-left: auto;"><?= $pending_palikas_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="sidebar-footer">
        <div style="color: #94a3b8; font-weight: 600; margin-bottom: 8px;">Logged as: <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></div>
        <a href="../index.php" target="_blank" style="color: #38bdf8; text-decoration: none; font-size: 12px; display: block; margin-bottom: 6px;">View Live Site ↗</a>
        <a href="login.php" style="color: #ef4444; text-decoration: none; font-size: 12px; display: block;">Sign Out</a>
    </div>
</aside>