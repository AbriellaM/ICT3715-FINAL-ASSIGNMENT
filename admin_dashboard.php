<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: admin.php');
    exit;
}

$mysqli = new mysqli('127.0.0.1', 'root', '', 'amandlahighschool_lockersystem');
if ($mysqli->connect_errno) {
    die('Database connection failed: '.$mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function month_name($m){ return DateTime::createFromFormat('!m', (string)$m)->format('M'); }

// ----------------------
// Locker usage by Grade 8 & 11
// ----------------------
$usageSql = "
    SELECT LockerGrade,
           COUNT(*) AS TotalLockers,
           SUM(CASE WHEN BookingID IS NOT NULL AND BookingID <> '' THEN 1 ELSE 0 END) AS Occupied,
           SUM(CASE WHEN BookingID IS NULL OR BookingID = '' THEN 1 ELSE 0 END) AS Available
      FROM lockers
     WHERE LockerGrade IN ('Grade 8', 'Grade 11')
  GROUP BY LockerGrade
  ORDER BY LockerGrade
";
$usageRows = $mysqli->query($usageSql)->fetch_all(MYSQLI_ASSOC);

$grades = ['Grade 8','Grade 11'];
$usageData = [
    'Grade 8' => ['Occupied'=>0,'Available'=>0,'TotalLockers'=>0],
    'Grade 11'=> ['Occupied'=>0,'Available'=>0,'TotalLockers'=>0]
];
foreach ($usageRows as $r) {
    $g = $r['LockerGrade'];
    if (isset($usageData[$g])) {
        $usageData[$g]['Occupied'] = (int)$r['Occupied'];
        $usageData[$g]['Available'] = (int)$r['Available'];
        $usageData[$g]['TotalLockers'] = (int)$r['TotalLockers'];
    }
}
$labels = array_keys($usageData);
$occupiedSeries  = array_map(fn($g)=>$usageData[$g]['Occupied'], $labels);
$availableSeries = array_map(fn($g)=>$usageData[$g]['Available'], $labels);

// ----------------------
// Booking summary Jan–Jun 2026
// ----------------------
$summarySql = "
    SELECT YEAR(BookingDate) AS Y,
           MONTH(BookingDate) AS M,
           COUNT(*) AS TotalBookings
      FROM bookings
     WHERE BookingDate >= '2026-01-01' AND BookingDate < '2026-07-01'
  GROUP BY Y, M
  ORDER BY Y, M
";
$summaryRows = $mysqli->query($summarySql)->fetch_all(MYSQLI_ASSOC);
$totalBookings = array_sum(array_column($summaryRows, 'TotalBookings'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Admin Dashboard - Amandla High School Locker System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
body {
  background: url('css/images/hallway.jpg') center/cover no-repeat fixed;
  min-height: 100vh;
}
.backdrop { background-color: rgba(0,0,0,0.6); min-height: 100vh; }
.card-glass {
  background: rgba(255,255,255,0.12);
  border: 1px solid rgba(255,255,255,0.2);
  border-radius: 12px;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  color: #fff;
}
.table-dark th, .table-dark td { color: #fff; }
/* Force KPI heading white */
.kpi-heading { color: #fff !important; }
</style>
</head>
<body>
<div class="backdrop d-flex flex-column">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="#">Amandla High School Locker System</a>
    <ul class="navbar-nav ms-auto">
      <li class="nav-item"><a class="nav-link active" href="admin_dashboard.php">Dashboard</a></li>
      <li class="nav-item"><a class="nav-link" href="admin.php?logout=1">Logout</a></li>
    </ul>
  </div>
</nav>

<!-- Content -->
<div class="container py-4 flex-grow-1 text-white">
  <h2 class="fw-bold mb-4">Administrator Dashboard</h2>

  <!-- KPI -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card card-glass shadow-sm">
        <div class="card-body">
          <h6 class="mb-1 kpi-heading">Bookings (Jan–Jun 2026)</h6>
          <div class="display-6 fw-bold"><?= esc($totalBookings) ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Locker usage -->
  <div class="row g-4 mb-4">
    <div class="col-lg-7">
      <div class="card card-glass shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3">Locker Usage by Grade (8 & 11)</h5>
          <canvas id="usageChart" height="180"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card card-glass shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3">Usage Details</h5>
          <table class="table table-dark table-bordered table-sm mb-0">
            <thead>
              <tr>
                <th>Grade</th>
                <th>Occupied</th>
                <th>Available</th>
                <th>Total</th>
                <th>Occupancy %</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($labels as $g): 
                  $occ = $usageData[$g]['Occupied'];
                  $tot = $usageData[$g]['TotalLockers'];
                  $pct = $tot > 0 ? round(($occ / $tot) * 100) : 0;
              ?>
              <tr>
                <td><?= esc($g) ?></td>
                <td><?= esc($occ) ?></td>
                <td><?= esc($usageData[$g]['Available']) ?></td>
                <td><?= esc($tot) ?></td>
                <td><?= esc($pct) ?>%</td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Booking summary -->
  <div class="card card-glass shadow-sm mb-4">
    <div class="card-body">
      <h5 class="card-title mb-3">Locker Booking Summary (Jan–Jun 2026)</h5>
      <table class="table table-dark table-bordered table-sm mb-0">
        <thead>
          <tr>
            <th>Month</th>
            <th>Total Bookings</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($summaryRows)): ?>
            <tr><td colspan="2" class="text-center">No bookings found.</td></tr>
          <?php else: ?>
            <?php foreach ($summaryRows as $r): ?>
              <tr>
                <td><?= esc(month_name($r['M']).' '.$r['Y']) ?></td>
                <td><?= esc($r['TotalBookings']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        <tfoot>
          <tr class="fw-bold">
            <td>Total (Jan–Jun 2026)</td>
            <td><?= esc($totalBookings) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

</div>

<footer class="text-center text-white-50 py-2 small">&copy; <?= date('Y') ?> Amandla High School Locker System</footer>
</div>

<script>
const usageLabels   = <?= json_encode($labels) ?>;
const occupiedData  = <?= json_encode($occupiedSeries, JSON_NUMERIC_CHECK) ?>;
const availableData = <?= json_encode($availableSeries, JSON_NUMERIC_CHECK) ?>;

new Chart(
  document.getElementById('usageChart'),
  {
    type: 'bar',
    data: {
      labels: usageLabels,
      datasets: [
        { label: 'Occupied', data: occupiedData,  backgroundColor: 'rgba(13,110,253,0.7)' },
        { label: 'Available', data: availableData, backgroundColor: 'rgba(108,117,125,0.7)' }
      ]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true,
          ticks: { color: '#fff' },
          grid:  { color: 'rgba(255,255,255,0.2)' }
        },
        x: {
          ticks: { color: '#fff' },
          grid:  { color: 'rgba(255,255,255,0.2)' }
        }
      },
      plugins: {
        legend: { labels: { color: '#fff' } }
      }
    }
  }
);
</script>