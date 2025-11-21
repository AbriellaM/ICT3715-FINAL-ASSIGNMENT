<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    die('Unauthorized. Please log in as an admin.');
}

$admin_id = $_SESSION['admin_id'];

// DB connection
$mysqli = new mysqli('127.0.0.1', 'root', '', 'amandlahighschool_lockersystem');
if ($mysqli->connect_errno) {
    die('Database connection failed: ' . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");

// Handle status update
if (isset($_POST['update_status'])) {
    $waiting_id = $mysqli->real_escape_string($_POST['waiting_id']);
    $new_status = $mysqli->real_escape_string($_POST['status']);
    $ok = $mysqli->query("UPDATE waitinglist 
                          SET Status='$new_status', AdminID='$admin_id' 
                          WHERE WaitingListID='$waiting_id'");
    $msg = $ok ? "Status updated successfully." : "Error updating status: " . $mysqli->error;
}

// Handle filter
$allowed = ['Waiting','Allocated','Cancelled','all'];
$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, $allowed)) {
    $filter = 'all';
}
$whereClause = $filter !== 'all' ? "WHERE w.Status = '".$mysqli->real_escape_string($filter)."'" : '';

// Fetch waitinglist entries
$sql = "
    SELECT 
      w.WaitingListID,
      w.BookingID,
      w.Status,
      w.RequestedOn,
      p.ParentName,
      p.ParentEmail,
      s.StudentName,
      s.StudentSurname,
      s.StudentGrade,
      l.LockerID
    FROM waitinglist w
    JOIN bookings b ON w.BookingID = b.BookingID
    JOIN students s ON b.StudentSchoolNumber = s.StudentSchoolNumber
    JOIN parents p ON b.ParentID = p.ParentID
    LEFT JOIN lockers l ON w.LockerID = l.LockerID
    $whereClause
    ORDER BY w.RequestedOn ASC
";
$result = $mysqli->query($sql);
?>  

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Waiting List - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: url('/amandla-lockersystem/css/images/hallway.jpg') center/cover no-repeat fixed;
      min-height: 100vh;
      margin: 0;
      padding: 0;
      color: #000;
    }
/* Glass card already has blur and transparency */
.glass-card {
  background: rgba(255, 255, 255, 0.25);
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 6px 25px rgba(0,0,0,0.2);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: #000;
}

/* Make the table transparent so the glass shows through */
.glass-card .table {
  background: transparent;
  color: #000;
  border-color: rgba(0, 0, 0, 0.15);
}

/* Frosted header row */
.glass-card .table thead th {
  background: rgba(255, 255, 255, 0.35);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  color: #000;
  border-bottom: 1px solid rgba(0, 0, 0, 0.2);
}

/* Transparent rows with subtle striping */
.glass-card .table tbody td {
  background: transparent;
  color: #000;
}

.glass-card .table.table-striped tbody tr:nth-of-type(odd) {
  background-color: rgba(255, 255, 255, 0.15);
}

.glass-card .table.table-hover tbody tr:hover {
  background-color: rgba(255, 255, 255, 0.25);
}
    .glass-nav {
      background: rgba(255, 255, 255, 0.2) !important;
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.3);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
    .glass-nav .nav-link.active::after {
      width: 100%;
    }
    .glass-footer {
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-top: 1px solid rgba(255, 255, 255, 0.3);
  box-shadow: 0 -4px 12px rgba(0,0,0,0.15);
  color: #000;
   }
  </style>
</head>
<body class="d-flex flex-column min-vh-100">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg glass-nav fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold text-dark" href="#">Amandla High School WaitingList</a>
    <div class="collapse navbar-collapse justify-content-end">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="/amandla-lockersystem/admin/admin_portal.php">Administrators Portal</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Main Content -->
<div class="container my-auto pt-5">
  <div class="glass-card mt-3">
    <h2 class="fw-bold mb-4">Waiting List Management</h2>
    <?php if (!empty($msg)): ?>
      <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- Quick Filter -->
    <form method="GET" class="mb-3 d-flex">
      <label class="me-2 fw-bold">Filter by Status:</label>
      <select name="filter" class="form-select w-auto me-2" onchange="this.form.submit()">
        <option value="all" <?= $filter=='all'?'selected':'' ?>>All</option>
        <option value="Waiting"   <?= $filter=='Waiting'?'selected':'' ?>>Waiting</option>
        <option value="Allocated" <?= $filter=='Allocated'?'selected':'' ?>>Allocated</option>
        <option value="Cancelled" <?= $filter=='Cancelled'?'selected':'' ?>>Cancelled</option>
      </select>
      <a href="waitinglist.php" class="btn btn-outline-secondary btn-sm ms-2">Reset</a>
      <noscript><button type="submit" class="btn btn-dark">Apply</button></noscript>
    </form>
  <?php if(isset($result) && $result->num_rows > 0): ?>
  <div class="table-responsive">
    <table class="table table-bordered table-striped table-hover align-middle">
      <thead>
        <tr>
          <th>Parent</th>
          <th>Email</th>
          <th>Student</th>
          <th>Grade</th>
          <th>Locker</th>
          <th>Status</th>
          <th>Requested On</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['ParentName']) ?></td>
            <td><?= htmlspecialchars($row['ParentEmail']) ?></td>
            <td><?= htmlspecialchars($row['StudentName'] . ' ' . $row['StudentSurname']) ?></td>
            <td><?= htmlspecialchars($row['StudentGrade']) ?></td>
            <td><?= htmlspecialchars($row['LockerID'] ?? '—') ?></td>
            <td>
              <?php if ($row['Status'] === 'Waiting'): ?>
                <span class="badge bg-warning text-dark">Waiting</span>
              <?php elseif ($row['Status'] === 'Allocated'): ?>
                <span class="badge bg-success">Allocated</span>
              <?php else: ?>
                <span class="badge bg-secondary">Cancelled</span>
              <?php endif; ?>
            </td>
            <td><?= date('d M Y H:i', strtotime($row['RequestedOn'])) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <p>No <?= htmlspecialchars($filter) ?> records found.</p>
<?php endif; ?>
  </div>
</div>

<footer class="glass-footer text-center py-3 mt-auto">
 &copy; <?= date('Y') ?> Amandla High School Locker System. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>