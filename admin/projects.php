<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_project') {
        $palika_id = (int)$_POST['palika_id'];
        $title = trim($_POST['title']);
        $budget = (float)$_POST['allocated_budget'];
        $fiscal_year = trim($_POST['fiscal_year']);
        $status = $_POST['status'];
        $ward_number = (int)$_POST['ward_number'];

        if ($palika_id > 0 && !empty($title) && $budget > 0) {
            $stmt = $pdo->prepare("INSERT INTO projects (palika_id, title, allocated_budget, fiscal_year, status, ward_number) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$palika_id, $title, $budget, $fiscal_year, $status, $ward_number]);
            $message = "Project '$title' recorded successfully.";
        }
    }
}

$palikas = $pdo->query("SELECT id, name, district FROM palikas WHERE status = 'approved' ORDER BY name ASC")->fetchAll();
$projects = $pdo->query("
    SELECT pr.*, p.name AS palika_name 
    FROM projects pr 
    JOIN palikas p ON pr.palika_id = p.id 
    ORDER BY pr.created_at DESC
")->fetchAll();

$total_budget_sum = $pdo->query("SELECT SUM(allocated_budget) FROM projects")->fetchColumn() ?: 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Projects & Budgets - RateMyPalika Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-content">
        <h1 style="margin-bottom: 20px;">Public Projects & Budget Tracker</h1>

        <div class="stat-grid" style="margin-bottom: 24px;">
            <div class="stat-card">
                <div class="label">Total Monitored Projects</div>
                <div class="num"><?= count($projects) ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Total Budget Tracked</div>
                <div class="num" style="color: #16a34a;">NPR <?= number_format($total_budget_sum, 2) ?></div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 320px 1fr; gap: 30px;">
            <div class="form-container" style="margin:0; width:100%;">
                <h3>Record Development Project</h3>
                <br>
                <form method="POST" action="projects.php">
                    <input type="hidden" name="action" value="add_project">

                    <div class="form-group">
                        <label>Municipality</label>
                        <select name="palika_id" required>
                            <option value="">-- Select Palika --</option>
                            <?php foreach ($palikas as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['district']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Project Title</label>
                        <input type="text" name="title" required placeholder="e.g. Ring Road Drainage Extension">
                    </div>

                    <div class="form-group">
                        <label>Allocated Budget (NPR)</label>
                        <input type="number" step="0.01" name="allocated_budget" required placeholder="e.g. 5000000">
                    </div>

                    <div class="form-group">
                        <label>Fiscal Year</label>
                        <input type="text" name="fiscal_year" value="2081/82" required>
                    </div>

                    <div class="form-group">
                        <label>Target Ward</label>
                        <input type="number" name="ward_number" placeholder="Leave blank if municipal-wide">
                    </div>

                    <div class="form-group">
                        <label>Execution Status</label>
                        <select name="status">
                            <option value="Planned">Planned</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                            <option value="Halted">Halted</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-primary" style="width:100%;">Save Project</button>
                </form>
            </div>

            <div>
                <h2>Tracked Infrastructure Projects</h2>
                <br>
                <table>
                    <thead>
                        <tr>
                            <th>Project Title</th>
                            <th>Palika</th>
                            <th>Budget (NPR)</th>
                            <th>Fiscal Year</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $pr): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($pr['title']) ?></strong></td>
                                <td><?= htmlspecialchars($pr['palika_name']) ?> <?= $pr['ward_number'] ? '(Ward ' . $pr['ward_number'] . ')' : '' ?></td>
                                <td><?= number_format($pr['allocated_budget'], 2) ?></td>
                                <td><?= htmlspecialchars($pr['fiscal_year']) ?></td>
                                <td><strong><?= htmlspecialchars($pr['status']) ?></strong></td>
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