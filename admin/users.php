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
    if ($_POST['action'] === 'create_admin') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        if (!empty($username) && !empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
            try {
                $stmt->execute([$username, $hash]);
                $message = "New administrator '$username' created successfully.";
            } catch (PDOException $e) {
                $error = "Username already exists.";
            }
        }
    }
}

$admins = $pdo->query("SELECT id, username, created_at FROM admins ORDER BY id ASC")->fetchAll();
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

        <div style="display: grid; grid-template-columns: 320px 1fr; gap: 30px;">
            <div class="form-container" style="margin:0; width:100%;">
                <h3>Create New Administrator</h3>
                <br>
                <form method="POST" action="users.php">
                    <input type="hidden" name="action" value="create_admin">

                    <div class="form-group">
                        <label>Admin Username</label>
                        <input type="text" name="username" required placeholder="New username">
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required placeholder="Strong password">
                    </div>

                    <button type="submit" class="btn-primary" style="width:100%;">Create Admin</button>
                </form>
            </div>

            <div>
                <h2>System Administrators</h2>
                <br>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $a): ?>
                            <tr>
                                <td>#<?= $a['id'] ?></td>
                                <td><strong><?= htmlspecialchars($a['username']) ?></strong></td>
                                <td>Super Admin</td>
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