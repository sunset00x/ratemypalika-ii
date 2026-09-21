<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) \vert{}\vert{}$_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$message = '';$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // 1. Add New Category
    if ($_POST['action'] === 'add_category') {
        $category_key = strtolower(trim($_POST['category_key']));
        $category_key = preg_replace('/[^a-z0-9_]/', '_',$category_key); // sanitize key format
        $display_name = trim($_POST['display_name']);
        $description = trim($_POST['description']);
        $weightage = (float)$_POST['weightage'];

        if (!empty($category_key) && !empty($display_name) &&$weightage >= 0) {
            $stmt =$pdo->prepare("SELECT COUNT(*) FROM scoring_categories WHERE category_key = ?");
            $stmt->execute([$category_key]);
            
            if ($stmt->fetchColumn() > 0) {$error = "Category key '$category_key' already exists. Please choose a unique key.";
            } else {
                $insert =$pdo->prepare("INSERT INTO scoring_categories (category_key, display_name, description, weightage, is_active) VALUES (?, ?, ?, ?, 1)");
                $insert->execute([$category_key,$display_name, $description,$weightage]);

                // Dynamically add column to reviews table if it doesn't exist
                try {
                    $pdo->exec("ALTER TABLE reviews ADD COLUMN `$category_key` DECIMAL(3,2) DEFAULT 3.00");
                } catch (PDOException $e) {
                    // Column may already exist
                }

                $message = "New category '$display_name' added successfully.";
            }
        } else {
            $error = "Please fill in all required fields accurately.";
        }
    }

    // 2. Update Existing Category Details
    if ($_POST['action'] === 'update_category') {
        $cat_id = (int)$_POST['category_id'];
        $display_name = trim($_POST['display_name']);
        $description = trim($_POST['description']);
        $weightage = (float)$_POST['weightage'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($cat_id > 0 && !empty($display_name) &&$weightage >= 0) {
            $update =$pdo->prepare("UPDATE scoring_categories SET display_name = ?, description = ?, weightage = ?, is_active = ? WHERE id = ?");
            $update->execute([$display_name, $description,$weightage, $is_active,$cat_id]);
            $message = "Category #$cat_id updated successfully.";
        } else {
            $error = "Failed to update category details.";
        }
    }

    // 3. Quick Bulk Weightage Updates
    if ($_POST['action'] === 'update_all_weights') {
        foreach ($_POST['weight'] as $cat_id =>$val) {
            $w = (float)$val;
            $stmt =$pdo->prepare("UPDATE scoring_categories SET weightage = ? WHERE id = ?");
            $stmt->execute([$w,$cat_id]);
        }
        $message = "Scoring weightages updated successfully.";
    }

    // 4. Delete Category
    if ($_POST['action'] === 'delete_category') {
        $cat_id = (int)$_POST['category_id'];
        if ($cat_id > 0) {
            $stmt =$pdo->prepare("DELETE FROM scoring_categories WHERE id = ?");
            $stmt->execute([$cat_id]);
            $message = "Category #$cat_id removed successfully.";
        }
    }
}

// Fetch item for edit mode
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_stmt =$pdo->prepare("SELECT * FROM scoring_categories WHERE id = ?");
    $edit_stmt->execute([$edit_id]);
    $edit_item =$edit_stmt->fetch();
}

