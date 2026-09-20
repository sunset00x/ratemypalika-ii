<?php
require_once __DIR__ . '/config/db.php';

$search = trim($_GET['search'] ?? '');
$province = trim($_GET['province'] ?? '');
$type = trim($_GET['type'] ?? '');

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="palikas_directory.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Name', 'District', 'Province', 'Type', 'Total Wards']);
    
    $rows = $pdo->query("SELECT id, name, district, province, type, total_wards FROM palikas ORDER BY name ASC")->fetchAll();
    foreach ($rows as $r) {
        fputcsv($out, $r);
    }
    fclose($out);
    exit;
}

$query = "
    SELECT 
        p.*,
        COUNT(r.id) AS total_reviews,
        ROUND(AVG(r.overall_rating), 2) AS avg_overall
    FROM palikas p
    LEFT JOIN reviews r ON p.id = r.palika_id AND r.status = 'approved'
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
            <li><a href="municipalities.php" class="active">Municipalities</a></li>
            <li><a href="compare.php">Compare</a></li>
            <li><a href="rankings.php">Rankings</a></li>
            <li><a href="submit_review.php">Rate Now</a></li>
        </ul>
    </nav>

    <div class="hero-section">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px;">
            <div>
                <span class="badge-tag">NEPAL CIVIC DIRECTORY</span>
                <h1 class="hero-title" style="font-size: 52px; margin-bottom: 12px;">Municipalities</h1>
                <p class="hero-subtitle">Explore municipal statistics, review ratings, and structural data.</p>
            </div>
            <a href="municipalities.php?export=csv" class="btn-primary">Export CSV</a>
        </div>

        <form method="GET" action="municipalities.php" style="display: flex; gap: 16px; margin-bottom: 30px;">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search municipality or district..." onchange="this.form.submit()">
            <select name="province" onchange="this.form.submit()">
                <option value="">All Provinces</option>
                <?php foreach ($provinces as $prov): ?>
                    <option value="<?= $prov ?>" <?= $province === $prov ? 'selected' : '' ?>><?= $prov ?></option>
                <?php endforeach; ?>
            </select>
            <select name="type" onchange="this.form.submit()">
                <option value="">All Types</option>
                <?php foreach ($types as $t): ?>
                    <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>
            <a href="municipalities.php" class="btn-primary" style="background:#6b7280;">Reset</a>
        </form>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            <?php foreach ($palikas as $p): ?>
                <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px;">
                    <h3><a href="palika.php?id=<?= $p['id'] ?>" style="color: #0f172a; text-decoration: none;"><?= htmlspecialchars($p['name']) ?></a></h3>
                    <p style="font-size: 14px; color: #6b7280; margin: 6px 0 16px 0;"><?= htmlspecialchars($p['district']) ?>, <?= htmlspecialchars($p['province']) ?></p>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 13px; color: #9ca3af;"><?= $p['total_wards'] ?> Wards</span>
                        <span class="score-badge"><?= $p['avg_overall'] ? $p['avg_overall'] . ' / 5.0' : 'N/A' ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</body>
</html>