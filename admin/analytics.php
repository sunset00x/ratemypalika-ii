<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$totals = $pdo->query("
    SELECT 
        COUNT(*) as total_reviews,
        ROUND(AVG(overall_rating), 2) as avg_overall,
        ROUND(AVG(road_infrastructure), 2) as avg_roads,
        ROUND(AVG(waste_management), 2) as avg_waste,
        ROUND(AVG(health_services), 2) as avg_health,
        ROUND(AVG(bureaucratic_efficiency), 2) as avg_efficiency,
        ROUND(AVG(transparency_anti_corruption), 2) as avg_transparency
    FROM reviews WHERE status = 'approved'
")->fetch();

$top_palikas = $pdo->query("
    SELECT p.name, p.district, ROUND(AVG(r.overall_rating), 2) AS score, COUNT(r.id) as total_votes
    FROM palikas p
    JOIN reviews r ON p.id = r.palika_id AND r.status = 'approved'
    GROUP BY p.id HAVING total_votes > 0
    ORDER BY score DESC LIMIT 5
")->fetchAll();

$bottom_palikas = $pdo->query("
    SELECT p.name, p.district, ROUND(AVG(r.overall_rating), 2) AS score, COUNT(r.id) as total_votes
    FROM palikas p
    JOIN reviews r ON p.id = r.palika_id AND r.status = 'approved'
    GROUP BY p.id HAVING total_votes > 0
    ORDER BY score ASC LIMIT 5
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics & Reports - RateMyPalika Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-content">
        <h1 style="margin-bottom: 20px;">Platform Analytics & National Indicators</h1>

        <div class="stat-grid" style="margin-bottom: 30px;">
            <div class="stat-card">
                <div class="label">Roads & Infrastructure</div>
                <div class="num" style="color: #2563eb;"><?= $totals['avg_roads'] ?: '0.00' ?> / 5.0</div>
            </div>
            <div class="stat-card">
                <div class="label">Waste Management</div>
                <div class="num" style="color: #16a34a;"><?= $totals['avg_waste'] ?: '0.00' ?> / 5.0</div>
            </div>
            <div class="stat-card">
                <div class="label">Health Services</div>
                <div class="num" style="color: #0284c7;"><?= $totals['avg_health'] ?: '0.00' ?> / 5.0</div>
            </div>
            <div class="stat-card">
                <div class="label">Bureaucracy & Efficiency</div>
                <div class="num" style="color: #d97706;"><?= $totals['avg_efficiency'] ?: '0.00' ?> / 5.0</div>
            </div>
            <div class="stat-card">
                <div class="label">Transparency Index</div>
                <div class="num" style="color: #9333ea;"><?= $totals['avg_transparency'] ?: '0.00' ?> / 5.0</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <div>
                <h2>Top 5 Highest Rated Palikas</h2>
                <br>
                <table>
                    <thead>
                        <tr>
                            <th>Municipality</th>
                            <th>District</th>
                            <th>Score</th>
                            <th>Reviews</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_palikas as $top): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($top['name']) ?></strong></td>
                                <td><?= htmlspecialchars($top['district']) ?></td>
                                <td><span style="color:#16a34a; font-weight:bold;"><?= $top['score'] ?> / 5.0</span></td>
                                <td><?= $top['total_votes'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div>
                <h2>Lowest Rated Palikas (Requires Attention)</h2>
                <br>
                <table>
                    <thead>
                        <tr>
                            <th>Municipality</th>
                            <th>District</th>
                            <th>Score</th>
                            <th>Reviews</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bottom_palikas as $bot): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($bot['name']) ?></strong></td>
                                <td><?= htmlspecialchars($bot['district']) ?></td>
                                <td><span style="color:#ef4444; font-weight:bold;"><?= $bot['score'] ?> / 5.0</span></td>
                                <td><?= $bot['total_votes'] ?></td>
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