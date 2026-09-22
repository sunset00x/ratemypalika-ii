<?php
require_once __DIR__ . '/config/db.php';

$message = '';
$error = '';

$palikas = $pdo->query("SELECT * FROM palikas WHERE status = 'approved' ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $palika_id = (int)$_POST['palika_id'];
    $ward_number = (int)$_POST['ward_number'];
    $category = $_POST['category'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $contact = trim($_POST['reporter_contact']);

    $image_path = null;
    if (isset($_FILES['issue_image']) && $_FILES['issue_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['issue_image']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array(strtolower($ext), $allowed)) {
            $filename = 'issue_' . time() . '_' . uniqid() . '.' . $ext;
            $destination = __DIR__ . '/uploads/' . $filename;
            if (!is_dir(__DIR__ . '/uploads/')) {
                mkdir(__DIR__ . '/uploads/', 0777, true);
            }
            if (move_uploaded_file($_FILES['issue_image']['tmp_name'], $destination)) {
                $image_path = 'uploads/' . $filename;
            }
        }
    }

    if ($palika_id > 0 && !empty($title) && !empty($description)) {
        $stmt = $pdo->prepare("INSERT INTO citizen_issues (palika_id, ward_number, category, title, description, image_url, reporter_contact) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$palika_id, $ward_number, $category, $title, $description, $image_path, $contact]);
        $message = "Your issue report has been submitted to municipal officers for investigation.";
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report an Issue - RateMyPalika</title>
    <link rel="stylesheet" href="css/style.css">

    <!-- Tom Select Searchable Dropdown Assets -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script src="js/script.js" defer></script>

    <style>
        .ts-control {
            border-radius: 8px !important;
            padding: 10px 14px !important;
            border: 1px solid #cbd5e1 !important;
            font-size: 14px !important;
            background-color: #ffffff !important;
        }
        .ts-dropdown {
            border-radius: 8px !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
            max-height: 280px !important;
        }
        .ts-dropdown .dropdown-input {
            padding: 8px 12px !important;
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">RateMyPalika</a>
        <ul class="nav-links">
            <li><a href="municipalities.php">Municipalities</a></li>
            <li><a href="compare.php">Compare</a></li>
            <li><a href="rankings.php">Rankings</a></li>
            <li><a href="submit_issue.php" class="active">Report Issue</a></li>
            <li><a href="submit_review.php">Rate Now</a></li>
        </ul>
    </nav>

    <div class="form-container">
        <h2>Report Local Municipal Issue</h2>
        <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">Report potholes, uncollected waste, water supply breakdowns, or street lighting failures.</p>

        <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="POST" action="submit_issue.php" enctype="multipart/form-data">
            <div class="form-group">
                <label>Select Municipality</label>
                <select name="palika_id" id="palikaSelect" class="searchable-select" required>
                    <option value="">-- Type to Search or Scroll Full List --</option>
                    <?php foreach ($palikas as $p): ?>
                        <option value="<?= $p['id'] ?>" data-wards="<?= $p['total_wards'] ?>">
                            <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['district']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Ward Number</label>
                <select name="ward_number" id="wardSelect" required>
                    <option value="">-- Select Palika First --</option>
                </select>
            </div>

            <div class="form-group">
                <label>Issue Category</label>
                <select name="category" required>
                    <option value="Roads & Infrastructure">Roads & Infrastructure</option>
                    <option value="Waste Management">Waste Management & Sanitation</option>
                    <option value="Health Services">Health Services</option>
                    <option value="Water & Electricity">Water & Electricity</option>
                    <option value="Other">Other Civil Matter</option>
                </select>
            </div>

            <div class="form-group">
                <label>Issue Title</label>
                <input type="text" name="title" placeholder="e.g. Broken drainage pipe on Main Road" required>
            </div>

            <div class="form-group">
                <label>Detailed Description</label>
                <textarea name="description" rows="4" placeholder="Specify landmark location and duration of problem..." required></textarea>
            </div>

            <div class="form-group">
                <label>Photo Evidence (Optional)</label>
                <input type="file" name="issue_image" accept="image/*">
            </div>

            <div class="form-group">
                <label>Your Phone / Email (Optional for updates)</label>
                <input type="text" name="reporter_contact" placeholder="For resolution notifications">
            </div>

            <button type="submit" class="btn-primary" style="width: 100%;">Submit Issue Report</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var selectEl = document.getElementById('palikaSelect');
            var wardSelect = document.getElementById('wardSelect');

            if (selectEl) {
                var ts = new TomSelect(selectEl, {
                    create: false,
                    maxOptions: null, // Shows all 753 palikas when no query is typed
                    sortField: { field: "text", direction: "asc" },
                    plugins: ['dropdown_input'],
                    placeholder: "Type municipality or district...",
                    onChange: function(value) {
                        wardSelect.innerHTML = '<option value="">-- Choose Ward --</option>';
                        if (!value) {
                            wardSelect.innerHTML = '<option value="">-- Select Palika First --</option>';
                            return;
                        }

                        var selectedOption = selectEl.querySelector('option[value="' + value + '"]');
                        if (selectedOption) {
                            var totalWards = parseInt(selectedOption.getAttribute('data-wards')) || 15;
                            for (var i = 1; i <= totalWards; i++) {
                                var opt = document.createElement('option');
                                opt.value = i;
                                opt.textContent = 'Ward ' + i;
                                wardSelect.appendChild(opt);
                            }
                        }
                    }
                });
            }
        });
    </script>

</body>
</html>
