<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/mail.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_admin') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (!empty($username) && !empty($email) && !empty($password)) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admins (username, email, password_hash, status) VALUES (?, ?, ?, 'Pending Invite')");
                
                try {
                    $stmt->execute([$username, $email, $hash]);
                    
                    // Dispatch invitation email
                    $mail_sent = sendAdminInviteEmail($email, $username, $password);

                    if ($mail_sent) {
                        $message = "Administrator '$username' created and invitation email sent to $email.";
                    } else {
                        $message = "Administrator '$username' created, but local mail server failed to dispatch the email. You can manually share their credentials.";
                    }

                } catch (PDOException $e) {
                    $error = "Username or Email already registered in the system.";
                }

            } else {
                $error = "Please enter a valid email address.";
            }
        } else {
            $error = "All fields (Username, Email, Password) are required.";
        }
    }
}

$admins = $pdo->query("SELECT id, username, email, status, created_at FROM admins ORDER BY id ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User & Admin Roles - RateMyPalika Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-content">
        <h1 style="margin-bottom: 20px;">User & Administrator Roles</h1>

        <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div style="display: grid; grid-template-columns: 340px 1fr; gap: 30px;">
            <div class="form-container" style="margin:0; width:100%;">
                <h3>Invite Administrator</h3>
                <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">An official HTML email invitation with login details will be dispatched upon registration.</p>
                
                <form method="POST" action="users.php">
                    <input type="hidden" name="action" value="create_admin">

                    <div class="form-group">
                        <label>Admin Username</label>
                        <input type="text" name="username" required placeholder="e.g. koirala_admin">
                    </div>

                    <div class="form-group">
                        <label>Official Email Address</label>
                        <input type="email" name="email" required placeholder="e.g. admin@ratemypalika.np">
                    </div>

                    <div class="form-group">
                        <label>Initial Password</label>
                        <input type="password" name="password" required placeholder="Assign password">
                    </div>

                    <button type="submit" class="btn-primary" style="width:100%;">Send Invite & Register</button>
                </form>
            </div>

            <div>
                <h2>System Administrators (<?= count($admins) ?>)</h2>
                <br>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Registered At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $a): ?>
                            <tr>
                                <td>#<?= $a['id'] ?></td>
                                <td><strong><?= htmlspecialchars($a['username']) ?></strong></td>
                                <td><?= htmlspecialchars($a['email'] ?: 'N/A') ?></td>
                                <td>
                                    <span style="font-weight: 600; font-size: 12px; padding: 4px 8px; border-radius: 4px; background: <?= $a['status'] === 'Pending Invite' ? '#fef3c7; color: #92400e;' : '#dcfce7; color: #166534;' ?>">
                                        <?= htmlspecialchars($a['status'] ?: 'Active') ?>
                                    </span>
                                </td>
                                <td><?= $a['created_at'] ?></td>
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