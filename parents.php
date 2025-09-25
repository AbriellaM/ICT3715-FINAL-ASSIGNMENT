<?php
// Parent Login

session_start();

// Quick DB connect
$db = new mysqli('127.0.0.1','root','','amandlahighschool_lockersystem');
if ($db->connect_errno) die('Database connection failed');
$db->set_charset('utf8mb4');

// Escape helper for safe output
function safe($txt) { return htmlspecialchars($txt ?? '', ENT_QUOTES, 'UTF-8'); }

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: parents.php');
    exit;
}

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['parent_email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $stmt = $db->prepare("SELECT * FROM parents WHERE ParentEmail = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $parent = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Plain-text match for now — replace with password hashing later
    if ($parent && $pass === $parent['Password']) {
        $_SESSION['parent'] = $parent;
        header('Location: parents.php');
        exit;
    } else {
        $error = 'Invalid login.';
    }
}

$loggedIn = isset($_SESSION['parent']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Parent Login - Amandla Locker System</title>
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
      <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
      <li class="nav-item"><a class="nav-link" href="admin.php">Administrator</a></li>
      <li class="nav-item"><a class="nav-link active" href="parents.php">Parents</a></li>
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
          <h4 class="text-center fw-bold mb-4">Parent Login</h4>
          <form method="post">
            <div class="mb-3">
              <label>Parent Email</label>
              <input type="email" name="parent_email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label>Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-modern w-100">Sign In</button>
          </form>
        <?php else: ?>
          <h5 class="mb-3">Welcome, <?= safe($_SESSION['parent']['ParentName']) ?></h5>
          <p class="text-muted">You are logged in as <?= safe($_SESSION['parent']['ParentEmail']) ?></p>
          <a href="parents_dashboard.php" class="btn btn-success w-100 mb-2">Go to Dashboard</a>
          <a href="parents.php?logout=1" class="btn btn-outline-dark w-100">Logout</a>
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
