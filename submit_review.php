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
    $feedback = trim($_POST['feedback_text']);
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
    <script src="js/script.js" defer></script>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">RateMyPalika</a>
        <ul class="nav-links">
            <li><a href="municipalities.php">Municipalities</a></li>
            <li><a href="compare.php">Compare</a></li>
            <li><a href="rankings.php">Rankings</a></li>
            <li><a href="submit_review.php" class="active">Rate Now</a></li>
        </ul>
    </nav>

    <div class="form-container">
        <h2>Submit Civic Evaluation</h2>
        <p style="color: #6b7280; font-size: 14px; margin-bottom: 20px;">Your feedback helps drive accountability across local ward operations.</p>
        
        <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Municipality (Palika)</label>
                <select name="palika_id" id="palikaSelect" required>
                    <option value="">-- Select Palika --</option>
                    <?php foreach ($palikas as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['district']) ?>)</option>
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
                            <option value="<?= $num_val ?>" <?= $num_val == 1.0 ? 'selected' : '' ?>>
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

</body>
</html>