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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($palika['name']) ?> - Details</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        <div style="display: grid; grid-template-columns: 1fr 300px; gap: 40px; margin-top: 40px;">
            <div>
                <div class="chart-box">
                    <h3>Category Performance Radar</h3>
                    <canvas id="radarChart"></canvas>
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
                                <span class="score-badge"><?= $r['overall_rating'] ?> / 5.0</span>
                            </div>
                            <p style="font-size: 14px; color: #374151;"><?= htmlspecialchars($r['feedback_text'] ?: 'No comments provided.') ?></p>
                            <small style="color: #9ca3af; display: block; margin-top: 8px;"><?= $r['created_at'] ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div>
                <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px;">
                    <h3>Overall Score</h3>
                    <div style="font-size: 48px; font-weight: 900; color: #16a34a; margin: 10px 0;"><?= $palika['avg_overall'] ?: 'N/A' ?></div>
                    <p style="font-size: 13px; color: #6b7280; margin-bottom: 20px;">Based on <?= $palika['total_reviews'] ?> total submissions</p>
                    <a href="submit_review.php" class="btn-primary" style="display: block; text-align: center;">Rate This Palika</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('radarChart').getContext('2d');
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['Roads', 'Waste', 'Health', 'Efficiency', 'Transparency'],
                datasets: [{
                    label: 'Score (out of 5)',
                    data: [
                        <?= $palika['avg_roads'] ?: 0 ?>,
                        <?= $palika['avg_waste'] ?: 0 ?>,
                        <?= $palika['avg_health'] ?: 0 ?>,
                        <?= $palika['avg_efficiency'] ?: 0 ?>,
                        <?= $palika['avg_transparency'] ?: 0 ?>
                    ],
                    backgroundColor: 'rgba(34, 197, 94, 0.2)',
                    borderColor: '#22c55e',
                    borderWidth: 2
                }]
            },
            options: {
                scales: {
                    r: { min: 0, max: 5 }
                }
            }
        });
    </script>

</body>
</html>