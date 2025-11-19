<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Amandla High School Locker System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: url('/amandla-lockersystem/css/images/hallway.jpg') center/cover no-repeat fixed;
      min-height: 100vh;
      margin: 0;
      padding: 0;
      display: flex;
      flex-direction: column;
    }

    /* Glass Navbar */
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

    /* Welcome Section */
    .welcome {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      text-align: center;
      color: #000;
      padding: 2rem;
    }
    .welcome-box {
      background: rgba(255, 255, 255, 0.15); /* subtle glass */
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      border-radius: 16px;
      padding: 3rem;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      border: 1px solid rgba(255, 255, 255, 0.25);
      max-width: 700px;

      /* Gentle fade-in */
      opacity: 0;
      animation: fadeInUp 1.5s ease forwards;
    }

    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to   { opacity: 1; transform: translateY(0); }
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
<nav class="navbar navbar-expand-lg glass-nav fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold text-dark" href="#">Amandla High School Locker System</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link active" href="/amandla-lockersystem/index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="/amandla-lockersystem/parents/parents.php">Parents</a></li>
        <li class="nav-item"><a class="nav-link" href="/amandla-lockersystem/admin/admin.php">Administrator</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Welcome Section -->
<div class="welcome">
  <div class="welcome-box text-center">
    <h1 class="fw-bold mb-3">Welcome to Amandla High School Locker System</h1>
    <p class="lead mb-4">Secure, simple, and transparent locker management for parents and administrators.</p>
    <!-- Optional Role Buttons -->
    <a href="/amandla-lockersystem/parents/parents.php" class="btn btn-dark m-2">Parents</a>
    <a href="/amandla-lockersystem/admin/admin.php" class="btn btn-outline-dark m-2">Administrator</a>
  </div>
</div>

<!-- Footer -->
<footer class="glass-footer text-center py-3 mt-auto">
  &copy; <?= date('Y') ?> Amandla High School Locker System. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>