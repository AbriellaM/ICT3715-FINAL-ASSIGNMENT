<?php
session_start();
require_once __DIR__ . '/../includes/dbconnect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';      // defines sendMail()
require_once __DIR__ . '/../includes/parentmailer.php';// calls sendMail()

if (!isset($_SESSION['parent'])) {
    header("Location: /amandla-lockersystem/parents/parents.php");
    exit;
}

$parentId      = (int)($_SESSION['parent']['ParentID'] ?? 0);
$parentName    = $_SESSION['parent']['ParentName'] ?? '';
$parentSurname = $_SESSION['parent']['ParentSurname'] ?? '';
$parentEmail   = $_SESSION['parent']['ParentEmail'] ?? '';

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$msg   = "";
$error = "";

/* ============================================================
   Register many students (writes only to students table)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_students'])) {
    $errors = [];

    if ($parentId === 0) {
        $errors[] = "Parent ID missing or invalid.";
    } else {
        foreach ($_POST['students'] ?? [] as $s) {
            $schoolNo = (int)($s['schoolno'] ?? 0);
            $name     = trim($s['name'] ?? '');
            $surname  = trim($s['surname'] ?? '');
            $grade    = trim($s['grade'] ?? '');

            if (!$schoolNo || $name === '' || $surname === '' || $grade === '') {
                $errors[] = "Missing fields for one or more students.";
                continue;
            }

            $result = registerStudent($mysqli, $schoolNo, $name, $surname, $grade, $parentId);
            if ($result !== "success") {
                $errors[] = "Failed to register student {$name} {$surname} (School No: {$schoolNo}): {$result}";
            }
        }
    }

    if (empty($errors)) {
        $msg = "Student(s) registered successfully.";
    } else {
        $error = implode("<br>", $errors);
    }
}

/* ============================================================
   Apply for many lockers (writes to bookings + waitinglist)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_many_lockers'])) {
    foreach ($_POST['applications'] ?? [] as $app) {
        $studentNo = (int)($app['student_no'] ?? 0);
        $date = trim($app['date'] ?? '');

        if ($studentNo && $date) {
            $stmt = $mysqli->prepare("
                SELECT StudentName, StudentSurname
                FROM students
                WHERE StudentSchoolNumber=? AND ParentID=?
            ");
            if ($stmt) {
                $stmt->bind_param("ii", $studentNo, $parentId);
                $stmt->execute();
                $res = $stmt->get_result();

                if ($row = $res->fetch_assoc()) {
                    $bookingId = applyForLocker(
                        $mysqli,
                        $studentNo,
                        $row['StudentName'],
                        $row['StudentSurname'],
                        $parentId,
                        $date
                    );

                    if ($bookingId) {
                        $msg .= "Locker application for {$row['StudentName']} {$row['StudentSurname']} submitted (BookingID: {$bookingId}).";

                        if (!empty($parentEmail)) {
                            sendPaymentEmail(
                                $parentEmail,
                                $parentName,
                                $parentSurname,
                                $row['StudentName'],
                                $row['StudentSurname'],
                                $bookingId
                            );
                        }
                    } else {
                        $error .= "Failed to apply for locker for {$row['StudentName']} {$row['StudentSurname']}.<br>";
                    }
                } else {
                    $error .= "Student with number {$studentNo} not found for this parent.<br>";
                }

                $stmt->close();
            } else {
                $error .= "Failed to prepare student query: ".$mysqli->error."<br>";
            }
        } else {
            $error .= "Invalid student number or date.<br>";
        }
    }
}

/* ============================================================
   Cancel booking
   ============================================================ */
if (isset($_POST['cancel_booking']) && !empty($_POST['booking_id'])) {
    $bookingId = $_POST['booking_id'];
    if (cancelBooking($mysqli, $bookingId, 'Cancelled by parent')) {
        $msg = "Booking $bookingId cancelled successfully.";
    } else {
        $error = "Failed to cancel booking $bookingId.";
    }
}

/* ============================================================
   Fetch bookings joined with student info
   ============================================================ */
