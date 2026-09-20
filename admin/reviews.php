<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $rev_id = (int)$_POST['review_id'];
    
    if ($_POST['action'] === 'approve_review') {
        $stmt = $pdo->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?");
        $stmt->execute([$rev_id]);
        $message = "Review #$rev_id approved and published live.";
    }

    if ($_POST['action'] === 'reject_review') {
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Moderation - RateMyPalika Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-content">
        <h1 style="margin-bottom: 20px;">Review Moderation Queue</h1>

        <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

        <?php if (empty($pending_reviews)): ?>
            <p style="color: #64748b;">No pending submissions in queue.</p>
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
                            <td><?= $rev['overall_rating'] ?> / 5.0</td>
                            <td><?= htmlspecialchars($rev['feedback_text'] ?: 'No comment') ?></td>
                            <td style="display: flex; gap: 8px;">
                                <form method="POST" action="reviews.php">
                                    <input type="hidden" name="action" value="approve_review">
                                    <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                                    <button type="submit" class="btn-primary" style="background: #16a34a; padding: 6px 12px; font-size: 12px;">Approve</button>
                                </form>
                                <form method="POST" action="reviews.php">
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
    </main>
</div>

</body>
</html>