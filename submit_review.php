<?php
// submit_review.php
require_once 'db.php';

$message = '';
$error = '';

// Fetch all Palikas for the dropdown
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

    // Rate-limiting check: 1 submission per IP per Palika per 24 hours
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE ip_address = ? AND palika_id = ? AND created_at > NOW() - INTERVAL 1 DAY");
    $stmt->execute([$ip_address, $palika_id]);
    
    if ($stmt->fetchColumn() > 0) {
        $error = "You have already rated this Palika in the last 24 hours.";
    } else {
        // Calculate average overall score for this submission
        $overall = ($road + $waste + $health + $efficiency + $transparency) / 5.0;

        $insert = $pdo->prepare("INSERT INTO reviews 
            (palika_id, ward_number, road_infrastructure, waste_management, health_services, bureaucratic_efficiency, transparency_anti_corruption, overall_rating, feedback_text, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $insert->execute([$palika_id, $ward_number, $road, $waste, $health, $efficiency, $transparency, $overall, $feedback, $ip_address]);
        $message = "Thank you! Your civic evaluation has been submitted.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rate My Palika - Submit Evaluation</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 30px; background: #f4f6f8; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        select, input, textarea { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #0066cc; color: #fff; border: none; padding: 12px 20px; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; }
        button:hover { background: #0052a3; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; }
        .danger { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<div class="container">
    <h2>Rate My Palika — Submit Feedback</h2>
    
    <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Select Local Municipality (Palika)</label>
            <select name="palika_id" required>
                <option value="">-- Choose Palika --</option>
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
            <label>Roads & Infrastructure (1 = Poor, 5 = Excellent)</label>
            <select name="road_infrastructure" required>
                <option value="5">5 - Excellent</option>
                <option value="4">4 - Good</option>
                <option value="3" selected>3 - Average</option>
                <option value="2">2 - Poor</option>
                <option value="1">1 - Very Poor</option>
            </select>
        </div>

        <div class="form-group">
            <label>Waste Management & Cleanliness</label>
            <select name="waste_management" required>
                <option value="5">5 - Excellent</option>
                <option value="4">4 - Good</option>
                <option value="3" selected>3 - Average</option>
                <option value="2">2 - Poor</option>
                <option value="1">1 - Very Poor</option>
            </select>
        </div>

        <div class="form-group">
            <label>Local Health Services / Health Posts</label>
            <select name="health_services" required>
                <option value="5">5 - Excellent</option>
                <option value="4">4 - Good</option>
                <option value="3" selected>3 - Average</option>
                <option value="2">2 - Poor</option>
                <option value="1">1 - Very Poor</option>
            </select>
        </div>

        <div class="form-group">
            <label>Bureaucratic Efficiency (Office Delays)</label>
            <select name="bureaucratic_efficiency" required>
                <option value="5">5 - Fast & Efficient</option>
                <option value="4">4 - Reasonable</option>
                <option value="3" selected>3 - Average</option>
                <option value="2">2 - Slow</option>
                <option value="1">1 - Extremely Slow</option>
            </select>
        </div>

        <div class="form-group">
            <label>Transparency & Anti-Corruption</label>
            <select name="transparency_anti_corruption" required>
                <option value="5">5 - Highly Transparent</option>
                <option value="4">4 - Transparent</option>
                <option value="3" selected>3 - Moderate</option>
                <option value="2">2 - Unclear/Suspicious</option>
                <option value="1">1 - High Bribery/Corruption</option>
            </select>
        </div>

        <div class="form-group">
            <label>Qualitative Feedback / Remarks</label>
            <textarea name="feedback_text" rows="4" placeholder="Share specific experiences regarding local services..."></textarea>
        </div>

        <button type="submit">Submit Civic Evaluation</button>
    </form>
    <p><a href="index.php">← View Public Scoreboard & Analytics</a></p>
</div>

</body>
</html>