<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Select Your Role — Amandla Locker System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
   /* The styling of my page */

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

  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg bg-dark navbar-dark">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold text-white" href="#">Amandla High School Locker System</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link text-white" href="index.php">Home</a></li>
          <li class="nav-item"><a class="nav-link text-white" href="admin.php">Administrator</a></li>
          <li class="nav-item"><a class="nav-link text-white" href="parents.php">Parents</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Main content grows to fill space -->
  <main class="flex-grow-1 d-flex justify-content-center align-items-start pt-5">
    <div class="col-md-8">
      <div class="glass-card text-center text-white mt-5">
        <h1 class="display-5 fw-bold mb-4 text-dark">What would you like to do?</h1>
        <p class="lead mb-5 text-dark fw-bold">Choose your role to continue</p>

        <div class="row justify-content-center g-4">
  <div class="col-md-5">
    <div class="glass-card p-4 text-white">
      <a href="parents.php" class="stretched-link text-decoration-none text-dark fw-bold">
        👨‍👩‍👧 Parent / Guardian
      </a>
    </div>
  </div>
  <div class="col-md-5">
    <div class="glass-card p-4 text-white">
      <a href="admin.php" class="stretched-link text-decoration-none text-dark fw-bold">
        🔑 Administrator
      </a>
    </div>
         </div>
        </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Footer sticks to bottom -->
  <footer class="text-center text-white py-3 mt-auto">
    <small>© 2025 Amandla Locker System. Built by Abigail Padayachy 50748122.</small>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
  