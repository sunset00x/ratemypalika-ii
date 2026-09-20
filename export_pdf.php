<?php
require_once __DIR__ . '/config/db.php';

// Fetch Palika structural details only (Excluding all rating data)
$query = "
    SELECT id, name, district, province, type, total_wards 
    FROM palikas 
    WHERE status = 'approved' 
    ORDER BY province ASC, district ASC, name ASC
";

$palikas = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
$total_count = count($palikas);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Palikas Directory PDF - RateMyPalika</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #0f172a;
            margin: 0;
            padding: 30px;
            background: #ffffff;
        }

        .pdf-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 16px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .brand-title {
            font-size: 24px;
            font-weight: 800;
            margin: 0;
            color: #0f172a;
        }

        .brand-subtitle {
            font-size: 13px;
            color: #2563eb;
            font-weight: 600;
            margin-top: 4px;
        }

        .report-meta {
            text-align: right;
            font-size: 12px;
            color: #64748b;
        }

        .summary-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 18px;
            margin-bottom: 24px;
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        th {
            background: #0f172a;
            color: #ffffff;
            text-align: left;
            padding: 10px 12px;
            font-weight: 600;
        }

        td {
            padding: 9px 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .no-print-bar {
            background: #0f172a;
            color: #ffffff;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-align: center;
            margin: -30px -30px 30px -30px;
        }

        .btn-print {
            background: #22c55e;
            color: #ffffff;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
        }

        @media print {
            .no-print-bar {
                display: none;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <span>PDF Document Preview & Print Ready</span>
        <button onclick="window.print()" class="btn-print">Save / Print as PDF</button>
    </div>

    <div class="pdf-header">
        <div>
            <h1 class="brand-title">RateMyPalika</h1>
            <div class="brand-subtitle">An AcademiX Digital Initiative</div>
        </div>
        <div class="report-meta">
            <strong>Official Municipalities Directory</strong><br>
            Generated Date: <?= date('Y-m-d H:i') ?><br>
            Total Records: <?= $total_count ?>
        </div>
    </div>

    <div class="summary-box">
        <strong>Document Summary:</strong> Official structural register of approved local government bodies across Nepal, including Province, District, Administrative Classification, and Total Ward Divisions.
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50px;">ID</th>
                <th>Municipality Name</th>
                <th>District</th>
                <th>Province</th>
                <th>Category Type</th>
                <th style="width: 90px; text-align: center;">Total Wards</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($palikas as $p): ?>
                <tr>
                    <td>#<?= $p['id'] ?></td>
                    <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                    <td><?= htmlspecialchars($p['district']) ?></td>
                    <td><?= htmlspecialchars($p['province']) ?></td>
                    <td><?= htmlspecialchars($p['type']) ?></td>
                    <td style="text-align: center;"><?= $p['total_wards'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>