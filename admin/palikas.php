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
    if ($_POST['action'] === 'approve_palika') {
        $p_id = (int)$_POST['palika_id'];
        $stmt = $pdo->prepare("UPDATE palikas SET status = 'approved' WHERE id = ?");
        $stmt->execute([$p_id]);
        $message = "Municipality #$p_id approved successfully.";
    }

    if ($_POST['action'] === 'create_palika') {
        $name = trim($_POST['name']);
        $district = trim($_POST['district']);
        $province = trim($_POST['province']);
        $type = trim($_POST['type']);
        $total_wards = (int)$_POST['total_wards'];

        if (!empty($name) && !empty($district) && !empty($province) && $total_wards > 0) {
            $stmt = $pdo->prepare("INSERT INTO palikas (name, district, province, type, total_wards, status) VALUES (?, ?, ?, ?, ?, 'approved')");
            $stmt->execute([$name, $district, $province, $type, $total_wards]);
            $message = "New Palika added.";
        } else {
            $error = "Please fill in all required fields.";
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

$query = "SELECT * FROM palikas WHERE status = 'approved'";
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

$pending_palikas = $pdo->query("SELECT * FROM palikas WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll();

$provinces = ['Koshi', 'Madhesh', 'Bagmati', 'Gandaki', 'Lumbini', 'Karnali', 'Sudurpashchim'];
$types = ['Metropolitan City', 'Sub-Metropolitan City', 'Municipality', 'Rural Municipality'];

$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_stmt = $pdo->prepare("SELECT * FROM palikas WHERE id = ?");
    $edit_stmt->execute([$edit_id]);
    $edit_item = $edit_stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Municipalities Management - RateMyPalika Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-content">
        <h1 style="margin-bottom: 20px;">Municipalities Management</h1>

        <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div style="display: grid; grid-template-columns: 320px 1fr; gap: 30px;">
            <div class="form-container" style="margin:0; width:100%;">
                <h3><?= $edit_item ? 'Edit Palika #' . $edit_item['id'] : 'Add New Palika' ?></h3>
                <br>
                <form method="POST" action="palikas.php">
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
                </form>
            </div>

            <div>
                <form method="GET" action="palikas.php" class="filter-bar" style="margin-bottom: 24px;">
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

                    <a href="palikas.php" class="btn-clear">Reset</a>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>District</th>
                            <th>Type</th>
                            <th>Wards</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($palikas as $p): ?>
                            <tr>
                                <td>#<?= $p['id'] ?></td>
                                <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                <td><?= htmlspecialchars($p['district']) ?></td>
                                <td><?= htmlspecialchars($p['type']) ?></td>
                                <td><?= $p['total_wards'] ?></td>
                                <td style="display: flex; gap: 8px;">
                                    <a href="palikas.php?edit=<?= $p['id'] ?>" class="btn-action btn-edit">Edit</a>
                                    <form method="POST" action="palikas.php" onsubmit="return confirm('Delete Palika?');">
                                        <input type="hidden" name="action" value="delete_palika">
                                        <input type="hidden" name="palika_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn-action btn-delete">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

</body>
</html>