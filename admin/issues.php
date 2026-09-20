<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $issue_id = (int)$_POST['issue_id'];
        $new_status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE citizen_issues SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $issue_id]);
        $message = "Issue #$issue_id status updated to '$new_status'.";
    }
}

$issues = $pdo->query("
    SELECT i.*, p.name AS palika_name 
    FROM citizen_issues i 
    JOIN palikas p ON i.palika_id = p.id 
    ORDER BY i.created_at DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Issue Moderation - RateMyPalika Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-content">
        <h1 style="margin-bottom: 20px;">Citizen Issue Ticket Management</h1>

        <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Palika / Ward</th>
                    <th>Category</th>
                    <th>Title & Description</th>
                    <th>Photo</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($issues as $iss): ?>
                    <tr>
                        <td>#<?= $iss['id'] ?></td>
                        <td><strong><?= htmlspecialchars($iss['palika_name']) ?></strong><br>Ward <?= $iss['ward_number'] ?></td>
                        <td><?= htmlspecialchars($iss['category']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($iss['title']) ?></strong>
                            <p style="font-size: 12px; color: #64748b;"><?= htmlspecialchars($iss['description']) ?></p>
                        </td>
                        <td>
                            <?php if ($iss['image_url']): ?>
                                <a href="../<?= htmlspecialchars($iss['image_url']) ?>" target="_blank" style="color:#2563eb;">View Photo ↗</a>
                            <?php else: ?>
                                <span style="color:#9ca3af;">No photo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="font-weight:bold; color: <?= $iss['status'] === 'Resolved' ? '#16a34a' : ($iss['status'] === 'In Progress' ? '#d97706' : '#ef4444') ?>;">
                                <?= $iss['status'] ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="issues.php" style="display:flex; gap:6px;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="issue_id" value="<?= $iss['id'] ?>">
                                <select name="status" style="padding: 4px; font-size: 12px;" onchange="this.form.submit()">
                                    <option value="Open" <?= $iss['status'] === 'Open' ? 'selected' : '' ?>>Open</option>
                                    <option value="In Progress" <?= $iss['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="Resolved" <?= $iss['status'] === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                    <option value="Rejected" <?= $iss['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</div>

</body>
</html>