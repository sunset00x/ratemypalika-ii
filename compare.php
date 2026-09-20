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
        LEFT JOIN reviews r ON p.id = r.palika_id AND r.status = 'approved'
        WHERE p.id IN ($placeholders)
        GROUP BY p.id
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($selected_ids);
    $compared_data = $stmt->fetchAll();
}

function getHighestScore($data, $key) {
    $scores = array_column($data, $key);
    return count($scores) ? max($scores) : 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compare Municipalities - RateMyPalika</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">RateMyPalika</a>
        <ul class="nav-links">
            <li><a href="municipalities.php">Municipalities</a></li>
            <li><a href="compare.php" class="active">Compare</a></li>
            <li><a href="rankings.php">Rankings</a></li>
            <li><a href="submit_review.php">Rate Now</a></li>
        </ul>
    </nav>

    <div class="hero-section">
        <span class="badge-tag">CIVIC ANALYTICS</span>
        <h1 class="hero-title" style="font-size: 52px; margin-bottom: 16px;">Compare Municipalities</h1>
        <p class="hero-subtitle">Side-by-side metric evaluations across selected municipalities.</p>

        <?php if ($error): ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin-bottom: 30px;">
            <form method="GET" action="compare.php">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                    <?php for ($i = 0; $i < 4; $i++): ?>
                        <div>
                            <label style="font-size: 13px; color: #6b7280;">Municipality <?= $i + 1 ?></label>
                            <select name="ids[]" style="width: 100%;">
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
                <button type="submit" class="btn-primary" style="margin-top: 20px;">Compare Selected</button>
            </form>
        </div>

        <?php if (count($compared_data) >= 2): ?>
            <div class="chart-box">
                <canvas id="compareBarChart"></canvas>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Metric</th>
                        <?php foreach ($compared_data as $item): ?>
                            <th><?= htmlspecialchars($item['name']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $metrics = [
                        'avg_overall' => 'Overall Score',
                        'avg_roads' => 'Roads & Infrastructure',
                        'avg_waste' => 'Waste Management',
                        'avg_health' => 'Health Services',
                        'avg_efficiency' => 'Bureaucratic Efficiency',
                        'avg_transparency' => 'Transparency'
                    ];

                    foreach ($metrics as $key => $label): 
                        $max = getHighestScore($compared_data, $key);
                    ?>
                        <tr>
                            <td><strong><?= $label ?></strong></td>
                            <?php foreach ($compared_data as $item): ?>
                                <?php 
                                    $val = $item[$key] ?: 0;
                                    $is_winner = ($val == $max && $val > 0);
                                ?>
                                <td class="<?= $is_winner ? 'winning-metric' : '' ?>">
                                    <?= $item[$key] ?: 'N/A' ?>
                                    <?= $is_winner ? ' 🏆' : '' ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if (count($compared_data) >= 2): ?>
    <script>
        const ctx = document.getElementById('compareBarChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Overall', 'Roads', 'Waste', 'Health', 'Efficiency', 'Transparency'],
                datasets: [
                    <?php foreach ($compared_data as $idx => $item): ?>
                    {
                        label: '<?= addslashes($item['name']) ?>',
                        data: [
                            <?= $item['avg_overall'] ?: 0 ?>,
                            <?= $item['avg_roads'] ?: 0 ?>,
                            <?= $item['avg_waste'] ?: 0 ?>,
                            <?= $item['avg_health'] ?: 0 ?>,
                            <?= $item['avg_efficiency'] ?: 0 ?>,
                            <?= $item['avg_transparency'] ?: 0 ?>
                        ],
                        borderWidth: 1
                    },
                    <?php endforeach; ?>
                ]
            },
            options: {
                responsive: true,
                scales: { y: { min: 0, max: 5 } }
            }
        });
    </script>
    <?php endif; ?>

</body>
</html>