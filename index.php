 <?php
session_start();

// Database connection
$mysqli = new mysqli('127.0.0.1', 'root','', 'amandlahighschool_lockersystem');
if ($mysqli->connect_errno) { die('Database connection failed.'); }
$mysqli->set_charset('utf8mb4');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // helpful during dev

// Helpers...
function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function flash($key, $msg = null) {
  if ($msg !== null) { $_SESSION['flash'][$key] = $msg; return; }
  if (!empty($_SESSION['flash'][$key])) { $m = $_SESSION['flash'][$key]; unset($_SESSION['flash'][$key]); return $m; }
  return null;
}
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }

// Login submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
  if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
    flash('error', 'Invalid session. Please try again.');
    header('Location: admin.php'); exit;
  }

  // IMPORTANT: ensure your HTML has name="login" and name="password"
  $login    = trim($_POST['login'] ?? '');      // can be RecordID or AdminEmail
  $password = (string)($_POST['password'] ?? '');

  if ($login === '' || $password === '') {
    flash('error', 'Please enter your email/RecordID and password.');
    header('Location: admin.php'); exit;
  }

  // Prefer exact match; LIMIT 1 avoids ambiguity when emails repeat across rows
  $sql = "SELECT RecordID, AdminID, AdminName, AdminSurname, AdminEmail, AdminPassword
          FROM administrator
          WHERE RecordID = ? OR AdminEmail = ?
          LIMIT 1";
  $stmt = $mysqli->prepare($sql);
  $stmt->bind_param('ss', $login, $login);
  $stmt->execute();
  $admin = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  $ok = false;
  if ($admin) {
    // supports both hashed and plaintext in your dataset
    $ok = password_verify($password, $admin['AdminPassword']) || hash_equals($admin['AdminPassword'], $password);
  }

  if ($ok) {
    $_SESSION['admin'] = [
      'record_id' => $admin['RecordID'],
      'admin_id'  => $admin['AdminID'],
      'name'      => $admin['AdminName'],
      'surname'   => $admin['AdminSurname'],
      'email'     => $admin['AdminEmail'],
    ];
    header('Location: admin.php'); exit;
  } else {
    flash('error', 'Invalid RecordID or Password.');
    header('Location: admin.php'); exit;
  }
}



// Assign from waiting list
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'assign_waiting') {
  if (!isset($_SESSION['admin'])) { header('Location: admin.php'); exit; }
  if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
    flash('error', 'Invalid session. Please try again.');
    header('Location: admin.php'); exit;
  }

  $waitingListId = trim($_POST['waitinglist_id'] ?? '');
  $lockerId      = trim($_POST['locker_id'] ?? '');
  $studentNumber = intval($_POST['student_number'] ?? 0);
  $parentId      = trim($_POST['parent_id'] ?? '');

  if (!$waitingListId || !$lockerId || !$studentNumber || !$parentId) {
    flash('error', 'Missing data to assign locker.');
    header('Location: admin.php'); exit;
  }

  // Ensure locker is not already booked
  $stmt = $mysqli->prepare("SELECT COUNT(*) AS c FROM bookings WHERE LockerID = ?");
  $stmt->bind_param('s', $lockerId);
  $stmt->execute();
  $c = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
  $stmt->close();
  if ($c > 0) {
    flash('error', "Locker $lockerId is already assigned.");
    header('Location: admin.php'); exit;
  }

  // Create booking + update locker + remove from waiting list
  $mysqli->begin_transaction();
  try {
    $bookingId = 'BK'.bin2hex(random_bytes(6));
    $today = date('Y-m-d');

    $stmt = $mysqli->prepare("INSERT INTO bookings (BookingID, StudentSchoolNumber, ParentID, LockerID, BookingDate, RecordID) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param('siisss', $bookingId, $studentNumber, $parentId, $lockerId, $today, $_SESSION['admin']['record_id']);
    $stmt->execute(); $stmt->close();

    $stmt = $mysqli->prepare("UPDATE lockers SET AssignedDate = ?, BookingID = ? WHERE LockerID = ?");
    $stmt->bind_param('sss', $today, $bookingId, $lockerId);
    $stmt->execute(); $stmt->close();

    $stmt = $mysqli->prepare("DELETE FROM waitinglist WHERE WaitingListID = ?");
    $stmt->bind_param('s', $waitingListId);
    $stmt->execute(); $stmt->close();

    $mysqli->commit();
    flash('success', "Locker $lockerId assigned and booking $bookingId created.");
  } catch (Throwable $e) {
    $mysqli->rollback();
    flash('error', 'Could not assign locker. Please try again.');
  }
  header('Location: admin.php'); exit;
}

$loggedIn = isset($_SESSION['admin']);

// MIS data (only if logged in)
$g8 = $g12 = 0; $labels = []; $values = [];
if ($loggedIn) {
  // Grade usage (8 and 12)
  $usage = [];
  $res = $mysqli->query("
    SELECT s.StudentGrade AS grade, COUNT(*) AS cnt
    FROM bookings b
    JOIN students s ON s.StudentSchoolNumber = b.StudentSchoolNumber
    WHERE s.StudentGrade IN ('Grade 8','Grade 12')
    GROUP BY s.StudentGrade
  ");
  while ($row = $res->fetch_assoc()) { $usage[$row['grade']] = (int)$row['cnt']; }
  $g8  = $usage['Grade 8'] ?? 0;
  $g12 = $usage['Grade 12'] ?? 0;

  // Bookings Jan–Jun 2026
  $series = [];
  $res2 = $mysqli->query("
    SELECT DATE_FORMAT(BookingDate, '%Y-%m') AS m, COUNT(*) AS cnt
    FROM bookings
    WHERE BookingDate BETWEEN '2026-01-01' AND '2026-06-30'
    GROUP BY m ORDER BY m
  ");
  while ($row = $res2->fetch_assoc()) { $series[$row['m']] = (int)$row['cnt']; }
  $labels = array_keys($series);
  $values = array_values($series);
}
 
?>
 
 <!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Amandla High School Locker System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="background: url('css/images/hallway.jpg') center/cover no-repeat; height: 100vh;">

  <!-- Dark overlay for contrast -->
  <div class="h-100 d-flex flex-column justify-content-between" style="background-color: rgba(0, 0, 0, 0.6);">

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg bg-dark">
      <div class="container-fluid">
        <a class="navbar-brand text-white fw-bold" href="#">Amandla Locker System</a>
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

    <!-- Welcome Section -->
    <div class="container text-center text-white my-auto">
      <h1 class="display-4 fw-bold">Welcome to the Amandla High School Locker System</h1>
      <p class="lead">Creating an effortless locker system.</p>
      <form action="role-selection.php" method="post">
        <button type="submit" class="btn btn-light btn-lg">Get Started</button>
      </form>
    </div>

    <!-- Footer -->
    <footer class="text-center text-white py-3">
      <small>© 2025 Amandla High School Locker System</small>
    </footer>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
