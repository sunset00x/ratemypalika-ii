<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$message = '';
$error = '';
$current_admin_username = $_SESSION['admin_username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'update_profile') {
        $new_username = trim($_POST['username']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$current_admin_username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($current_password, $admin['password_hash'])) {
            if (!empty($new_username)) {
                if (!empty($new_password)) {
                    if ($new_password === $confirm_password) {
                        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $update = $pdo->prepare("UPDATE admins SET username = ?, password_hash = ? WHERE id = ?");
                        $update->execute([$new_username, $new_hash, $admin['id']]);
                        $_SESSION['admin_username'] = $new_username;
                        $current_admin_username = $new_username;
                        $message = "Credentials updated successfully.";
                    } else {
                        $error = "Passwords do not match.";
                    }
                } else {
                    $update = $pdo->prepare("UPDATE admins SET username = ? WHERE id = ?");
                    $update->execute([$new_username, $admin['id']]);
                    $_SESSION['admin_username'] = $new_username;
                    $current_admin_username = $new_username;
                    $message = "Username updated successfully.";
                }
            }
        } else {
            $error = "Incorrect current password.";
        }
    }

    if ($_POST['action'] === 'update_system_settings') {
        $site_name = trim($_POST['site_name']);
        $support_email = trim($_POST['support_email']);
        $auto_approve = isset($_POST['auto_approve_reviews']) ? '1' : '0';

        $settings = [
            'site_name' => $site_name,
            'support_email' => $support_email,
            'auto_approve_reviews' => $auto_approve
        ];

        foreach ($settings as $key => $val) {
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $val, $val]);
        }

        $message = "Platform settings updated successfully.";
    }
}

$system_cfg = [];
$cfg_rows = $pdo->query("SELECT * FROM system_settings")->fetchAll();
foreach ($cfg_rows as $row) {
    $system_cfg[$row['setting_key']] = $row['setting_value'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Platform Settings - RateMyPalika Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-content">
        <h1 style="margin-bottom: 20px;">Platform & Security Settings</h1>

        <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <div class="form-container" style="margin: 0; width: 100%;">
                <h3>Admin Credentials</h3>
                <br>
                <form method="POST" action="settings.php">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="form-group">
                        <label>Admin Username</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($current_admin_username) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Current Password *</label>
                        <input type="password" name="current_password" required placeholder="Verify current password">
                    </div>

                    <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">

                    <div class="form-group">
                        <label>New Password (Optional)</label>
                        <input type="password" name="new_password" placeholder="Leave blank to keep current">
                    </div>

                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" placeholder="Re-enter new password">
                    </div>

                    <button type="submit" class="btn-primary" style="width: 100%;">Save Credentials</button>
                </form>
            </div>

            <div class="form-container" style="margin: 0; width: 100%;">
                <h3>Platform Options</h3>
                <br>
                <form method="POST" action="settings.php">
                    <input type="hidden" name="action" value="update_system_settings">

                    <div class="form-group">
                        <label>Site Title</label>
                        <input type="text" name="site_name" value="<?= htmlspecialchars($system_cfg['site_name'] ?? 'RateMyPalika') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Support Email</label>
                        <input type="email" name="support_email" value="<?= htmlspecialchars($system_cfg['support_email'] ?? 'admin@ratemypalika.np') ?>" required>
                    </div>

                    <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 20px;">
                        <input type="checkbox" name="auto_approve_reviews" id="auto_approve" style="width: auto;" <?= ($system_cfg['auto_approve_reviews'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <label for="auto_approve" style="margin: 0;">Automatically Approve Citizen Submissions</label>
                    </div>

                    <button type="submit" class="btn-primary" style="width: 100%; margin-top: 20px;">Save System Settings</button>
                </form>
            </div>
        </div>
    </main>
</div>

</body>
</html>