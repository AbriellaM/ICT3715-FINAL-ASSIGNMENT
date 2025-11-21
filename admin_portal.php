<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    die('Unauthorized. Please log in as an admin.');
}

// Example: check if there are paid applications
// Replace with your own query logic
$hasPaidApplications = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Administrators Portal - Amandla High School Locker System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: url('/amandla-lockersystem/css/images/hallway.jpg') center/cover no-repeat fixed;
      min-height: 100vh;
      margin: 0;
      display: flex;
      flex-direction: column;
    }

    /* Glass Navbar */
    .glass-nav {
      background: rgba(255,255,255,0.25) !important;
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(255,255,255,0.2);
    }
    .glass-nav .nav-link {
      color: #000 !important; /* dark text */
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

    /* Big Flash Card */
    .big-card-container {
      flex: 1;
      display: flex;
      align-items: flex-start; /* lift higher */
      justify-content: center;
      padding: 6rem 1rem 3rem; /* more top padding */
    }
    .big-flash-card {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-radius: 20px;
      padding: 3rem;
      text-align: center;
      color: #000;
      box-shadow: 0 8px 25px rgba(0,0,0,0.3);
      border: 1px solid rgba(255,255,255,0.25);
      width: 100%;
      max-width: 900px;
    }
    .big-flash-card h2 {
      font-weight: bold;
      margin-bottom: 2rem;
    }
    .portal-links .btn {
      margin: 0.5rem;
      min-width: 200px;
    }
    .btn[disabled] {
      opacity: 0.6;
      cursor: not-allowed;
    }

     /* Glass Footer */
    .glass-footer {
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-top: 1px solid rgba(255, 255, 255, 0.3);
      box-shadow: 0 -4px 12px rgba(0,0,0,0.15);
      color: #000;
      font-weight: 500;
    }

  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg glass-nav">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold text-dark" href="#">Administrators Portal</a>
    <div class="collapse navbar-collapse justify-content-end">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="/amandla-lockersystem/index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="/amandla-lockersystem/admin/admin.php">Administrator</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- One Big Flash Card -->
<div class="big-card-container">
  <div class="big-flash-card">
    <h2>Administrators Portal</h2>
    <p class="lead mb-4">Quick access to all administrative functions</p>
    <div class="portal-links">
      <a href="/amandla-lockersystem/admin/admin_applications.php" class="btn btn-dark">Applications</a>
      <a href="/amandla-lockersystem/admin/payments.php" class="btn btn-outline-dark">Payments</a>
      <a href="/amandla-lockersystem/admin/waitinglist.php" class="btn btn-outline-dark">Waiting List</a>

      <?php if ($hasPaidApplications): ?>
        <a href="/amandla-lockersystem/admin/allocate_lockers.php" class="btn btn-dark">Allocate Lockers</a>
      <?php else: ?>
        <button class="btn btn-dark" disabled title="No paid applications yet">Allocate Lockers</button>
      <?php endif; ?>

      <a href="/amandla-lockersystem/admin/mis_reports.php" class="btn btn-outline-dark">MIS Reports</a>
    </div>
  </div>
</div>

<!-- Footer -->
<footer class="glass-footer text-center py-3 mt-auto">
  &copy; <?= date('Y') ?> Amandla High School Locker System. All rights reserved.
</footer>

</body>
</html>