$bookings = [];
$stmt = $mysqli->prepare("
  SELECT b.BookingID, b.Status AS BookingStatus,
         s.StudentName, s.StudentSurname, s.StudentGrade
  FROM bookings b
  JOIN students s ON b.StudentSchoolNumber = s.StudentSchoolNumber
  WHERE b.ParentID = ?
  ORDER BY b.BookingID DESC
");
if ($stmt) {
    $stmt->bind_param("i", $parentId);
    if ($stmt->execute()) {
        $bookingsRes = $stmt->get_result();
        $bookings = $bookingsRes ? $bookingsRes->fetch_all(MYSQLI_ASSOC) : [];
        $bookingsRes?->free();
    } else {
        error_log("Fetch bookings execute failed: ".$stmt->error);
    }
    $stmt->close();
} else {
    error_log("Fetch bookings prepare failed: ".$mysqli->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Parent Dashboard - Amandla High School Locker System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  /* Minimal footer pin fix */
  html, body { height: 100%; }
  body{background:url('/amandla-lockersystem/css/images/hallway.jpg') center/cover no-repeat fixed;min-height:100vh;display:flex;flex-direction:column;padding-top:72px;color:#000}
  .glass-nav{background:rgba(255,255,255,0.2)!important;backdrop-filter:blur(12px);border-bottom:1px solid rgba(255,255,255,0.3);box-shadow:0 4px 12px rgba(0,0,0,0.15)}
  .glass-nav .nav-link{color:#000!important;font-weight:600;position:relative}
  .glass-nav .nav-link::after{content:"";position:absolute;width:0;height:2px;left:0;bottom:-4px;background:#000;transition:width .3s}
  .glass-nav .nav-link:hover::after,.glass-nav .nav-link.active::after{width:100%}
  .glass-card{background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);backdrop-filter:blur(12px);border-radius:16px;padding:2rem;margin-top:24px;box-shadow:0 6px 25px rgba(0,0,0,0.2)}
  .form-control{background:rgba(255,255,255,0.25)!important;border:1px solid rgba(255,255,255,0.3);backdrop-filter:blur(8px);color:#000}
  input[type="date"]{background:rgba(0,0,0,0.5)!important;color:#fff!important;border:1px solid rgba(255,255,255,0.3);backdrop-filter:blur(8px)}
  input[type="date"]::-webkit-calendar-picker-indicator{filter:invert(1)}
  .table{background:rgba(255,255,255,0.1)!important;backdrop-filter:blur(6px)}
  .table thead{background:rgba(255,255,255,0.2)!important}
  .btn-modern{background:linear-gradient(135deg,rgba(0,0,0,0.85),rgba(60,60,60,0.95));color:#fff!important;border:none;border-radius:999px;padding:.55rem 1.2rem;font-weight:600;letter-spacing:.3px;box-shadow:0 4px 12px rgba(0,0,0,.25);transition:all .2s}
  .btn-modern:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,.35)}
  .btn-modern-light{background:linear-gradient(135deg,rgba(255,255,255,.35),rgba(255,255,255,.6));color:#000!important;border:1px solid rgba(255,255,255,.55);border-radius:999px;padding:.5rem 1rem;font-weight:600;transition:all .2s}
  .btn-modern-light:hover{transform:translateY(-1px)}
  .badge-status{font-weight:600;padding:.3rem .6rem;border-radius:999px;border:1px solid rgba(0,0,0,.15);background:rgba(255,255,255,.35);backdrop-filter:blur(6px)}
  footer.glass-footer{background:rgba(255,255,255,0.2);backdrop-filter:blur(12px);border-top:1px solid rgba(255,255,255,0.3);box-shadow:0 -4px 12px rgba(0,0,0,0.15);color:#000;margin-top:auto;padding:.5rem;font-size:.85rem}
  .student-no-input{border-radius:999px;padding:.6rem 1rem;background:rgba(255,255,255,0.25)!important;backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.3);color:#000}
  .navbar-toggler { border-color: rgba(0,0,0,0.4); }
  .navbar-toggler-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(0,0,0,0.85)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
  }
  .status-waiting { background: rgba(255, 193, 7, .25); border-color: rgba(255,193,7,.35); }
  .status-allocated { background: rgba(40, 167, 69, .25); border-color: rgba(40,167,69,.35); }
  .status-cancelled { background: rgba(220, 53, 69, .25); border-color: rgba(220,53,69,.35); }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg glass-nav fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold text-dark" href="/amandla-lockersystem/parents/parents_dashboard.php">
      <span id="welcomeText">
        Amandla High School Parent - Welcome
          <?php echo esc($parentName) . " " . esc($parentSurname); ?>
      </span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
            data-bs-target="#navbarNav" aria-controls="navbarNav" 
            aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav align-items-center">
        <li class="nav-item"><a class="nav-link active" href="/amandla-lockersystem/index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="/amandla-lockersystem/parents/parents_logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container flex-grow-1">
  <div class="glass-card">
    <h2 class="fw-bold mb-3">Parent Dashboard</h2>

    <!-- Feedback messages -->
    <?php if (!empty($msg)) echo "<div class='alert alert-success'>".esc($msg)."</div>"; ?>
    <?php if (!empty($error)) echo "<div class='alert alert-danger'>".esc($error)."</div>"; ?>

    <div class="row">
      <!-- Register Students -->
      <div class="col-md-6">
        <h4 class="mb-3">Register students</h4>
        <form method="POST" id="registerForm" action="/amandla-lockersystem/parents/parents_dashboard.php">
          <div id="studentsWrapper">
            <div class="row g-2 mb-2">
              <div class="col-md-3">
                <input type="text" name="students[0][name]" class="form-control" placeholder="First name" required>
              </div>
              <div class="col-md-3">
                <input type="text" name="students[0][surname]" class="form-control" placeholder="Surname" required>
              </div>
              <div class="col-md-2">
                <input type="text" name="students[0][grade]" class="form-control" placeholder="Grade" required>
              </div>
              <div class="col-md-4">
                <input type="text" name="students[0][schoolno]" class="form-control" placeholder="Student no" required>
              </div>
            </div>
          </div>
          <div class="d-flex justify-content-between mt-3">
            <button type="button" id="addRow" class="btn-modern-light">+ Add student</button>
            <button type="submit" name="register_students" class="btn-modern">Register</button>
          </div>
        </form>
      </div>

      <!-- Apply for Lockers -->
      <div class="col-md-6">
        <h4 class="mb-3">Apply for lockers</h4>
        <form method="POST" id="lockerForm" action="/amandla-lockersystem/parents/parents_dashboard.php">
          <div id="lockerWrapper">
            <div class="row g-2 mb-2">
              <div class="col-md-6">
                <select name="applications[0][student_no]" class="form-control" required>
                  <option value="">-- Select Student --</option>
                  <?php
                  $stmt = $mysqli->prepare("SELECT StudentSchoolNumber, StudentName, StudentSurname FROM students WHERE ParentID=?");
                  $stmt->bind_param("i", $parentId);
                  $stmt->execute();
                  $res = $stmt->get_result();
                  while ($row = $res->fetch_assoc()) {
                      echo '<option value="'.esc($row['StudentSchoolNumber']).'">'
                           .esc($row['StudentName'].' '.$row['StudentSurname']).'</option>';
                  }
                  $stmt->close();
                  ?>
                </select>
              </div>
              <div class="col-md-6">
                <input type="date" name="applications[0][date]" class="form-control" required>
              </div>
            </div>
          </div>
          <div class="d-flex justify-content-between mt-3">
            <button type="button" id="addLockerRow" class="btn-modern-light">+ Add application</button>
            <button type="submit" name="apply_many_lockers" class="btn-modern">Apply</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Current Applications -->
    <h4 class="mt-4 mb-3">Current applications</h4>
    <table class="table table-sm align-middle">
      <thead>
        <tr>
          <th>Booking ID</th>
          <th>Student</th>
          <th>Grade</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="bookingsTableBody">
        <?php
        $stmt = $mysqli->prepare("
          SELECT b.BookingID,
                 s.StudentName, s.StudentSurname, s.StudentGrade,
                 b.Status AS BookingStatus
          FROM bookings b
          JOIN students s ON b.StudentSchoolNumber = s.StudentSchoolNumber
          WHERE b.ParentID = ?
          ORDER BY b.BookingID ASC
        ");
        if ($stmt) {
          $stmt->bind_param("i", $parentId);
          if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
              echo '<tr><td colspan="5">No current applications.</td></tr>';
            }
            while ($row = $result->fetch_assoc()) {
              $status = esc($row['BookingStatus']);
              $badgeClass = 'badge-status';
              if (strcasecmp($status, 'Allocated') === 0) $badgeClass .= ' status-allocated';
              elseif (strcasecmp($status, 'Waiting') === 0) $badgeClass .= ' status-waiting';
              elseif (strcasecmp($status, 'Cancelled') === 0) $badgeClass .= ' status-cancelled';
              echo '<tr>';
              echo '<td>'.esc($row['BookingID']).'</td>';
              echo '<td>'.esc($row['StudentName'].' '.$row['StudentSurname']).'</td>';
              echo '<td>'.esc($row['StudentGrade']).'</td>';
              echo '<td><span class="'.$badgeClass.'">'.$status.'</span></td>';
              echo '<td>
                      <form method="POST" action="/amandla-lockersystem/parents/parents_dashboard.php"
                            onsubmit="return confirm(\'Cancel this booking?\');" style="display:inline;">
                        <input type="hidden" name="booking_id" value="'.esc($row['BookingID']).'">
                        <button type="submit" name="cancel_booking" class="btn btn-sm btn-modern">Cancel</button>
                      </form>
                    </td>';
              echo '</tr>';
            }
            $result->free();
          } else {
            echo '<tr><td colspan="5">Failed to fetch bookings.</td></tr>';
          }
          $stmt->close();
        } else {
          echo '<tr><td colspan="5">Query prepare failed.</td></tr>';
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<footer class="glass-footer text-center py-3 mt-auto">
  &copy; <span id="yearSpan"></span> Amandla High School Locker System. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Footer year
  const yearSpan = document.getElementById('yearSpan');
  if (yearSpan) yearSpan.textContent = new Date().getFullYear();

  // Add student row
  (function(){
    const addStudentBtn = document.getElementById('addRow');
    const studentsWrapper = document.getElementById('studentsWrapper');
    let sIndex = 1;
    if (addStudentBtn && studentsWrapper) {
      addStudentBtn.addEventListener('click', function(){
        const row = document.createElement('div');
        row.className = 'row g-2 mb-2';
        row.innerHTML = `
          <div class="col-md-3">
            <input type="text" name="students[${sIndex}][name]" class="form-control" placeholder="First name" required>
          </div>
          <div class="col-md-3">
            <input type="text" name="students[${sIndex}][surname]" class="form-control" placeholder="Surname" required>
          </div>
          <div class="col-md-2">
            <input type="text" name="students[${sIndex}][grade]" class="form-control" placeholder="Grade" required>
          </div>
          <div class="col-md-4">
            <input type="text" name="students[${sIndex}][schoolno]" class="form-control" placeholder="Student no" required>
          </div>
        `;
        studentsWrapper.appendChild(row);
        sIndex++;
      });
    }
  })();

  // Add application rows
  (function(){
    const addBtn = document.getElementById('addLockerRow');
    const wrapper = document.getElementById('lockerWrapper');
    let index = 1; // next application index
    const firstSelect = wrapper.querySelector('select[name="applications[0][student_no]"]');
    const studentOptions = firstSelect ? firstSelect.innerHTML : '';
    if (addBtn && wrapper) {
      addBtn.addEventListener('click', function(){
        const row = document.createElement('div');
        row.className = 'row g-2 mb-2';
        row.innerHTML = `
          <div class="col-md-6">
            <select name="applications[${index}][student_no]" class="form-control" required>
              ${studentOptions}
            </select>
          </div>
          <div class="col-md-6">
            <input type="date" name="applications[${index}][date]" class="form-control" required>
          </div>
        `;
        wrapper.appendChild(row);
        index++;
      });
    }
  })();
</script>
</body>
</html>