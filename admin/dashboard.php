<?php
require_once __DIR__ . '/../config/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $name = trim($_POST['name']);
        $district = trim($_POST['district']);
        $province = trim($_POST['province']);
        $total_wards = (int)$_POST['total_wards'];

        if (!empty($name) && !empty($district) && !empty($province) && $total_wards > 0) {
            $stmt = $pdo->prepare("INSERT INTO palikas (name, district, province, total_wards) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $district, $province, $total_wards]);
            $message = "Palika added successfully.";
        } else {
            $error = "Please fill in all required fields.";
        }
    }

    if ($_POST['action'] === 'update') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $district = trim($_POST['district']);
        $province = trim($_POST['province']);
        $total_wards = (int)$_POST['total_wards'];

        if ($id > 0 && !empty($name) && !empty($district) && !empty($province) && $total_wards > 0) {
            $stmt = $pdo->prepare("UPDATE palikas SET name = ?, district = ?, province = ?, total_wards = ? WHERE id = ?");
            $stmt->execute([$name, $district, $province, $total_wards, $id]);
            $message = "Palika updated successfully.";
        } else {
            $error = "Failed to update Palika details.";
        }
    }

    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM palikas WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Palika deleted successfully.";
        }
    }
}

$palika_details = $pdo->query("
    SELECT 
        p.*,
        COUNT(r.id) AS total_reviews,
        ROUND(AVG(r.overall_rating), 2) AS avg_overall,
        ROUND(AVG(r.road_infrastructure), 2) AS avg_roads,
        ROUND(AVG(r.waste_management), 2) AS avg_waste,
        ROUND(AVG(r.health_services), 2) AS avg_health,
        ROUND(AVG(r.bureaucratic_efficiency), 2) AS avg_efficiency,
        ROUND(AVG(r.transparency_anti_corruption), 2) AS avg_transparency
    FROM palikas p
    LEFT JOIN reviews r ON p.id = r.palika_id
    GROUP BY p.id
    ORDER BY p.id DESC
")->fetchAll();

$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM palikas WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_item = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - RateMyPalika</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-container {
            padding: 40px 80px;
        }
        .admin-grid {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 40px;
            margin-top: 30px;
        }
        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            border: none;
        }
        .btn-edit { background: #eab308; color: #fff; }
        .btn-delete { background: #ef4444; color: #fff; }
        .flex-gap { display: flex; gap: 8px; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="dashboard.php" class="logo">RateMyPalika [Admin Console]</a>
        <ul class="nav-links">
            <li><a href="../index.php" target="_blank">View Live Site ↗</a></li>
        </ul>
    </nav>

    <div class="admin-container">
        <h1>Palika Management Dashboard</h1>
        <p style="color: #6b7280; font-size: 14px; margin-top: 5px;">Create, modify, and monitor detailed performance stats across all registered municipalities.</p>

        <?php if ($message): ?><div class="alert success" style="margin-top: 20px;"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert danger" style="margin-top: 20px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="admin-grid">
            <div class="form-container" style="margin: 0; width: 100%;">
                <h3><?= $edit_item ? 'Edit Palika' : 'Add New Palika' ?></h3>
                <br>
                <form method="POST" action="dashboard.php">
                    <input type="hidden" name="action" value="<?= $edit_item ? 'update' : 'create' ?>">
                    <?php if ($edit_item): ?>
                        <input type="hidden" name="id" value="<?= $edit_item['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Palika Name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($edit_item['name'] ?? '') ?>" placeholder="e.g. Biratnagar Metropolitan City" required>
                    </div>

                    <div class="form-group">
                        <label>District</label>
                        <input type="text" name="district" value="<?= htmlspecialchars($edit_item['district'] ?? '') ?>" placeholder="e.g. Morang" required>
                    </div>

                    <div class="form-group">
                        <label>Province</label>
                        <input type="text" name="province" value="<?= htmlspecialchars($edit_item['province'] ?? '') ?>" placeholder="e.g. Koshi" required>
                    </div>

                    <div class="form-group">
                        <label>Total Wards</label>
                        <input type="number" name="total_wards" min="1" max="100" value="<?= htmlspecialchars($edit_item['total_wards'] ?? '1') ?>" required>
                    </div>

                    <button type="submit"><?= $edit_item ? 'Update Palika' : 'Create Palika' ?></button>
                    <?php if ($edit_item): ?>
                        <a href="dashboard.php" style="display: block; text-align: center; margin-top: 10px; font-size: 13px; color: #6b7280; text-decoration: none;">Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </div>

            <div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Wards</th>
                            <th>Reviews</th>
                            <th>Overall</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($palika_details as $row): ?>
                            <tr>
                                <td>#<?= $row['id'] ?></td>
                                <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['district']) ?>, <?= htmlspecialchars($row['province']) ?></td>
                                <td><?= $row['total_wards'] ?></td>
                                <td><?= $row['total_reviews'] ?></td>
                                <td><span class="score-badge"><?= $row['avg_overall'] ?: 'N/A' ?></span></td>
                                <td>
                                    <div class="flex-gap">
                                        <a href="dashboard.php?edit=<?= $row['id'] ?>" class="action-btn btn-edit">Edit</a>
                                        <form method="POST" action="dashboard.php" onsubmit="return confirm('Are you sure you want to delete this Palika?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="action-btn btn-delete">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>