<?php
// index.php
require_once 'db.php';

// Fetch Palikas with calculated average score and total review counts
$sql = "
    SELECT 
        p.id, 
        p.name, 
        p.district, 
        p.province,
        COUNT(r.id) AS total_reviews,
        ROUND(AVG(r.overall_rating), 2) AS avg_overall,
        ROUND(AVG(r.road_infrastructure), 2) AS avg_roads,
        ROUND(AVG(r.waste_management), 2) AS avg_waste,
        ROUND(AVG(r.health_services), 2) AS avg_health,
        ROUND(AVG(r.bureaucratic_efficiency), 2) AS avg_efficiency,
        ROUND(AVG(r.transparency_anti_corruption), 2) AS avg_transparency
    FROM palikas p
    LEFT JOIN reviews r ON p.id = r.palika_id
    GROUP BY p.id
    ORDER BY avg_overall DESC
";

$rankings = $pdo->query($sql)->fetchAll();

// Fetch 10 most recent qualitative reviews
$recent_reviews = $pdo->query("
    SELECT r.*, p.name AS palika_name 
    FROM reviews r 
    JOIN palikas p ON r.palika_id = p.id 
    ORDER BY r.created_at DESC 
    LIMIT 10
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rate My Palika - Public Governance Leaderboard</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 30px; background: #f8f9fa; }
        .container { max-width: 1100px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; background: #fff; margin-bottom: 30px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e9ecef; }
        th { background: #343a40; color: #fff; }
        tr:hover { background: #f1f3f5; }
        .badge { background: #28a745; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
        .card { background: #fff; padding: 15px; margin-bottom: 10px; border-radius: 6px; border-left: 4px solid #0066cc; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .btn { background: #0066cc; color: #fff; padding: 10px 16px; text-decoration: none; border-radius: 4px; display: inline-block; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🇳🇵 Rate My Palika — Governance Transparency</h1>
        <a href="submit_review.php" class="btn">+ Submit Palika Rating</a>
    </div>

    <h2>Municipal Performance Index</h2>
    <table>
        <thead>
            <tr>
                <th>Palika Name</th>
                <th>District</th>
                <th>Total Ratings</th>
                <th>Roads</th>
                <th>Waste</th>
                <th>Health</th>
                <th>Efficiency</th>
                <th>Transparency</th>
                <th>Overall Index</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rankings as $row): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                    <td><?= htmlspecialchars($row['district']) ?></td>
                    <td><?= $row['total_reviews'] ?></td>
                    <td><?= $row['avg_roads'] ?? '-' ?></td>
                    <td><?= $row['avg_waste'] ?? '-' ?></td>
                    <td><?= $row['avg_health'] ?? '-' ?></td>
                    <td><?= $row['avg_efficiency'] ?? '-' ?></td>
                    <td><?= $row['avg_transparency'] ?? '-' ?></td>
                    <td>
                        <span class="badge">
                            <?= $row['avg_overall'] ? $row['avg_overall'] . ' / 5.0' : 'N/A' ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Recent Citizen Feedback</h2>
    <?php if (empty($recent_reviews)): ?>
        <p>No ratings submitted yet.</p>
    <?php else: ?>
        <?php foreach ($recent_reviews as $rev): ?>
            <div class="card">
                <strong><?= htmlspecialchars($rev['palika_name']) ?> (Ward <?= $rev['ward_number'] ?>)</strong> 
                — Score: <strong><?= $rev['overall_rating'] ?> / 5.0</strong>
                <p><?= htmlspecialchars($rev['feedback_text'] ?: 'No additional feedback provided.') ?></p>
                <small style="color: #6c757d;"><?= $rev['created_at'] ?></small>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>