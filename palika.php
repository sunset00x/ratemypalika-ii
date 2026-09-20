<?php
require_once __DIR__ . '/config/db.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: municipalities.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        p.*,
        COUNT(r.id) AS total_reviews,
        ROUND(AVG(r.overall_rating), 2) AS avg_overall,
        ROUND(AVG(r.road_infrastructure), 2) AS avg_roads,
        ROUND(AVG(r.waste_management), 2) AS avg_waste,
        ROUND(AVG(r.health_services), 2) AS avg_health,
        ROUND(AVG(r.bureaucratic_efficiency), 2) AS avg_efficiency,
        ROUND(AVG(r.transparency_anti_corruption), 2) AS avg_transparency
    FROM palikas p
    LEFT JOIN reviews r ON p.id = r.palika_id AND r.status = 'approved'
    WHERE p.id = ?
    GROUP BY p.id
");
$stmt->execute([$id]);
$palika = $stmt->fetch();

if (!$palika) {
    header('Location: municipalities.php');
    exit;
}

$rev_stmt = $pdo->prepare("SELECT * FROM reviews WHERE palika_id = ? AND status = 'approved' ORDER BY created_at DESC");
$rev_stmt->execute([$id]);
$reviews = $rev_stmt->fetchAll();

$categories = [
    'Roads & Infrastructure' => $palika['avg_roads'] ?: '0.00',
    'Waste Management & Cleanliness' => $palika['avg_waste'] ?: '0.00',
    'Health Posts & Medical Services' => $palika['avg_health'] ?: '0.00',
    'Bureaucratic Efficiency' => $palika['avg_efficiency'] ?: '0.00',
    'Transparency & Anti-Corruption' => $palika['avg_transparency'] ?: '0.00',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($palika['name']) ?> - Details</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .category-score-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 30px;
        }
        .category-row {
            margin-bottom: 20px;
        }
        .category-row:last-child {
            margin-bottom: 0;
        }
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .category-name {
            font-size: 15px;
            font-weight: 600;
            color: #1e293b;
        }
        .category-value {
            font-size: 16px;
            font-weight: 800;
            color: #16a34a;
        }
        .progress-bar-bg {
            width: 100%;
            height: 10px;
            background: #f1f5f9;
            border-radius: 6px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            background: #22c55e;
            border-radius: 6px;
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
            <li><a href="submit_review.php">Rate Now</a></li>
        </ul>
    </nav>

    <div class="hero-section">
        <span class="badge-tag"><?= htmlspecialchars($palika['province']) ?> PROVINCE</span>
        <h1 class="hero-title" style="font-size: 52px; margin-bottom: 8px;"><?= htmlspecialchars($palika['name']) ?></h1>
        <p class="hero-subtitle"><?= htmlspecialchars($palika['district']) ?> District • <?= $palika['total_wards'] ?> Total Wards</p>

        <div style="display: grid; grid-template-columns: 1fr 320px; gap: 40px; margin-top: 40px;">
            <div>
                <div class="category-score-card">
                    <h3 style="margin-bottom: 24px; font-size: 20px; color: #0f172a;">Field Ratings Summary</h3>
                    
                    <?php foreach ($categories as $label => $score): ?>
                        <?php $percentage = min(100, max(0, ($score / 5.0) * 100)); ?>
                        <div class="category-row">
                            <div class="category-header">
                                <span class="category-name"><?= $label ?></span>
                                <span class="category-value"><?= number_format((float)$score, 2) ?> / 5.0</span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill" style="width: <?= $percentage ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <h2>Citizen Reviews (<?= count($reviews) ?>)</h2>
                <br>
                <?php if (empty($reviews)): ?>
                    <p style="color: #6b7280;">No approved reviews yet for this municipality.</p>
                <?php else: ?>
                    <?php foreach ($reviews as $r): ?>
                        <div style="background: #fff; padding: 20px; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 16px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <strong>Ward <?= $r['ward_number'] ?></strong>
                                <span class="score-badge"><?= number_format((float)$r['overall_rating'], 2) ?> / 5.0</span>
                            </div>
                            <p style="font-size: 14px; color: #374151;"><?= htmlspecialchars($r['feedback_text'] ?: 'No comments provided.') ?></p>
                            <small style="color: #9ca3af; display: block; margin-top: 8px;"><?= $r['created_at'] ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div>
                <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; position: sticky; top: 20px;">
                    <h3>Overall Score</h3>
                    <div style="font-size: 48px; font-weight: 900; color: #16a34a; margin: 10px 0;"><?= $palika['avg_overall'] ? sprintf("%.2f", $palika['avg_overall']) : 'N/A' ?></div>
                    <p style="font-size: 13px; color: #6b7280; margin-bottom: 20px;">Based on <?= $palika['total_reviews'] ?> total submissions</p>
                    <a href="submit_review.php" class="btn-primary" style="display: block; text-align: center;">Rate This Palika</a>
                </div>
            </div>
        </div>
    </div>

</body>
</html>