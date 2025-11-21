<?php
session_start();
$mysqli = new mysqli('127.0.0.1','root','','amandlahighschool_lockersystem');
if ($mysqli->connect_error) {
    die("DB connection failed: " . $mysqli->connect_error);
}

$msg = "";

// ---------------- REGISTER ----------------
if (isset($_POST['register'])) {
    $parentId     = $_POST['parent_id'];
    $parenttitle  = $_POST['parent_title'];
    $name         = $_POST['name'];
    $surname      = $_POST['surname'];
    $email        = $_POST['email'];
    $phone        = $_POST['phone'];
    $home         = $_POST['home_address'];
    $password     = $_POST['password'];

    if (!preg_match('/^\d{13}$/', $parentId)) {
        $msg = "ParentID must be exactly 13 digits long.";
    } else {
       $hash = $password; // store as plain text
        $sql = "INSERT INTO parents 
                (ParentID, ParentTitle, ParentName, ParentSurname, ParentEmail, ParentHomeAddress, ParentPhoneNumber, Password) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("isssssss", $parentId, $parenttitle, $name, $surname, $email, $home, $phone, $hash);
        if ($stmt->execute()) {
            $msg = "Parent registered successfully with ParentID: ".$parentId;
        } else {
            $msg = "Error: ".$stmt->error;
        }
        $stmt->close();
    }
}

// ---------------- LOGIN ----------------
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $pass  = trim($_POST['password']);

    // Select all the fields you need
    $sql = "SELECT ParentID, ParentName, ParentSurname, ParentEmail, Password
            FROM parents
            WHERE ParentEmail=? AND Password=?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $email, $pass);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Successful login: set session array
        $_SESSION['parent'] = [
            'ParentID'      => $row['ParentID'],
            'ParentName'    => $row['ParentName'],
            'ParentSurname' => $row['ParentSurname'],
            'ParentEmail'   => $row['ParentEmail']
        ];
        header("Location: /amandla-lockersystem/parents/parents_dashboard.php");
        exit;
    } else {
        $msg = "Invalid email or password.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Parent Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      min-height: 100vh;
      /* ✅ Corrected path to your image folder */
      background: url('/amandla-lockersystem/css/images/hallway.jpg') center center / cover no-repeat fixed;
      color: #fff;
    }
    .card-glass {
      -webkit-backdrop-filter: blur(12px); /* ✅ Added prefix for browser support */
      backdrop-filter: blur(12px);
      background: rgba(255,255,255,0.25);
      border: 1px solid rgba(255,255,255,0.18);
      border-radius: 16px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }
    .navbar {
  background: rgba(255, 255, 255, 0.15) !important;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.3);
  box-shadow: 0 4px 12px rgba(0,0,0,0.25);
}

.navbar .nav-link {
  position: relative;
  text-decoration: none;
}

.navbar .nav-link::after {
  content: "";
  position: absolute;
  width: 0;
  height: 2px;
  left: 0;
  bottom: -4px;
  background-color: black;
  transition: width 0.3s ease;
}

.navbar .nav-link:hover::after,
.navbar .nav-link.active::after {
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

<body><nav class="navbar navbar-expand-lg glass-nav fixed-top">
 <div class="container-fluid">
    <a class="navbar-brand fw-bold text-dark" href="/amandla-lockersystem/index.php">Amandla High School Locker System</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link text-dark fw-bold" href="/amandla-lockersystem/index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-dark fw-bold active" href="/amandla-lockersystem/admin/admin.php">Administrator</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<div class="container d-flex justify-content-center align-items-center" style="min-height:100vh;">
  <div class="card card-glass p-4 col-md-5">
    <h3 class="mb-3">Parent Login</h3>
    <?php if ($msg): ?><div class="alert alert-danger"><?= $msg ?></div><?php endif; ?>
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button class="btn btn-dark w-100 text-white" name="login">Login</button>
    </form>
    <p class="mt-3">No account? <a href="/amandla-lockersystem/parents/parent_register.php" class="text-light">Register here</a></p>
  </div>
</div>

<!-- Footer -->
<footer class="glass-footer text-center py-3 mt-auto">
  &copy; <?= date('Y') ?> Amandla High School Locker System. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>