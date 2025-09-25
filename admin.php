<?php
// Admin Login 

session_start();

// Quick DB connect
$db = new mysqli('127.0.0.1','root','','amandlahighschool_lockersystem');
if ($db->connect_errno) die('Database connection failed');
$db->set_charset('utf8mb4');

// Escape helper to keep output safe
function safe($txt) { return htmlspecialchars($txt ?? '', ENT_QUOTES, 'UTF-8'); }

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['admin_email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $stmt = $db->prepare("SELECT * FROM administrator WHERE AdminEmail = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Note: Plain-text check now — change to password_verify() later for security
    if ($admin && $pass === $admin['AdminPassword']) {
        $_SESSION['admin'] = $admin;
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Invalid login.';
    }
}

$loggedIn = isset($_SESSION['admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Administrator Login - Amandla Locker System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
/* My styling of my page */
body {
  background: url('css/images/hallway.jpg') center/cover no-repeat fixed;
  min-height: 100vh;
}
.glass-card {
  background: rgba(255, 255, 255, 0.15);
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 6px 25px rgba(0,0,0,0.25);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}
.btn-modern {
  background: black;
  color: white;
  border: none;
  padding: 0.75rem;
  font-weight: 600;
  border-radius: 50px;
}
.btn-modern:hover {
  background: #333;
}
</style>
</head>
<body class="d-flex flex-column min-vh-100">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="#">Amandla High School Locker System</a>
    <ul class="navbar-nav ms-auto">
      <li class="nav-item"><a class="nav-link active" href="admin.php">Administrator</a></li>
      <li class="nav-item"><a class="nav-link" href="parents.php">Parents</a></li>
    </ul>
  </div>
</nav>

<!-- Main Content -->
<div class="container my-auto">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="glass-card">
        <?php if ($error): ?>
          <div class="alert alert-danger"><?= safe($error) ?></div>
        <?php endif; ?>

        <?php if (!$loggedIn): ?>
          <h4 class="text-center fw-bold mb-4">Administrator Login</h4>
          <form method="post">
            <div class="mb-3">
              <label>Admin Email</label>
              <input type="email" name="admin_email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label>Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-modern w-100">Sign In</button>
          </form>
        <?php else: ?>
          <h5 class="mb-3">Welcome, <?= safe($_SESSION['admin']['AdminName']) ?></h5>
          <p class="text-muted">You are logged in as <?= safe($_SESSION['admin']['AdminEmail']) ?></p>
          <a href="admin_dashboard.php" class="btn btn-success w-100 mb-2">Go to Dashboard</a>
          <a href="admin.php?logout=1" class="btn btn-outline-dark w-100">Logout</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<footer class="text-center text-white-50 py-2 small">
  &copy; <?= date('Y') ?> Amandla High School Locker System
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>