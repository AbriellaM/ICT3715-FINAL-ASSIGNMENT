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
        // ✅ Set session variables here, after successful login
        $_SESSION['admin_id']    = $admin['AdminID'];
        $_SESSION['admin_email'] = $admin['AdminEmail'];
        $_SESSION['admin']       = $admin; // keep full row if you want

        header('Location: /amandla-lockersystem/admin/admin.php');
        exit;
    } else {
        $error = 'Invalid login.';
    }
}

$loggedIn = isset($_SESSION['admin_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Administrator Login - Amandla Locker System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>

 /* Background */
body {
  background: url('/amandla-lockersystem/css/images/hallway.jpg') center/cover no-repeat fixed;
  min-height: 100vh;
  margin: 0;
  padding: 0;
}

/* Remove overlay completely */
body::before {
  content: none;
}

/* Light glass card */
.glass-card {
  position: relative;
  z-index: 1;
  background: rgba(255, 255, 255, 0.25); /* light frosted glass */
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 6px 25px rgba(0,0,0,0.2);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: #000; /* dark text for readability */
}
.glass-card h4, .glass-card h5, .glass-card label, .glass-card p {
  color: #000;
}

/* Buttons */
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

/* Glass Navbar */
.glass-nav {
  background: rgba(255, 255, 255, 0.2) !important;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
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
<body class="d-flex flex-column min-vh-100">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg glass-nav fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold text-dark" href="#">Amandla High School Locker System</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="/amandla-lockersystem/index.php">Home</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Main Content -->
<div class="container my-auto position-relative z-1">
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
          <h5 class="mb-3 text-dark">Welcome, <?= safe($_SESSION['admin']['AdminName']) ?></h5>
          <p class="text-dark">You are logged in as <?= safe($_SESSION['admin']['AdminEmail']) ?></p>
          <a href="admin_portal.php" class="btn btn-black w-100 mb-2">Go to Administrators Portal</a>
          <a href="admin.php?logout=1" class="btn btn-outline-light w-100">Logout</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<footer class="glass-footer text-center py-3 mt-auto">
  &copy; <?= date('Y') ?> Amandla High School Locker System. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>