$categories =$pdo->query("SELECT * FROM scoring_categories ORDER BY id ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Categories & Scoring - RateMyPalika Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-content">
        <h1 style="margin-bottom: 20px;">Categories & Rating Scoring Management</h1>

        <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div style="display: grid; grid-template-columns: 340px 1fr; gap: 30px;">
            
            <!-- Left Column: Add / Edit Form -->
            <div class="form-container" style="margin: 0; width: 100%;">
                <h3><?= $edit_item ? 'Edit Category #' . $edit_item['id'] : 'Add New Category' ?></h3>
                <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">
                    <?= $edit_item ? 'Modify category title, weightage, or visibility.' : 'Create a new evaluation criteria dimension for palika ratings.' ?>
                </p>

                <form method="POST" action="categories.php">
                    <input type="hidden" name="action" value="<?= $edit_item ? 'update_category' : 'add_category' ?>">
                    <?php if ($edit_item): ?>
                        <input type="hidden" name="category_id" value="<?= $edit_item['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Category Key (System Name)</label>
                        <input type="text" name="category_key" value="<?= htmlspecialchars($edit_item['category_key'] ?? '') ?>" placeholder="e.g. education_quality" <?= $edit_item ? 'readonly style="background:#f1f5f9;"' : 'required' ?>>
                        <small style="color: #64748b; font-size: 11px;">Lowercase letters and underscores only (e.g. road_infrastructure).</small>
                    </div>

                    <div class="form-group">
                        <label>Display Name (Public Label)</label>
                        <input type="text" name="display_name" value="<?= htmlspecialchars($edit_item['display_name'] ?? '') ?>" required placeholder="e.g. Education & Schools">
                    </div>

                    <div class="form-group">
                        <label>Short Description</label>
                        <input type="text" name="description" value="<?= htmlspecialchars($edit_item['description'] ?? '') ?>" placeholder="e.g. Quality of public schools and literacy programs">
                    </div>

                    <div class="form-group">
                        <label>Score Weightage Factor</label>
                        <input type="number" step="0.1" name="weightage" value="<?= htmlspecialchars($edit_item['weightage'] ?? '1.0') ?>" min="0.1" max="5.0" required>
                        <small style="color: #64748b; font-size: 11px;">Default is 1.0. Higher values weigh more in index calculations.</small>
                    </div>

                    <?php if ($edit_item): ?>
                        <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                            <input type="checkbox" name="is_active" id="is_active" style="width: auto;" <?= ($edit_item['is_active'] ?? 1) == 1 ? 'checked' : '' ?>>
                            <label for="is_active" style="margin: 0;">Active in Rating Form</label>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn-primary" style="width: 100%; margin-top: 15px;"><?= $edit_item ? 'Update Category' : 'Add Category' ?></button>
                    
                    <?php if ($edit_item): ?>
                        <a href="categories.php" style="display: block; text-align: center; margin-top: 12px; font-size: 13px; color: #6b7280; text-decoration: none;">Cancel Editing</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Right Column: Active Categories List -->
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h2>Active Rating Categories (<?= count($categories) ?>)</h2>
                </div>

                <form method="POST" action="categories.php">
                    <input type="hidden" name="action" value="update_all_weights">

                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Display Name & Key</th>
                                <th>Weightage</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: #64748b;">No categories configured.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as$cat): ?>
                                    <tr>
                                        <td>#<?= $cat['id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($cat['display_name']) ?></strong>
                                            <div style="font-size: 12px; color: #64748b;">Key: <code><?= htmlspecialchars($cat['category_key']) ?></code></div>
                                            <?php if (!empty($cat['description'])): ?>
                                                <div style="font-size: 11px; color: #94a3b8;"><?= htmlspecialchars($cat['description']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <input type="number" step="0.1" name="weight[<?= $cat['id'] ?>]" value="<?= $cat['weightage'] ?>" style="width: 70px; text-align: center; padding: 4px;">
                                        </td>
                                        <td>
                                            <span style="font-weight: 600; font-size: 12px; padding: 3px 8px; border-radius: 4px; background: <?= $cat['is_active'] ? '#dcfce7; color: #166534;' : '#fef3c7; color: #92400e;' ?>">
                                                <?= $cat['is_active'] ? 'Active' : 'Disabled' ?>
                                            </span>
                                        </td>
                                        <td style="display: flex; gap: 8px;">
                                            <a href="categories.php?edit=<?= $cat['id'] ?>" class="btn-action btn-edit">Edit</a>
                                            <form method="POST" action="categories.php" onsubmit="return confirm('Delete category? Note: This will remove this dimension from future forms.');">
                                                <input type="hidden" name="action" value="delete_category">
                                                <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                                <button type="submit" class="btn-action btn-delete">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <div style="margin-top: 16px; text-align: right;">
                        <button type="submit" class="btn-primary" style="background: #0f172a;">Save Bulk Weightages</button>
                    </div>
                </form>
            </div>

        </div>
    </main>
</div><?php
session_start();
if (!isset($_SESSION['admin_logged_in']) \vert{}\vert{}$_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$message = '';$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // 1. Add New Category
    if ($_POST['action'] === 'add_category') {
        $category_key = strtolower(trim($_POST['category_key']));
        $category_key = preg_replace('/[^a-z0-9_]/', '_',$category_key); // sanitize key format
        $display_name = trim($_POST['display_name']);
        $description = trim($_POST['description']);
        $weightage = (float)$_POST['weightage'];

        if (!empty($category_key) && !empty($display_name) &&$weightage >= 0) {
            $stmt =$pdo->prepare("SELECT COUNT(*) FROM scoring_categories WHERE category_key = ?");
            $stmt->execute([$category_key]);
            
            if ($stmt->fetchColumn() > 0) {$error = "Category key '$category_key' already exists. Please choose a unique key.";
            } else {
                $insert =$pdo->prepare("INSERT INTO scoring_categories (category_key, display_name, description, weightage, is_active) VALUES (?, ?, ?, ?, 1)");
                $insert->execute([$category_key,$display_name, $description,$weightage]);

                // Dynamically add column to reviews table if it doesn't exist
                try {
                    $pdo->exec("ALTER TABLE reviews ADD COLUMN `$category_key` DECIMAL(3,2) DEFAULT 3.00");
                } catch (PDOException $e) {
                    // Column may already exist
                }

                $message = "New category '$display_name' added successfully.";
            }
        } else {
            $error = "Please fill in all required fields accurately.";
        }
    }

    // 2. Update Existing Category Details
    if ($_POST['action'] === 'update_category') {
        $cat_id = (int)$_POST['category_id'];
        $display_name = trim($_POST['display_name']);
        $description = trim($_POST['description']);
        $weightage = (float)$_POST['weightage'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($cat_id > 0 && !empty($display_name) &&$weightage >= 0) {
            $update =$pdo->prepare("UPDATE scoring_categories SET display_name = ?, description = ?, weightage = ?, is_active = ? WHERE id = ?");
            $update->execute([$display_name, $description,$weightage, $is_active,$cat_id]);
            $message = "Category #$cat_id updated successfully.";
        } else {
            $error = "Failed to update category details.";
        }
    }

    // 3. Quick Bulk Weightage Updates
    if ($_POST['action'] === 'update_all_weights') {
        foreach ($_POST['weight'] as $cat_id =>$val) {
            $w = (float)$val;
            $stmt =$pdo->prepare("UPDATE scoring_categories SET weightage = ? WHERE id = ?");
            $stmt->execute([$w,$cat_id]);
        }
        $message = "Scoring weightages updated successfully.";
    }

    // 4. Delete Category
    if ($_POST['action'] === 'delete_category') {
        $cat_id = (int)$_POST['category_id'];
        if ($cat_id > 0) {
            $stmt =$pdo->prepare("DELETE FROM scoring_categories WHERE id = ?");
            $stmt->execute([$cat_id]);
            $message = "Category #$cat_id removed successfully.";
        }
    }
}

// Fetch item for edit mode
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_stmt =$pdo->prepare("SELECT * FROM scoring_categories WHERE id = ?");
    $edit_stmt->execute([$edit_id]);
    $edit_item =$edit_stmt->fetch();
}

$categories =$pdo->query("SELECT * FROM scoring_categories ORDER BY id ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Categories & Scoring - RateMyPalika Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-content">
        <h1 style="margin-bottom: 20px;">Categories & Rating Scoring Management</h1>

        <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div style="display: grid; grid-template-columns: 340px 1fr; gap: 30px;">
            
            <!-- Left Column: Add / Edit Form -->
            <div class="form-container" style="margin: 0; width: 100%;">
                <h3><?= $edit_item ? 'Edit Category #' . $edit_item['id'] : 'Add New Category' ?></h3>
                <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">
                    <?= $edit_item ? 'Modify category title, weightage, or visibility.' : 'Create a new evaluation criteria dimension for palika ratings.' ?>
                </p>

                <form method="POST" action="categories.php">
                    <input type="hidden" name="action" value="<?= $edit_item ? 'update_category' : 'add_category' ?>">
                    <?php if ($edit_item): ?>
                        <input type="hidden" name="category_id" value="<?= $edit_item['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Category Key (System Name)</label>
                        <input type="text" name="category_key" value="<?= htmlspecialchars($edit_item['category_key'] ?? '') ?>" placeholder="e.g. education_quality" <?= $edit_item ? 'readonly style="background:#f1f5f9;"' : 'required' ?>>
                        <small style="color: #64748b; font-size: 11px;">Lowercase letters and underscores only (e.g. road_infrastructure).</small>
                    </div>

                    <div class="form-group">
                        <label>Display Name (Public Label)</label>
                        <input type="text" name="display_name" value="<?= htmlspecialchars($edit_item['display_name'] ?? '') ?>" required placeholder="e.g. Education & Schools">
                    </div>

                    <div class="form-group">
                        <label>Short Description</label>
                        <input type="text" name="description" value="<?= htmlspecialchars($edit_item['description'] ?? '') ?>" placeholder="e.g. Quality of public schools and literacy programs">
                    </div>

                    <div class="form-group">
                        <label>Score Weightage Factor</label>
                        <input type="number" step="0.1" name="weightage" value="<?= htmlspecialchars($edit_item['weightage'] ?? '1.0') ?>" min="0.1" max="5.0" required>
                        <small style="color: #64748b; font-size: 11px;">Default is 1.0. Higher values weigh more in index calculations.</small>
                    </div>

                    <?php if ($edit_item): ?>
                        <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                            <input type="checkbox" name="is_active" id="is_active" style="width: auto;" <?= ($edit_item['is_active'] ?? 1) == 1 ? 'checked' : '' ?>>
                            <label for="is_active" style="margin: 0;">Active in Rating Form</label>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn-primary" style="width: 100%; margin-top: 15px;"><?= $edit_item ? 'Update Category' : 'Add Category' ?></button>
                    
                    <?php if ($edit_item): ?>
                        <a href="categories.php" style="display: block; text-align: center; margin-top: 12px; font-size: 13px; color: #6b7280; text-decoration: none;">Cancel Editing</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Right Column: Active Categories List -->
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h2>Active Rating Categories (<?= count($categories) ?>)</h2>
                </div>

                <form method="POST" action="categories.php">
                    <input type="hidden" name="action" value="update_all_weights">

                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Display Name & Key</th>
                                <th>Weightage</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: #64748b;">No categories configured.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as$cat): ?>
                                    <tr>
                                        <td>#<?= $cat['id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($cat['display_name']) ?></strong>
                                            <div style="font-size: 12px; color: #64748b;">Key: <code><?= htmlspecialchars($cat['category_key']) ?></code></div>
                                            <?php if (!empty($cat['description'])): ?>
                                                <div style="font-size: 11px; color: #94a3b8;"><?= htmlspecialchars($cat['description']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <input type="number" step="0.1" name="weight[<?= $cat['id'] ?>]" value="<?= $cat['weightage'] ?>" style="width: 70px; text-align: center; padding: 4px;">
                                        </td>
                                        <td>
                                            <span style="font-weight: 600; font-size: 12px; padding: 3px 8px; border-radius: 4px; background: <?= $cat['is_active'] ? '#dcfce7; color: #166534;' : '#fef3c7; color: #92400e;' ?>">
                                                <?= $cat['is_active'] ? 'Active' : 'Disabled' ?>
                                            </span>
                                        </td>
                                        <td style="display: flex; gap: 8px;">
                                            <a href="categories.php?edit=<?= $cat['id'] ?>" class="btn-action btn-edit">Edit</a>
                                            <form method="POST" action="categories.php" onsubmit="return confirm('Delete category? Note: This will remove this dimension from future forms.');">
                                                <input type="hidden" name="action" value="delete_category">
                                                <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                                <button type="submit" class="btn-action btn-delete">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <div style="margin-top: 16px; text-align: right;">
                        <button type="submit" class="btn-primary" style="background: #0f172a;">Save Bulk Weightages</button>
                    </div>
                </form>
            </div>

        </div>
    </main>
</div>

</body>
</html>

</body>
</html>