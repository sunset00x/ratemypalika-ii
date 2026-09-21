<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_weights') {
        foreach ($_POST['weight'] as $cat_id => $val) {
            $w = (float)$val;
            $stmt = $pdo->prepare("UPDATE scoring_categories SET weightage = ? WHERE id = ?");
            $stmt->execute([$w, $cat_id]);
        }
        $message = "Scoring weightages updated successfully.";
    }
}

$categories = $pdo->query("SELECT * FROM scoring_categories ORDER BY id ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Categories & Scoring - RateMyPalika Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-content">
        <h1 style="margin-bottom: 20px;">Categories & Rating Weightage Configuration</h1>

        <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

        <div style="max-width: 600px; background: #fff; padding: 24px; border-radius: 10px; border: 1px solid #e2e8f0;">
            <form method="POST" action="categories.php">
                <input type="hidden" name="action" value="update_weights">

                <?php foreach ($categories as $cat): ?>
                    <div class="form-group" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <div>
                            <strong><?= htmlspecialchars($cat['display_name']) ?></strong>
                            <div style="font-size: 12px; color: #64748b;">Key: <code><?= htmlspecialchars($cat['category_key']) ?></code></div>
                        </div>
                        <input type="number" step="0.1" name="weight[<?= $cat['id'] ?>]" value="<?= $cat['weightage'] ?>" style="width: 90px; text-align: center;">
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px;">Save Weightage Rules</button>
            </form>
        </div>
    </main>
</div>

</body>
</html>