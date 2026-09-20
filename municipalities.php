<?php
require_once __DIR__ . '/config/db.php';

$search = trim($_GET['search'] ?? '');
$province = trim($_GET['province'] ?? '');
$type = trim($_GET['type'] ?? '');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_palika') {
    $name = trim($_POST['name']);
    $district = trim($_POST['district']);
    $province_req = trim($_POST['province']);
    $type_req = trim($_POST['type']);
    $total_wards = (int)$_POST['total_wards'];

    if (!empty($name) && !empty($district) && !empty($province_req) && $total_wards > 0) {
        
        $check = $pdo->prepare("SELECT COUNT(*) FROM palikas WHERE LOWER(name) = LOWER(?) AND LOWER(district) = LOWER(?)");
        $check->execute([$name, $district]);
        
        if ($check->fetchColumn() > 0) {
            $error = "This municipality ($name, $district) already exists in our system.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO palikas (name, district, province, type, total_wards, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$name, $district, $province_req, $type_req, $total_wards]);
            $message = "Thank you! Your municipality request for '$name' has been submitted for admin approval.";
        }

    } else {
        $error = "Please fill in all fields correctly.";
    }
}

$query = "
    SELECT 
        p.*,
        COUNT(r.id) AS total_reviews,
        ROUND(AVG(r.overall_rating), 2) AS avg_overall
    FROM palikas p
    LEFT JOIN reviews r ON p.id = r.palika_id AND r.status = 'approved'
    WHERE p.status = 'approved'
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
            <li><a href="municipalities.php" class="active">Municipalities</a></li>
            <li><a href="compare.php">Compare</a></li>
            <li><a href="rankings.php">Rankings</a></li>
            <li><a href="submit_issue.php">Report Issue</a></li>
            <li><a href="submit_review.php">Rate Now</a></li>
        </ul>
    </nav>

    <div class="hero-section" style="padding-bottom: 60px;">
        
        <?php if ($message): ?><div class="alert success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="directory-header">
            <div>
                <span class="directory-badge">NEPAL CIVIC DIRECTORY</span>
                <h1 class="directory-title">Municipalities</h1>
                <p class="directory-subtitle">Explore municipalities across Nepal and view their public information, performance data, projects and budgets.</p>
            </div>
            <div style="display: flex; gap: 20px; align-items: center;">
                <a href="export_pdf.php" target="_blank" class="btn-primary" style="background:#15803d; text-decoration:none;">Export PDF</a>
                <div class="count-card">
                    <div class="count-number"><?= $total_count ?></div>
                    <div class="count-label">Municipalities</div>
                </div>
            </div>
        </div>

        <form method="GET" action="municipalities.php" class="filter-bar">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search municipality or district..." onchange="this.form.submit()">

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

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            <?php if (empty($palikas)): ?>
                <p style="color: #6b7280; grid-column: 1 / -1;">No approved municipalities found matching your filter parameters.</p>
            <?php else: ?>
                <?php foreach ($palikas as $p): ?>
                    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px;">
                        <h3><a href="palika.php?id=<?= $p['id'] ?>" style="color: #0f172a; text-decoration: none;"><?= htmlspecialchars($p['name']) ?></a></h3>
                        <p style="font-size: 14px; color: #6b7280; margin: 6px 0 16px 0;"><?= htmlspecialchars($p['district']) ?>, <?= htmlspecialchars($p['province']) ?> Province</p>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 13px; color: #9ca3af;"><?= $p['total_wards'] ?> Wards</span>
                            <span class="score-badge"><?= $p['avg_overall'] ? $p['avg_overall'] . ' / 5.0' : 'N/A' ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="cant-find-banner">
            <h3>Can't find your municipality?</h3>
            <p>If your local Palika isn't listed in our public directory yet, you can request to add it for admin verification.</p>
            <button type="button" class="btn-primary" onclick="openPalikaModal()">+ Create Municipality Request</button>
        </div>

    </div>

    <div class="modal-overlay" id="palikaModal">
        <div class="modal-card">
            <button class="modal-close" onclick="closePalikaModal()">✕</button>
            <h2 style="margin-bottom: 8px;">Create Municipality Request</h2>
            <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">Submit your municipality details. Once reviewed and accepted by an admin, it will appear on this page.</p>

            <form method="POST" action="municipalities.php">
                <input type="hidden" name="action" value="request_palika">

                <div class="form-group">
                    <label>Municipality Name</label>
                    <input type="text" name="name" placeholder="e.g. Damak Municipality" required>
                </div>

                <div class="form-group">
                    <label>District</label>
                    <input type="text" name="district" placeholder="e.g. Jhapa" required>
                </div>

                <div class="form-group">
                    <label>Province</label>
                    <select name="province" required>
                        <option value="">-- Choose Province --</option>
                        <?php foreach ($provinces as $prov): ?>
                            <option value="<?= $prov ?>"><?= $prov ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Type</label>
                    <select name="type" required>
                        <?php foreach ($types as $t): ?>
                            <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Total Wards</label>
                    <input type="number" name="total_wards" min="1" max="100" value="1" required>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px;">Submit for Verification</button>
            </form>
        </div>
    </div>

    <script>
        function openPalikaModal() {
            document.getElementById('palikaModal').classList.add('active');
        }
        function closePalikaModal() {
            document.getElementById('palikaModal').classList.remove('active');
        }
    </script>

</body>
</html>