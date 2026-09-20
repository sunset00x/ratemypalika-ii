<?php
require_once __DIR__ . '/config/db.php';

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RateMyPalika</title>
    <link rel="stylesheet" href="css/style.css">
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

    <section class="hero-section">
        <span class="badge-tag">NEPAL CIVIC TRANSPARENCY</span>
        <h1 class="hero-title">
            Measure.<br>
            Compare.<br>
            <span class="highlight">Improve.</span>
        </h1>
        <p class="hero-subtitle">
            Explore municipality performance, public projects, budgets and citizen reports.
        </p>
        <a href="municipalities.php" class="btn-primary">Explore Municipalities</a>
    </section>

</body>
</html>