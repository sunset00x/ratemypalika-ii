<?php
require_once __DIR__ . '/config/db.php';

$message = '';
$error = '';

$palikas = $pdo->query("SELECT * FROM palikas ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $palika_id = (int)$_POST['palika_id'];
    $ward_number = (int)$_POST['ward_number'];
    $road = (int)$_POST['road_infrastructure'];
    $waste = (int)$_POST['waste_management'];
    $health = (int)$_POST['health_services'];
    $efficiency = (int)$_POST['bureaucratic_efficiency'];
    $transparency = (int)$_POST['transparency_anti_corruption'];
    $feedback = trim($_POST['feedback_text']);
    $ip_address = $_SERVER['REMOTE_ADDR'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE ip_address = ? AND palika_id = ? AND created_at > NOW() - INTERVAL 1 DAY");
    $stmt->execute([$ip_address, $palika_id]);
    
    if ($stmt->fetchColumn() > 0) {
        $error = "You have already submitted a rating for this municipality in the last 24 hours.";
    } else {
        $overall = ($road + $waste + $health + $efficiency + $transparency) / 5.0;

        $insert = $pdo->prepare("INSERT INTO reviews 
            (palika_id, ward_number, road_infrastructure, waste_management, health_services, bureaucratic_efficiency, transparency_anti_corruption, overall_rating, feedback_text, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $insert->execute([$palika_id, $ward_number, $road, $waste, $health, $efficiency, $transparency, $overall, $feedback, $ip_address]);
        $message = "Thank you! Your evaluation has been submitted.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Rating - RateMyPalika</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">RateMyPalika</a>
        <ul class="nav-links">
            <li><a href="index.php#municipalities">Municipalities</a></li>
            <li><a href="index.php#compare">Compare</a></li>
            <li><a href="index.php#rankings">Rankings</a></li>
            <li><a href="submit_review.php">Rate Now</a></li>
        </ul>
    </nav>

    <div class="form-container">
        <h2>Submit Civic Evaluation</h2>
        <br>
        
        <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Municipality (Palika)</label>
                <select name="palika_id" required>
                    <option value="">-- Select Palika --</option>
                    <?php foreach ($palikas as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['district']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Ward Number</label>
                <input type="number" name="ward_number" min="1" max="35" placeholder="e.g. 4" required>
            </div>

            <div class="form-group">
                <label>Roads & Infrastructure</label>
                <select name="road_infrastructure" required>
                    <option value="5">5 - Excellent</option>
                    <option value="4">4 - Good</option>
                    <option value="3" selected>3 - Average</option>
                    <option value="2">2 - Poor</option>
                    <option value="1">1 - Very Poor</option>
                </select>
            </div>

            <div class="form-group">
                <label>Waste Management</label>
                <select name="waste_management" required>
                    <option value="5">5 - Excellent</option>
                    <option value="4">4 - Good</option>
                    <option value="3" selected>3 - Average</option>
                    <option value="2">2 - Poor</option>
                    <option value="1">1 - Very Poor</option>
                </select>
            </div>

            <div class="form-group">
                <label>Health Services</label>
                <select name="health_services" required>
                    <option value="5">5 - Excellent</option>
                    <option value="4">4 - Good</option>
                    <option value="3" selected>3 - Average</option>
                    <option value="2">2 - Poor</option>
                    <option value="1">1 - Very Poor</option>
                </select>
            </div>

            <div class="form-group">
                <label>Bureaucratic Efficiency</label>
                <select name="bureaucratic_efficiency" required>
                    <option value="5">5 - Fast & Responsive</option>
                    <option value="4">4 - Reasonable</option>
                    <option value="3" selected>3 - Average</option>
                    <option value="2">2 - Slow</option>
                    <option value="1">1 - Extremely Delayed</option>
                </select>
            </div>

            <div class="form-group">
                <label>Transparency & Anti-Corruption</label>
                <select name="transparency_anti_corruption" required>
                    <option value="5">5 - Fully Transparent</option>
                    <option value="4">4 - Mostly Transparent</option>
                    <option value="3" selected>3 - Moderate</option>
                    <option value="2">2 - Low Transparency</option>
                    <option value="1">1 - High Corruption</option>
                </select>
            </div>

            <div class="form-group">
                <label>Feedback & Remarks</label>
                <textarea name="feedback_text" rows="4" placeholder="Additional observations..."></textarea>
            </div>

            <button type="submit">Submit Evaluation</button>
        </form>
    </div>

</body>
</html>