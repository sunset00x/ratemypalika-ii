<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'approve_review') {
        $rev_id = (int)$_POST['review_id'];
        $stmt = $pdo->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?");
        $stmt->execute([$rev_id]);
        $message = "Review #$rev_id approved.";
    }

    if ($_POST['action'] === 'reject_review') {
        $rev_id = (int)$_POST['review_id'];
        $stmt = $pdo->prepare("UPDATE reviews SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$rev_id]);
        $message = "Review #$rev_id rejected.";
    }
}

$pending_reviews = $pdo->query("
    SELECT r.*, p.name AS palika_name 
    FROM reviews r 
    JOIN palikas p ON r.palika_id = p.id 
    WHERE r.status = 'pending' 
    ORDER BY r.created_at DESC
")->fetchAll();

$palikas = $pdo->query("SELECT * FROM palikas ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - RateMyPalika</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

    <nav class="navbar">
        <a href="dashboard.php" class="logo">RateMyPalika [Admin]</a>
        <ul class="nav-links">
            <li><a href="../index.php" target="_blank">Live Site ↗</a></li>
            <li><a href="login.php" style="color:#ef4444;">Logout</a></li>
        </ul>
    </nav>

    <div class="hero-section">
        <h1>Admin Control Console</h1>
        <br>
        <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

        <h2>Pending Review Submissions (<?= count($pending_reviews) ?>)</h2>
        <br>
        <?php if (empty($pending_reviews)): ?>
            <p style="color: #6b7280; margin-bottom: 40px;">No reviews currently awaiting moderation.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Palika</th>
                        <th>Ward</th>
                        <th>Overall</th>
                        <th>Feedback</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_reviews as $rev): ?>
                        <tr>
                            <td>#<?= $rev['id'] ?></td>
                            <td><strong><?= htmlspecialchars($rev['palika_name']) ?></strong></td>
                            <td>Ward <?= $rev['ward_number'] ?></td>
                            <td><?= $rev['overall_rating'] ?></td>
                            <td><?= htmlspecialchars($rev['feedback_text'] ?: 'No comment') ?></td>
                            <td style="display: flex; gap: 8px;">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="approve_review">
                                    <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                                    <button type="submit" class="btn-primary" style="background: #16a34a; padding: 6px 12px; font-size: 12px;">Approve</button>
                                </form>
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="reject_review">
                                    <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                                    <button type="submit" class="btn-primary" style="background: #dc2626; padding: 6px 12px; font-size: 12px;">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2>Registered Municipalities</h2>
        <br>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>District</th>
                    <th>Province</th>
                    <th>Total Wards</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($palikas as $p): ?>
                    <tr>
                        <td>#<?= $p['id'] ?></td>
                        <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                        <td><?= htmlspecialchars($p['district']) ?></td>
                        <td><?= htmlspecialchars($p['province']) ?></td>
                        <td><?= $p['total_wards'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</body>
</html>