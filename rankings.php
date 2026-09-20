<?php
require_once __DIR__ . '/config/db.php';

$sort = $_GET['sort'] ?? 'overall';
$order = $_GET['order'] ?? 'DESC';
$province = trim($_GET['province'] ?? '');

$sort_columns = [
    'overall' => 'avg_overall',
    'roads' => 'avg_roads',
    'waste' => 'avg_waste',
    'health' => 'avg_health',
    'efficiency' => 'avg_efficiency',
    'transparency' => 'avg_transparency',
    'reviews' => 'total_reviews'
];

$order_by = $sort_columns[$sort] ?? 'avg_overall';
$order_dir = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

$query = "
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
    WHERE 1=1
";

$params = [];
if ($province !== '') {
    $query .= " AND p.province = ?";
    $params[] = $province;
}

$query .= " GROUP BY p.id ORDER BY $order_by $order_dir, total_reviews DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rankings = $stmt->fetchAll();

$top_3 = array_slice(array_filter($rankings, fn($item) => $item['avg_overall'] !== null), 0, 3);
$provinces = ['Koshi', 'Madhesh', 'Bagmati', 'Gandaki', 'Lumbini', 'Karnali', 'Sudurpashchim'];

function toggleOrder($current_sort, $column, $current_order) {
    if ($current_sort === $column) {
        return $current_order === 'DESC' ? 'ASC' : 'DESC';
    }
    return 'DESC';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Municipal Rankings - RateMyPalika</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">RateMyPalika</a>
        <ul class="nav-links">
            <li><a href="municipalities.php">Municipalities</a></li>
            <li><a href="compare.php">Compare</a></li>
            <li><a href="rankings.php" style="color: #111827; font-weight: 600;">Rankings</a></li>
            <li><a href="submit_review.php">Rate Now</a></li>
        </ul>
    </nav>

    <div class="hero-section" style="padding-bottom: 40px;">
        <span class="badge-tag">PERFORMANCE LEADERBOARD</span>
        <h1 class="hero-title" style="font-size: 56px; margin-bottom: 20px;">Palika Rankings</h1>
        <p class="hero-subtitle">Real-time civic performance ratings across all municipalities in Nepal based on citizen evaluations.</p>

        <?php if (count($top_3) === 3 && $province === ''): ?>
            <div class="podium-grid">
                <div class="podium-card silver">
                    <span class="podium-rank-tag" style="color: #64748b;">🥈 Rank #2</span>
                    <div class="podium-title"><?= htmlspecialchars($top_3[1]['name']) ?></div>
                    <div style="font-size: 13px; color: #6b7280;"><?= htmlspecialchars($top_3[1]['district']) ?></div>
                    <div class="podium-score"><?= $top_3[1]['avg_overall'] ?></div>
                    <div style="font-size: 12px; color: #94a3b8;">Based on <?= $top_3[1]['total_reviews'] ?> ratings</div>
                </div>

                <div class="podium-card gold">
                    <span class="podium-rank-tag" style="color: #ca8a04;">🥇 Rank #1</span>
                    <div class="podium-title"><?= htmlspecialchars($top_3[0]['name']) ?></div>
                    <div style="font-size: 13px; color: #6b7280;"><?= htmlspecialchars($top_3[0]['district']) ?></div>
                    <div class="podium-score"><?= $top_3[0]['avg_overall'] ?></div>
                    <div style="font-size: 12px; color: #94a3b8;">Based on <?= $top_3[0]['total_reviews'] ?> ratings</div>
                </div>

                <div class="podium-card bronze">
                    <span class="podium-rank-tag" style="color: #ea580c;">🥉 Rank #3</span>
                    <div class="podium-title"><?= htmlspecialchars($top_3[2]['name']) ?></div>
                    <div style="font-size: 13px; color: #6b7280;"><?= htmlspecialchars($top_3[2]['district']) ?></div>
                    <div class="podium-score"><?= $top_3[2]['avg_overall'] ?></div>
                    <div style="font-size: 12px; color: #94a3b8;">Based on <?= $top_3[2]['total_reviews'] ?> ratings</div>
                </div>
            </div>
        <?php endif; ?>

        <form method="GET" action="rankings.php" class="filter-bar" style="margin-bottom: 24px;">
            <select name="province" class="filter-select" onchange="this.form.submit()">
                <option value="">All Provinces</option>
                <?php foreach ($provinces as $prov): ?>
                    <option value="<?= $prov ?>" <?= $province === $prov ? 'selected' : '' ?>><?= $prov ?></option>
                <?php endforeach; ?>
            </select>
            <a href="rankings.php" class="btn-clear">Reset Filters</a>
        </form>

        <table class="leaderboard-table">
            <thead>
                <tr>
                    <th style="width: 60px;">Rank</th>
                    <th>Palika Name</th>
                    <th>District</th>
                    <th><a href="rankings.php?sort=reviews&order=<?= toggleOrder($sort, 'reviews', $order) ?>&province=<?= urlencode($province) ?>">Ratings ⇕</a></th>
                    <th><a href="rankings.php?sort=roads&order=<?= toggleOrder($sort, 'roads', $order) ?>&province=<?= urlencode($province) ?>">Roads ⇕</a></th>
                    <th><a href="rankings.php?sort=waste&order=<?= toggleOrder($sort, 'waste', $order) ?>&province=<?= urlencode($province) ?>">Waste ⇕</a></th>
                    <th><a href="rankings.php?sort=health&order=<?= toggleOrder($sort, 'health', $order) ?>&province=<?= urlencode($province) ?>">Health ⇕</a></th>
                    <th><a href="rankings.php?sort=efficiency&order=<?= toggleOrder($sort, 'efficiency', $order) ?>&province=<?= urlencode($province) ?>">Efficiency ⇕</a></th>
                    <th><a href="rankings.php?sort=transparency&order=<?= toggleOrder($sort, 'transparency', $order) ?>&province=<?= urlencode($province) ?>">Transparency ⇕</a></th>
                    <th><a href="rankings.php?sort=overall&order=<?= toggleOrder($sort, 'overall', $order) ?>&province=<?= urlencode($province) ?>">Overall ⇕</a></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rankings as $index => $row): ?>
                    <?php 
                        $rank = $index + 1;
                        $rank_class = '';
                        if ($rank === 1) $rank_class = 'rank-1';
                        elseif ($rank === 2) $rank_class = 'rank-2';
                        elseif ($rank === 3) $rank_class = 'rank-3';
                    ?>
                    <tr>
                        <td><span class="rank-badge <?= $rank_class ?>"><?= $rank ?></span></td>
                        <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                        <td><?= htmlspecialchars($row['district']) ?></td>
                        <td><?= $row['total_reviews'] ?></td>
                        <td><?= $row['avg_roads'] ? sprintf("%.2f", $row['avg_roads']) : '-' ?></td>
                        <td><?= $row['avg_waste'] ? sprintf("%.2f", $row['avg_waste']) : '-' ?></td>
                        <td><?= $row['avg_health'] ? sprintf("%.2f", $row['avg_health']) : '-' ?></td>
                        <td><?= $row['avg_efficiency'] ? sprintf("%.2f", $row['avg_efficiency']) : '-' ?></td>
                        <td><?= $row['avg_transparency'] ? sprintf("%.2f", $row['avg_transparency']) : '-' ?></td>
                        <td>
                            <span class="score-badge">
                                <?= $row['avg_overall'] ? sprintf("%.2f", $row['avg_overall']) : 'N/A' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</body>
</html>