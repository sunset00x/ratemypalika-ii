<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$total_palikas = $pdo->query("SELECT COUNT(*) FROM palikas WHERE status = 'approved'")->fetchColumn();
$total_reviews = $pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'approved'")->fetchColumn();
$pending_reviews_count = $pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")->fetchColumn();
$pending_palikas_count = $pdo->query("SELECT COUNT(*) FROM palikas WHERE status = 'pending'")->fetchColumn();
$national_avg = $pdo->query("SELECT ROUND(AVG(overall_rating), 2) FROM reviews WHERE status = 'approved'")->fetchColumn() ?: '0.00';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Overview - RateMyPalika Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-content">
        <h1 style="margin-bottom: 20px;">System Overview</h1>

        <div class="stat-grid">
            <div class="stat-card">
                <div class="label">Approved Municipalities</div>
                <div class="num"><?= $total_palikas ?></div>
            </div>
            <a href="palikas.php" class="stat-card" style="text-decoration: none;">
                <div class="label">Pending Palika Requests</div>
                <div class="num" style="color: #f59e0b;"><?= $pending_palikas_count ?></div>
            </a>
            <div class="stat-card">
                <div class="label">Approved Reviews</div>
                <div class="num"><?= $total_reviews ?></div>
            </div>
            <a href="reviews.php" class="stat-card" style="text-decoration: none;">
                <div class="label">Pending Reviews</div>
                <div class="num" style="color: #ef4444;"><?= $pending_reviews_count ?></div>
            </a>
        </div>
    </main>
</div>

</body>
</html>