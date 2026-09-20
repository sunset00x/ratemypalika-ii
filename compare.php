<?php
require_once __DIR__ . '/config/db.php';

$all_palikas = $pdo->query("SELECT id, name, district FROM palikas ORDER BY name ASC")->fetchAll();

$selected_ids = array_filter(array_map('intval', $_GET['ids'] ?? []));
$selected_ids = array_slice($selected_ids, 0, 4);

$error = '';
$compared_data = [];

if (count($selected_ids) > 0 && count($selected_ids) < 2) {
    $error = 'Please select at least 2 municipalities to perform a comparison.';
} elseif (count($selected_ids) >= 2) {
    $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
    $sql = "
        SELECT 
            p.id, 
            p.name, 
            p.district, 
            p.province,
            p.total_wards,
            COUNT(r.id) AS total_reviews,
            ROUND(AVG(r.overall_rating), 2) AS avg_overall,
            ROUND(AVG(r.road_infrastructure), 2) AS avg_roads,
            ROUND(AVG(r.waste_management), 2) AS avg_waste,
            ROUND(AVG(r.health_services), 2) AS avg_health,
            ROUND(AVG(r.bureaucratic_efficiency), 2) AS avg_efficiency,
            ROUND(AVG(r.transparency_anti_corruption), 2) AS avg_transparency
        FROM palikas p
        LEFT JOIN reviews r ON p.id = r.palika_id
        WHERE p.id IN ($placeholders)
        GROUP BY p.id
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($selected_ids);
    $compared_data = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compare Municipalities - RateMyPalika</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">RateMyPalika</a>
        <ul class="nav-links">
            <li><a href="municipalities.php">Municipalities</a></li>
            <li><a href="compare.php" style="color: #111827; font-weight: 600;">Compare</a></li>
            <li><a href="index.php#rankings">Rankings</a></li>
            <li><a href="submit_review.php">Rate Now</a></li>
        </ul>
    </nav>

    <div class="hero-section" style="padding-bottom: 40px;">
        <span class="badge-tag">CIVIC ANALYTICS</span>
        <h1 class="hero-title" style="font-size: 56px; margin-bottom: 20px;">Compare Municipalities</h1>
        <p class="hero-subtitle">Select between 2 and 4 municipalities to compare performance metrics side-by-side.</p>

        <?php if ($error): ?>
            <div class="alert danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="compare-selector-card">
            <h3>Select Palikas (Min: 2, Max: 4)</h3>
            <form method="GET" action="compare.php">
                <div class="compare-grid-select">
                    <?php for ($i = 0; $i < 4; $i++): ?>
                        <div>
                            <label style="font-size: 13px; color: #6b7280;">Municipality <?= $i + 1 ?></label>
                            <select name="ids[]" class="filter-select" style="width: 100%;">
                                <option value="">-- Choose Palika --</option>
                                <?php foreach ($all_palikas as $palika): ?>
                                    <option value="<?= $palika['id'] ?>" <?= (isset($selected_ids[$i]) && $selected_ids[$i] == $palika['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($palika['name']) ?> (<?= htmlspecialchars($palika['district']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endfor; ?>
                </div>
                <button type="submit" class="btn-compare">Compare Selected</button>
            </form>
        </div>

        <?php if (count($compared_data) >= 2): ?>
            <table class="compare-table">
                <thead>
                    <tr>
                        <th>Metric / Indicator</th>
                        <?php foreach ($compared_data as $item): ?>
                            <th>
                                <?= htmlspecialchars($item['name']) ?>
                                <span style="display:block; font-size: 12px; font-weight: 400; opacity: 0.8;"><?= htmlspecialchars($item['district']) ?>, <?= htmlspecialchars($item['province']) ?></span>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Overall Score</td>
                        <?php foreach ($compared_data as $item): ?>
                            <td>
                                <span class="metric-score" style="color: #16a34a; font-size: 22px;"><?= $item['avg_overall'] ?: 'N/A' ?></span>
                                <span class="metric-sub">out of 5.0</span>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Total Reviews Submitted</td>
                        <?php foreach ($compared_data as $item): ?>
                            <td><span class="metric-score"><?= $item['total_reviews'] ?></span></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Total Wards</td>
                        <?php foreach ($compared_data as $item): ?>
                            <td><span class="metric-score"><?= $item['total_wards'] ?></span></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Roads & Infrastructure</td>
                        <?php foreach ($compared_data as $item): ?>
                            <td><span class="metric-score"><?= $item['avg_roads'] ?: '-' ?></span></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Waste Management</td>
                        <?php foreach ($compared_data as $item): ?>
                            <td><span class="metric-score"><?= $item['avg_waste'] ?: '-' ?></span></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Health Services</td>
                        <?php foreach ($compared_data as $item): ?>
                            <td><span class="metric-score"><?= $item['avg_health'] ?: '-' ?></span></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Bureaucratic Efficiency</td>
                        <?php foreach ($compared_data as $item): ?>
                            <td><span class="metric-score"><?= $item['avg_efficiency'] ?: '-' ?></span></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Transparency & Anti-Corruption</td>
                        <?php foreach ($compared_data as $item): ?>
                            <td><span class="metric-score"><?= $item['avg_transparency'] ?: '-' ?></span></td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</body>
</html>