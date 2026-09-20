<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_official') {
        $palika_id = (int)$_POST['palika_id'];
        $name = trim($_POST['name']);
        $position = $_POST['position'];
        $party = trim($_POST['party']);
        $phone = trim($_POST['contact_phone']);
        $email = trim($_POST['email']);

        if ($palika_id > 0 && !empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO officials (palika_id, name, position, party, contact_phone, email) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$palika_id, $name, $position, $party, $phone, $email]);
            $message = "Official '$name' registered successfully.";
        }
    }

    if ($_POST['action'] === 'delete_official') {
        $id = (int)$_POST['official_id'];
        $stmt = $pdo->prepare("DELETE FROM officials WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Official removed.";
    }
}

$palikas = $pdo->query("SELECT id, name, district FROM palikas WHERE status = 'approved' ORDER BY name ASC")->fetchAll();
$officials = $pdo->query("
    SELECT o.*, p.name AS palika_name, p.district 
    FROM officials o 
    JOIN palikas p ON o.palika_id = p.id 
    ORDER BY o.created_at DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Elected Officials Directory - RateMyPalika Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-content">
        <h1 style="margin-bottom: 20px;">Elected Officials Directory</h1>

        <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

        <div style="display: grid; grid-template-columns: 320px 1fr; gap: 30px;">
            <div class="form-container" style="margin:0; width:100%;">
                <h3>Register Official Profile</h3>
                <br>
                <form method="POST" action="officials.php">
                    <input type="hidden" name="action" value="add_official">

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
                        <label>Official's Full Name</label>
                        <input type="text" name="name" required placeholder="e.g. Nagesh Koirala">
                    </div>

                    <div class="form-group">
                        <label>Position / Title</label>
                        <select name="position" required>
                            <option value="Mayor">Mayor / Chairperson</option>
                            <option value="Deputy Mayor">Deputy Mayor / Vice-Chairperson</option>
                            <option value="Ward Chairperson">Ward Chairperson</option>
                            <option value="Chief Administrative Officer">Chief Administrative Officer</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Political Party</label>
                        <input type="text" name="party" placeholder="e.g. Nepali Congress, CPN-UML">
                    </div>

                    <div class="form-group">
                        <label>Contact Phone</label>
                        <input type="text" name="contact_phone" placeholder="Official phone number">
                    </div>

                    <div class="form-group">
                        <label>Official Email</label>
                        <input type="email" name="email" placeholder="official@palika.gov.np">
                    </div>

                    <button type="submit" class="btn-primary" style="width:100%;">Register Official</button>
                </form>
            </div>

            <div>
                <h2>Registered Leaders (<?= count($officials) ?>)</h2>
                <br>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Palika</th>
                            <th>Position</th>
                            <th>Party</th>
                            <th>Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($officials as $o): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($o['name']) ?></strong></td>
                                <td><?= htmlspecialchars($o['palika_name']) ?></td>
                                <td><?= htmlspecialchars($o['position']) ?></td>
                                <td><?= htmlspecialchars($o['party'] ?: 'Independent') ?></td>
                                <td><?= htmlspecialchars($o['contact_phone'] ?: 'N/A') ?></td>
                                <td>
                                    <form method="POST" action="officials.php" onsubmit="return confirm('Remove official?');">
                                        <input type="hidden" name="action" value="delete_official">
                                        <input type="hidden" name="official_id" value="<?= $o['id'] ?>">
                                        <button type="submit" class="btn-action btn-delete">Remove</button>
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