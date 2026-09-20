<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$message = '';
$error = '';

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

    if ($_POST['action'] === 'create_palika') {
        $name = trim($_POST['name']);
        $district = trim($_POST['district']);
        $province = trim($_POST['province']);
        $type = trim($_POST['type']);
        $total_wards = (int)$_POST['total_wards'];

        if (!empty($name) && !empty($district) && !empty($province) && $total_wards > 0) {
            $stmt = $pdo->prepare("INSERT INTO palikas (name, district, province, type, total_wards) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $district, $province, $type, $total_wards]);
            $message = "New Palika created successfully.";
        } else {
            $error = "Please fill in all fields correctly.";
        }
    }

    if ($_POST['action'] === 'update_palika') {
        $id = (int)$_POST['palika_id'];
        $name = trim($_POST['name']);
        $district = trim($_POST['district']);
        $province = trim($_POST['province']);
        $type = trim($_POST['type']);
        $total_wards = (int)$_POST['total_wards'];

        if ($id > 0 && !empty($name) && !empty($district) && !empty($province) && $total_wards > 0) {
            $stmt = $pdo->prepare("UPDATE palikas SET name = ?, district = ?, province = ?, type = ?, total_wards = ? WHERE id = ?");
            $stmt->execute([$name, $district, $province, $type, $total_wards, $id]);
            $message = "Palika #$id updated successfully.";
        } else {
            $error = "Failed to update Palika details.";
        }
    }

    if ($_POST['action'] === 'delete_palika') {
        $id = (int)$_POST['palika_id'];
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM palikas WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Palika #$id deleted successfully.";
        }
    }
}

$search = trim($_GET['search'] ?? '');
$province = trim($_GET['province'] ?? '');
$type = trim($_GET['type'] ?? '');

$query = "SELECT * FROM palikas WHERE 1=1";
$params = [];

if ($search !== '') {
    $query .= " AND (name LIKE ? OR district LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($province !== '') {
    $query .= " AND province = ?";
    $params[] = $province;
}
if ($type !== '') {
    $query .= " AND type = ?";
    $params[] = $type;
}

$query .= " ORDER BY id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$palikas = $stmt->fetchAll();

$pending_reviews = $pdo->query("
    SELECT r.*, p.name AS palika_name 
    FROM reviews r 
    JOIN palikas p ON r.palika_id = p.id 
    WHERE r.status = 'pending' 
    ORDER BY r.created_at DESC
")->fetchAll();

$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_stmt = $pdo->prepare("SELECT * FROM palikas WHERE id = ?");
    $edit_stmt->execute([$edit_id]);
    $edit_item = $edit_stmt->fetch();
}

$provinces = ['Koshi', 'Madhesh', 'Bagmati', 'Gandaki', 'Lumbini', 'Karnali', 'Sudurpashchim'];
$types = ['Metropolitan City', 'Sub-Metropolitan City', 'Municipality', 'Rural Municipality'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - RateMyPalika</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .btn-action {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            border: none;
            color: #ffffff;
            display: inline-block;
        }
        .btn-edit { background: #f59e0b; }
        .btn-edit:hover { background: #d97706; }
        .btn-delete { background: #ef4444; }
        .btn-delete:hover { background: #dc2626; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="dashboard.php" class="logo">RateMyPalika [Admin Console]</a>
        <ul class="nav-links">
            <li><a href="../index.php" target="_blank">Live Site ↗</a></li>
            <li><a href="login.php" style="color:#ef4444;">Logout</a></li>
        </ul>
    </nav>

    <div class="hero-section">
        <h1>Admin Control Console</h1>
        <br>
        <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div style="display: grid; grid-template-columns: 320px 1fr; gap: 40px; margin-bottom: 40px;">
            
            <div class="form-container" style="margin:0; width:100%;">
                <h3><?= $edit_item ? 'Edit Palika #' . $edit_item['id'] : 'Add New Palika' ?></h3>
                <br>
                <form method="POST" action="dashboard.php">
                    <input type="hidden" name="action" value="<?= $edit_item ? 'update_palika' : 'create_palika' ?>">
                    <?php if ($edit_item): ?>
                        <input type="hidden" name="palika_id" value="<?= $edit_item['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Palika Name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($edit_item['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>District</label>
                        <input type="text" name="district" value="<?= htmlspecialchars($edit_item['district'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Province</label>
                        <select name="province" required>
                            <option value="">-- Choose Province --</option>
                            <?php foreach ($provinces as $prov): ?>
                                <option value="<?= $prov ?>" <?= (isset($edit_item['province']) && $edit_item['province'] === $prov) ? 'selected' : '' ?>><?= $prov ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="type">
                            <?php foreach ($types as $t): ?>
                                <option value="<?= $t ?>" <?= (isset($edit_item['type']) && $edit_item['type'] === $t) ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Total Wards</label>
                        <input type="number" name="total_wards" min="1" max="100" value="<?= htmlspecialchars($edit_item['total_wards'] ?? '1') ?>" required>
                    </div>
                    <button type="submit" class="btn-primary" style="width: 100%;"><?= $edit_item ? 'Update Palika' : 'Create Palika' ?></button>
                    <?php if ($edit_item): ?>
                        <a href="dashboard.php" style="display: block; text-align: center; margin-top: 10px; font-size: 13px; color: #6b7280; text-decoration: none;">Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </div>

            <div>
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
                                        <form method="POST" action="dashboard.php">
                                            <input type="hidden" name="action" value="approve_review">
                                            <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                                            <button type="submit" class="btn-primary" style="background: #16a34a; padding: 6px 12px; font-size: 12px;">Approve</button>
                                        </form>
                                        <form method="POST" action="dashboard.php">
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

                <form method="GET" action="dashboard.php" class="filter-bar" style="margin-bottom: 20px;">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search municipality or district..." onchange="this.form.submit()">

                    <select name="province" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Provinces</option>
                        <?php foreach ($provinces as $prov): ?>
                            <option value="<?= $prov ?>" <?= $province === $prov ? 'selected' : '' ?>><?= $prov ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="type" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <?php foreach ($types as $t): ?>
                            <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>

                    <a href="dashboard.php" class="btn-clear">Reset</a>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>District</th>
                            <th>Type</th>
                            <th>Total Wards</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($palikas)): ?>
                            <tr>
                                <td colspan="6" style="color: #6b7280; text-align: center;">No municipalities found matching filter criteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($palikas as $p): ?>
                                <tr>
                                    <td>#<?= $p['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($p['district']) ?></td>
                                    <td><?= htmlspecialchars($p['type']) ?></td>
                                    <td><?= $p['total_wards'] ?></td>
                                    <td style="display: flex; gap: 8px;">
                                        <a href="dashboard.php?edit=<?= $p['id'] ?>" class="btn-action btn-edit">Edit</a>
                                        <form method="POST" action="dashboard.php" onsubmit="return confirm('Are you sure you want to delete <?= htmlspecialchars(addslashes($p['name'])) ?>?');">
                                            <input type="hidden" name="action" value="delete_palika">
                                            <input type="hidden" name="palika_id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn-action btn-delete">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>