<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$section = $_GET['section'] ?? 'dashboard';
$message = '';
$error = '';

$current_admin_username = $_SESSION['admin_username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'approve_palika') {
        $p_id = (int)$_POST['palika_id'];
        $stmt = $pdo->prepare("UPDATE palikas SET status = 'approved' WHERE id = ?");
        $stmt->execute([$p_id]);
        $message = "Municipality #$p_id approved and published live.";
    }

    if ($_POST['action'] === 'approve_review') {
        $rev_id = (int)$_POST['review_id'];
        $stmt = $pdo->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?");
        $stmt->execute([$rev_id]);
        $message = "Review #$rev_id approved successfully.";
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
            $stmt = $pdo->prepare("INSERT INTO palikas (name, district, province, type, total_wards, status) VALUES (?, ?, ?, ?, ?, 'approved')");
            $stmt->execute([$name, $district, $province, $type, $total_wards]);
            $message = "New Palika added and published.";
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

$pending_palikas = $pdo->query("SELECT * FROM palikas WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll();

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

$pending_reviews = $pdo->query("
    SELECT r.*, p.name AS palika_name 
    FROM reviews r 
    JOIN palikas p ON r.palika_id = p.id 
    WHERE r.status = 'pending' 
    ORDER BY r.created_at DESC
")->fetchAll();

$total_palikas = $pdo->query("SELECT COUNT(*) FROM palikas WHERE status = 'approved'")->fetchColumn();
$total_reviews = $pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'approved'")->fetchColumn();
$pending_count = count($pending_reviews);
$pending_palika_count = count($pending_palikas);

$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_stmt = $pdo->prepare("SELECT * FROM palikas WHERE id = ?");
    $edit_stmt->execute([$edit_id]);
    $edit_item = $edit_stmt->fetch();
}

$provinces = ['Koshi', 'Madhesh', 'Bagmati', 'Gandaki', 'Lumbini', 'Karnali', 'Sudurpashchim'];
$types = ['Metropolitan City', 'Sub-Metropolitan City', 'Municipality', 'Rural Municipality'];

$menu_items = [
    'dashboard' => ['label' => 'Dashboard Overview', 'icon' => '📊'],
    'palikas' => ['label' => 'Municipalities', 'icon' => '🏛️'],
    'wards' => ['label' => 'Ward Management', 'icon' => '📍'],
    'reviews' => ['label' => 'Review Moderation', 'icon' => '💬'],
    'analytics' => ['label' => 'Analytics & Reports', 'icon' => '📈'],
    'officials' => ['label' => 'Elected Officials', 'icon' => '👤'],
    'projects' => ['label' => 'Projects & Budgets', 'icon' => '🏗️'],
    'categories' => ['label' => 'Categories & Scoring', 'icon' => '⚙️'],
    'users' => ['label' => 'User & Admin Roles', 'icon' => '🔐'],
    'settings' => ['label' => 'Platform Settings', 'icon' => '🛠️'],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - RateMyPalika</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="admin-layout">

    <aside class="admin-sidebar">
        <div>
            <div class="sidebar-header">
                <a href="dashboard.php" class="sidebar-brand">RateMyPalika Admin</a>
            </div>
            <ul class="sidebar-menu">
                <?php foreach ($menu_items as $key => $item): ?>
                    <li>
                        <a href="dashboard.php?section=<?= $key ?>" class="<?= $section === $key ? 'active' : '' ?>">
                            <span><?= $item['icon'] ?></span>
                            <span><?= $item['label'] ?></span>
                            <?php if ($key === 'palikas' && $pending_palika_count > 0): ?>
                                <span style="background: #f59e0b; color: white; font-size: 11px; padding: 2px 6px; border-radius: 10px; margin-left: auto;"><?= $pending_palika_count ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="sidebar-footer">
            <div style="color: #94a3b8; font-weight: 600; margin-bottom: 8px;">Logged as: <?= htmlspecialchars($_SESSION['admin_username']) ?></div>
            <a href="../index.php" target="_blank" style="color: #38bdf8; text-decoration: none; font-size: 12px; display: block; margin-bottom: 6px;">View Live Site ↗</a>
            <a href="login.php" style="color: #ef4444; text-decoration: none; font-size: 12px; display: block;">Sign Out</a>
        </div>
    </aside>

    <main class="admin-content">

        <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <?php if ($section === 'dashboard'): ?>
            <h1 style="margin-bottom: 20px;">System Overview</h1>
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="label">Approved Municipalities</div>
                    <div class="num"><?= $total_palikas ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Pending Palika Requests</div>
                    <div class="num" style="color: #f59e0b;"><?= $pending_palika_count ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Approved Reviews</div>
                    <div class="num"><?= $total_reviews ?></div>
                </div>
                <div class="stat-card">
                    <div class="label">Pending Reviews</div>
                    <div class="num" style="color: #ef4444;"><?= $pending_count ?></div>
                </div>
            </div>

            <?php if (!empty($pending_palikas)): ?>
                <h2>Pending Municipality Creation Requests</h2>
                <br>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>District</th>
                            <th>Province</th>
                            <th>Type</th>
                            <th>Wards</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_palikas as $req): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($req['name']) ?></strong></td>
                                <td><?= htmlspecialchars($req['district']) ?></td>
                                <td><?= htmlspecialchars($req['province']) ?></td>
                                <td><?= htmlspecialchars($req['type']) ?></td>
                                <td><?= $req['total_wards'] ?></td>
                                <td style="display: flex; gap: 8px;">
                                    <form method="POST" action="dashboard.php?section=dashboard">
                                        <input type="hidden" name="action" value="approve_palika">
                                        <input type="hidden" name="palika_id" value="<?= $req['id'] ?>">
                                        <button type="submit" class="btn-primary" style="background: #16a34a; padding: 6px 12px; font-size: 12px;">Approve & Publish</button>
                                    </form>
                                    <form method="POST" action="dashboard.php?section=dashboard">
                                        <input type="hidden" name="action" value="delete_palika">
                                        <input type="hidden" name="palika_id" value="<?= $req['id'] ?>">
                                        <button type="submit" class="btn-primary" style="background: #dc2626; padding: 6px 12px; font-size: 12px;">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <?php elseif ($section === 'palikas'): ?>
            <h1 style="margin-bottom: 20px;">Municipalities Management</h1>
            <div style="display: grid; grid-template-columns: 320px 1fr; gap: 30px;">
                <div class="form-container" style="margin:0; width:100%;">
                    <h3><?= $edit_item ? 'Edit Palika #' . $edit_item['id'] : 'Add New Palika' ?></h3>
                    <br>
                    <form method="POST" action="dashboard.php?section=palikas">
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
                    <?php if (!empty($pending_palikas)): ?>
                        <h2 style="margin-bottom: 12px; color: #d97706;">Pending User Requests (<?= count($pending_palikas) ?>)</h2>
                        <table style="margin-bottom: 30px;">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>District</th>
                                    <th>Type</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_palikas as $req): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($req['name']) ?></strong></td>
                                        <td><?= htmlspecialchars($req['district']) ?></td>
                                        <td><?= htmlspecialchars($req['type']) ?></td>
                                        <td style="display: flex; gap: 8px;">
                                            <form method="POST" action="dashboard.php?section=palikas">
                                                <input type="hidden" name="action" value="approve_palika">
                                                <input type="hidden" name="palika_id" value="<?= $req['id'] ?>">
                                                <button type="submit" class="btn-primary" style="background: #16a34a; padding: 6px 12px; font-size: 12px;">Approve</button>
                                            </form>
                                            <form method="POST" action="dashboard.php?section=palikas">
                                                <input type="hidden" name="action" value="delete_palika">
                                                <input type="hidden" name="palika_id" value="<?= $req['id'] ?>">
                                                <button type="submit" class="btn-primary" style="background: #dc2626; padding: 6px 12px; font-size: 12px;">Reject</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <h2>Approved Municipalities Directory</h2>
                    <br>
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
                                        <a href="dashboard.php?section=palikas&edit=<?= $p['id'] ?>" class="btn-action btn-edit">Edit</a>
                                        <form method="POST" action="dashboard.php?section=palikas" onsubmit="return confirm('Delete Palika?');">
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

        <?php else: ?>
            <h1 style="margin-bottom: 12px;"><?= htmlspecialchars($menu_items[$section]['label'] ?? 'Module') ?></h1>
            <p style="color: #64748b;">Module section active: <code><?= htmlspecialchars($section) ?></code>.</p>
        <?php endif; ?>

    </main>

</div>

</body>
</html>