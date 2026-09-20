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
    if ($_POST['action'] === 'save_ward_info') {
        $palika_id = (int)$_POST['palika_id'];
        $ward_number = (int)$_POST['ward_number'];
        $chairperson = trim($_POST['chairperson_name']);
        $contact = trim($_POST['contact_number']);
        $address = trim($_POST['office_address']);

        if ($palika_id > 0 && $ward_number > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO ward_details (palika_id, ward_number, chairperson_name, contact_number, office_address) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE chairperson_name=?, contact_number=?, office_address=?
            ");
            $stmt->execute([$palika_id, $ward_number, $chairperson, $contact, $address, $chairperson, $contact, $address]);
            $message = "Ward #$ward_number information saved successfully.";
        } else {
            $error = "Please select a valid Palika and Ward Number.";
        }
    }
}

$palikas = $pdo->query("SELECT id, name, district, total_wards FROM palikas WHERE status = 'approved' ORDER BY name ASC")->fetchAll();

$ward_records = $pdo->query("
    SELECT w.*, p.name AS palika_name, p.district 
    FROM ward_details w 
    JOIN palikas p ON w.palika_id = p.id 
    ORDER BY p.name ASC, w.ward_number ASC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ward Management - RateMyPalika Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-content">
        <h1 style="margin-bottom: 20px;">Ward Management & Contacts</h1>

        <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div style="display: grid; grid-template-columns: 340px 1fr; gap: 30px;">
            <div class="form-container" style="margin: 0; width: 100%;">
                <h3>Add / Update Ward Details</h3>
                <br>
                <form method="POST" action="wards.php">
                    <input type="hidden" name="action" value="save_ward_info">

                    <div class="form-group">
                        <label>Select Municipality</label>
                        <select name="palika_id" required>
                            <option value="">-- Select Palika --</option>
                            <?php foreach ($palikas as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['district']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Ward Number</label>
                        <input type="number" name="ward_number" min="1" max="100" required placeholder="e.g. 1">
                    </div>

                    <div class="form-group">
                        <label>Ward Chairperson Name</label>
                        <input type="text" name="chairperson_name" placeholder="Full Name">
                    </div>

                    <div class="form-group">
                        <label>Contact Phone</label>
                        <input type="text" name="contact_number" placeholder="Phone Number">
                    </div>

                    <div class="form-group">
                        <label>Office Address</label>
                        <input type="text" name="office_address" placeholder="e.g. Main Chowk, Ward Office">
                    </div>

                    <button type="submit" class="btn-primary" style="width: 100%;">Save Ward Contact</button>
                </form>
            </div>

            <div>
                <h2>Configured Ward Contacts (<?= count($ward_records) ?>)</h2>
                <br>
                <?php if (empty($ward_records)): ?>
                    <p style="color: #64748b;">No specific ward chairperson contacts added yet.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Palika</th>
                                <th>Ward #</th>
                                <th>Chairperson</th>
                                <th>Contact</th>
                                <th>Office Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ward_records as $w): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($w['palika_name']) ?></strong> (<?= htmlspecialchars($w['district']) ?>)</td>
                                    <td>Ward <?= $w['ward_number'] ?></td>
                                    <td><?= htmlspecialchars($w['chairperson_name'] ?: 'N/A') ?></td>
                                    <td><?= htmlspecialchars($w['contact_number'] ?: 'N/A') ?></td>
                                    <td><?= htmlspecialchars($w['office_address'] ?: 'N/A') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

</body>
</html>