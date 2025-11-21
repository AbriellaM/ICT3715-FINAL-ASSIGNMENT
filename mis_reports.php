<?php
session_start();
if (!isset($_SESSION['admin'])) { header('Location: /amandla-lockersystem/admin.php'); exit; }

mysqli_report(MYSQLI_REPORT_OFF);
$mysqli = new mysqli('127.0.0.1', 'root', '', 'amandlahighschool_lockersystem');
if ($mysqli->connect_errno) { die('Database connection failed: '.$mysqli->connect_error); }
$mysqli->set_charset('utf8mb4');

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function month_name($m){
    $m = (int)$m; if ($m < 1 || $m > 12) return '';
    $dt = DateTime::createFromFormat('!m', (string)$m);
    return $dt ? $dt->format('M') : '';
}
function qall($mysqli, $sql){
    $res = $mysqli->query($sql);
    if (!$res) die('Query failed: '.$mysqli->error);
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $res->free();
    return $rows;
}

// optional grade filter
$gradeFilter = $_GET['grade'] ?? '';
$where = $gradeFilter !== '' ? "WHERE LockerGrade = ".(int)$gradeFilter : '';
/* Locker usage per grade */
$usageRows = qall($mysqli, "
    SELECT LockerGrade,
           COUNT(*) AS TotalLockers,
           SUM(CASE WHEN Status='Allocated' THEN 1 ELSE 0 END) AS Occupied,
           SUM(CASE WHEN Status='Available' THEN 1 ELSE 0 END) AS Available,
           SUM(CASE WHEN Status='Maintenance' THEN 1 ELSE 0 END) AS Maintenance
      FROM lockers
      $where
  GROUP BY LockerGrade
  ORDER BY LockerGrade
");

$usageData = [];
$grandTotals = ['Occupied'=>0,'Available'=>0,'Maintenance'=>0,'TotalLockers'=>0];

foreach ($usageRows as $r) {
    $g = trim((string)$r['LockerGrade']);
    $occ   = (int)$r['Occupied'];
    $avail = (int)$r['Available'];
    $maint = (int)$r['Maintenance'];
    $total = (int)$r['TotalLockers'];
    $pct   = $total > 0 ? round(($occ / $total) * 100, 1) : 0;

    $usageData[$g] = [
        'Occupied'     => $occ,
        'Available'    => $avail,
        'Maintenance'  => $maint,
        'TotalLockers' => $total,
        'Percent'      => $pct
    ];

    // accumulate grand totals
    $grandTotals['Occupied']    += $occ;
    $grandTotals['Available']   += $avail;
    $grandTotals['Maintenance'] += $maint;
    $grandTotals['TotalLockers']+= $total;
}

// overall occupancy %
$grandTotals['Percent'] = $grandTotals['TotalLockers'] > 0
    ? round(($grandTotals['Occupied'] / $grandTotals['TotalLockers']) * 100, 1)
    : 0;

$labels = array_keys($usageData);
$occupiedSeries  = array_map(fn($g)=>$usageData[$g]['Occupied'], $labels);
$availableSeries = array_map(fn($g)=>$usageData[$g]['Available'], $labels);

/* Bookings reserved Jan–Jun 2026 */
$forRows = qall($mysqli, "
    SELECT MONTH(BookedForDate) AS M, COUNT(*) AS TotalBookings
      FROM bookings
     WHERE BookedForDate >= '2026-01-01' AND BookedForDate < '2026-07-01'
  GROUP BY M ORDER BY M
");
$forLabels = []; $forSeries = [];
foreach ($forRows as $r) {
    $forLabels[] = month_name($r['M'] ?? 0);
    $forSeries[] = (int)($r['TotalBookings'] ?? 0);
}
$grand2026 = array_sum($forSeries);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MIS Reports - Amandla High School Locker System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

  <style>
    body { background: url('/amandla-lockersystem/css/images/hallway.jpg') center/cover no-repeat fixed; min-height: 100vh; }
    .glass-card {
      background: rgba(255,255,255,0.2);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-radius: 16px;
      padding: 2rem;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      border: 1px solid rgba(255,255,255,0.2);
      margin-bottom: 1.5rem;
      color: #000; /* ensure text is black */
    }
    .table-glass th, .table-glass td { background-color: rgba(255,255,255,0.2); color: #000; }
    .glass-nav {
      background: rgba(255,255,255,0.2);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(255,255,255,0.3);
    }
    .glass-nav .nav-link {
      color: #000 !important;
      font-weight: 600;
      position: relative;
    }
    .glass-nav .nav-link::after {
      content: "";
      position: absolute;
      width: 0;
      height: 2px;
      left: 0;
      bottom: -4px;
      background-color: black;
      transition: width 0.3s ease;
    }
    .glass-nav .nav-link:hover::after,
    .glass-nav .nav-link.active::after { width: 100%; }
    .glass-footer {
      background: rgba(255,255,255,0.2 );
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-top: 1px solid rgba(255,255,255,0.3);
      color: #000;
      text-align: center;
      padding: .5rem;
      font-size: 0.85rem;
    }
    .content-offset { padding-top: 72px; }
    canvas { min-height: 300px; }
    /* Glass footer */
footer {
  backdrop-filter: blur(10px) saturate(180%);
  -webkit-backdrop-filter: blur(10px) saturate(180%);
  background-color: rgba(255, 255, 255, 0.25);
  border-top: 1px solid rgba(0,0,0,0.1);
  text-align: center;
  padding: 1rem;
  color: #000;
  width: 100%;
  margin-top: auto;
  font-size: 0.9rem;
  font-weight: 500;
}
  </style>
</head>
<body class="d-flex flex-column min-vh-100">

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg glass-nav fixed-top">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold text-dark" href="/amandla-lockersystem/index.php">Amandla High School Locker System</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMIS">
        <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
      </button>
      <div id="navMIS" class="collapse navbar-collapse justify-content-end">
          <ul li class="nav-item"><a class="nav-link" href="/amandla-lockersystem/admin/admin_portal.php">Administrators Portal</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="container content-offset py-4 flex-grow-1">

    <!-- KPI -->
    <div class="glass-card">
      <h2 class="mb-4">Management Information Reports</h2>
      <h5 class="text-dark">Total Bookings (Jan–Jun 2026)</h5>
      <h1 class="display-4 fw-bold text-primary"><?= esc($grand2026) ?></h1>
    </div>

    <form method="get" class="mb-3">
  <label for="grade" class="form-label fw-bold">Filter by Grade:</label>
  <select name="grade" id="grade" class="form-select w-auto d-inline-block">
    <option value="">All Grades</option>
    <option value="8" <?= $gradeFilter=='8'?'selected':'' ?>>Grade 8</option>
    <option value="9" <?= $gradeFilter=='9'?'selected':'' ?>>Grade 9</option>
    <option value="10" <?= $gradeFilter=='10'?'selected':'' ?>>Grade 10</option>
    <option value="11" <?= $gradeFilter=='11'?'selected':'' ?>>Grade 11</option>
    <option value="12" <?= $gradeFilter=='12'?'selected':'' ?>>Grade 12</option>
  </select>
  <button type="submit" class="btn btn-dark btn-sm">Apply</button>
  <?php if ($gradeFilter): ?>
    <a href="/amandla-lockersystem/admin/mis_reports.php" class="btn btn-outline-secondary btn-sm">Reset</a>
  <?php endif; ?>
</form>

    <!-- Locker Usage -->
    <div class="glass-card">
      <h5 class="text-dark">Locker Usage (Grades 8 - 12)</h5>
      <canvas id="lockerChart"></canvas>
      <table class="table table-glass mt-3">
  <thead>
    <tr>
      <th>Grade</th>
      <th>Occupied</th>
      <th>Available</th>
      <th>Total Lockers</th>
      <th>Occupancy %</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($usageData as $grade => $data): ?>
      <tr>
        <td><?= esc($grade) ?></td>
        <td><?= esc($data['Occupied']) ?></td>
        <td><?= esc($data['Available']) ?></td>
        <td><?= esc($data['TotalLockers']) ?></td>
        <td><?= esc($data['Percent']) ?>%</td>
      </tr>
    <?php endforeach; ?>
    <tr class="fw-bold">
      <td>Total</td>
      <td><?= esc($grandTotals['Occupied']) ?></td>
      <td><?= esc($grandTotals['Available']) ?></td>
      <td><?= esc($grandTotals['TotalLockers']) ?></td>
      <td><?= esc($grandTotals['Percent']) ?>%</td>
    </tr>
  </tbody>
</table>
    </div>

      </div> <!-- end container -->

  <script>
    // Locker Usage Chart
    const lockerCtx = document.getElementById('lockerChart').getContext('2d');
    new Chart(lockerCtx, {
      type: 'bar',
      data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [
  { label: 'Occupied', data: <?= json_encode(array_column($usageData,'Occupied')) ?>, backgroundColor: 'rgba(255, 99, 132, 0.7)' },
  { label: 'Available', data: <?= json_encode(array_column($usageData,'Available')) ?>, backgroundColor: 'rgba(75, 192, 192, 0.7)' },
  { label: 'Maintenance', data: <?= json_encode(array_column($usageData,'Maintenance')) ?>, backgroundColor: 'rgba(255, 206, 86, 0.7)' }
] 
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'top', labels: { color: '#000' } },
          title: { display: true, text: 'Locker Usage by Grade', color: '#000' }
        },
        scales: { x: { ticks: { color: '#000' } }, y: { beginAtZero: true, ticks: { color: '#000' } } }
      }
    });

    // Bookings Jan–Jun 2026 Chart
    const ctx2026 = document.getElementById('bookings2026Chart').getContext('2d');
    new Chart(ctx2026, {
      type: 'bar',
      data: {
        labels: <?= json_encode($forLabels) ?>,
        datasets: [{ label: 'Bookings', data: <?= json_encode($forSeries) ?>, backgroundColor: 'rgba(54, 162, 235, 0.7)' }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false, labels: { color: '#000' } },
          title: { display: true, text: 'Bookings Jan–Jun 2026', color: '#000' }
        },
        scales: { x: { ticks: { color: '#000' } }, y: { beginAtZero: true, ticks: { color: '#000' } } }
      }
    });
  </script>

      <!-- Locker Booking Summary (Jan–Jun 2026) -->
    <div class="glass-card">
      <h5 class="text-dark">Locker Booking Summary (Jan–Jun 2026)</h5>
      <table class="table table-glass mt-3">
        <thead>
          <tr>
            <th>Month</th>
            <th>Total Bookings</th>
          </tr>
        </thead>
        <tbody>
          <?php
            // Ensure all months Jan–Jun appear
            $months = ['Jan','Feb','Mar','Apr','May','Jun'];
            $countsByMonth = [];
            for ($i = 0; $i < count($forLabels); $i++) {
                $countsByMonth[$forLabels[$i]] = $forSeries[$i];
            }
            foreach ($months as $mon) {
                $count = $countsByMonth[$mon] ?? 0;
          ?>
            <tr>
              <td><?= esc($mon) ?> 2026</td>
              <td><?= esc($count) ?></td>
            </tr>
          <?php } ?>
          <tr>
            <th>Total (Jan–Jun 2026)</th>
            <th><?= esc($grand2026) ?></th>
          </tr>
        </tbody>
      </table>
    </div>

    <footer class="glass-footer">
    &copy; <?= date('Y') ?> Amandla High School Locker System. All rights reserved.
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>