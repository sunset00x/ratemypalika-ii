<?php
require_once __DIR__ . '/config/db.php';

$search = trim($_GET['search'] ?? '');
$province = trim($_GET['province'] ?? '');
$type = trim($_GET['type'] ?? '');

$query = "
    SELECT 
        p.*,
        COUNT(r.id) AS total_reviews,
        ROUND(AVG(r.overall_rating), 2) AS avg_overall
    FROM palikas p
    LEFT JOIN reviews r ON p.id = r.palika_id
    WHERE 1=1
";

$params = [];

if ($search !== '') {
    $query .= " AND (p.name LIKE ? OR p.district LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($province !== '') {
    $query .= " AND p.province = ?";
    $params[] = $province;
}

if ($type !== '') {
    $query .= " AND p.type = ?";
    $params[] = $type;
}

$query .= " GROUP BY p.id ORDER BY p.name ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$palikas = $stmt->fetchAll();

$total_count = count($palikas);

$provinces = ['Koshi', 'Madhesh', 'Bagmati', 'Gandaki', 'Lumbini', 'Karnali', 'Sudurpashchim'];
$types = ['Metropolitan City', 'Sub-Metropolitan City', 'Municipality', 'Rural Municipality'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Municipalities Directory - RateMyPalika</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="logo">RateMyPalika</a>
        <ul class="nav-links">
            <li><a href="municipalities.php" style="color: #111827; font-weight: 600;">Municipalities</a></li>
            <li><a href="index.php#compare">Compare</a></li>
            <li><a href="index.php#rankings">Rankings</a></li>
            <li><a href="submit_review.php">Rate Now</a></li>
        </ul>
    </nav>

    <div class="hero-section" style="padding-bottom: 40px;">
        <div class="directory-header">
            <div>
                <span class="directory-badge">NEPAL CIVIC DIRECTORY</span>
                <h1 class="directory-title">Municipalities</h1>
                <p class="directory-subtitle">Explore municipalities across Nepal and view their public information, performance data, projects and budgets.</p>
            </div>
            <div class="count-card">
                <div class="count-number"><?= $total_count ?></div>
                <div class="count-label">Municipalities</div>
            </div>
        </div>

        <form method="GET" action="municipalities.php" class="filter-bar">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search municipality or district..." onchange="this.form.submit()">
            </div>

            <select name="province" class="filter-select" onchange="this.form.submit()">
                <option value="">All Provinces</option>
                <?php foreach ($provinces as $prov): ?>
                    <option value="<?= $prov ?>" <?= $province === $prov ? 'selected' : '' ?>><?= $prov ?></option>
                <?php endforeach; ?>
            </select>

            <select name="type" class="filter-select" onchange="this.form.submit()">
                <option value="">All Types</option>
                <?php foreach ($types as $t): ?>
                    <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>

            <a href="municipalities.php" class="btn-clear">Clear</a>
        </form>

        <div class="palika-grid">
            <?php if (empty($palikas)): ?>
                <p style="color: #6b7280;">No municipalities found matching your filters.</p>
            <?php else: ?>
                <?php foreach ($palikas as $p): ?>
                    <div class="palika-card">
                        <div class="palika-card-header">
                            <div class="palika-card-title"><?= htmlspecialchars($p['name']) ?></div>
                            <span class="palika-type-badge"><?= htmlspecialchars($p['type'] ?? 'Municipality') ?></span>
                        </div>
                        <div class="palika-location">
                            <?= htmlspecialchars($p['district']) ?>, <?= htmlspecialchars($p['province']) ?> Province
                        </div>
                        <div class="palika-card-footer">
                            <span style="font-size: 13px; color: #6b7280;"><?= $p['total_wards'] ?> Wards</span>
                            <span class="score-badge">
                                <?= $p['avg_overall'] ? $p['avg_overall'] . ' / 5.0' : 'No Ratings' ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>