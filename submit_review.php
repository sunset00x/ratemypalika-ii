<?php
require_once __DIR__ . '/config/db.php';

$message = '';
$error = '';

$palikas = $pdo->query("SELECT * FROM palikas WHERE status = 'approved' ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $palika_id = (int)$_POST['palika_id'];
    $ward_number = (int)$_POST['ward_number'];
    $road = (float)($_POST['road_infrastructure'] ?? 3.0);
    $waste = (float)($_POST['waste_management'] ?? 3.0);
    $health = (float)($_POST['health_services'] ?? 3.0);
    $efficiency = (float)($_POST['bureaucratic_efficiency'] ?? 3.0);
    $transparency = (float)($_POST['transparency_anti_corruption'] ?? 3.0);
    $feedback = trim($_POST['feedback_text'] ?? '');
    $ip_address = $_SERVER['REMOTE_ADDR'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE ip_address = ? AND palika_id = ? AND created_at > NOW() - INTERVAL 1 DAY");
    $stmt->execute([$ip_address, $palika_id]);
    
    if ($stmt->fetchColumn() > 0) {
        $error = "You have already submitted a rating for this municipality in the last 24 hours.";
    } else {
        $overall = ($road + $waste + $health + $efficiency + $transparency) / 5.0;

        $insert = $pdo->prepare("INSERT INTO reviews 
            (palika_id, ward_number, road_infrastructure, waste_management, health_services, bureaucratic_efficiency, transparency_anti_corruption, overall_rating, feedback_text, ip_address, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        
        $insert->execute([$palika_id, $ward_number, $road, $waste, $health, $efficiency, $transparency, $overall, $feedback, $ip_address]);
        $message = "Thank you! Your civic evaluation has been submitted and is pending moderation.";
    }
}

$rating_options = [
    '1' => 1.0,
    '1.5' => 1.5,
    '2' => 2.0,
    '2.5' => 2.5,
    '3' => 3.0,
    '3.5' => 3.5,
    '4' => 4.0,
    '4.5' => 4.5,
    '5' => 5.0
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Rating - RateMyPalika</title>
    <link rel="stylesheet" href="css/style.css">

    <!-- Tom Select Assets for Searchable Municipalities -->
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
            <li><a href="submit_issue.php">Report Issue</a></li>
            <li><a href="submit_review.php" class="active">Rate Now</a></li>
        </ul>
    </nav>

    <div class="form-container">
        <h2>Submit Civic Evaluation</h2>
        <p style="color: #6b7280; font-size: 14px; margin-bottom: 20px;">Your feedback helps drive accountability across local ward operations.</p>
        
        <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="POST" action="submit_review.php">
            <div class="form-group">
                <label>Municipality (Palika)</label>
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

            <?php 
            $categories = [
                'road_infrastructure' => 'Roads & Infrastructure',
                'waste_management' => 'Waste Management & Cleanliness',
                'health_services' => 'Health Posts & Medical Services',
                'bureaucratic_efficiency' => 'Bureaucratic Efficiency',
                'transparency_anti_corruption' => 'Transparency & Anti-Corruption'
            ];
            foreach ($categories as $field => $label): 
            ?>
                <div class="form-group">
                    <label><?= $label ?> Rating</label>
                    <select name="<?= $field ?>" required>
                        <?php foreach ($rating_options as $display => $num_val): ?>
                            <option value="<?= $num_val ?>" <?= $num_val == 3.0 ? 'selected' : '' ?>>
                                <?= $display ?> / 5
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>

            <div class="form-group">
                <label>Remarks & Specific Experience</label>
                <textarea name="feedback_text" rows="4" placeholder="Detail your experience with municipal office responsiveness or public works..."></textarea>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%;">Submit Rating</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var selectEl = document.getElementById('palikaSelect');
            var wardSelect = document.getElementById('wardSelect');

            if (selectEl) {
                var ts = new TomSelect(selectEl, {
                    create: false,
                    maxOptions: null, // Shows all 753 items when no search term is entered
                    sortField: { field: "text", direction: "asc" },
                    plugins: ['dropdown_input'],
                    placeholder: "Type municipality or district...",
                    onChange: function(value) {
                        // Update Ward numbers dynamically on change
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

