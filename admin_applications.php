<?php
session_start();
require_once __DIR__ . '/../includes/dbconnect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';      // defines sendMail()
require_once __DIR__ . '/../includes/parentmailer.php';// calls sendMail()

if (!isset($_SESSION['admin_id'])) {
    header("Location: /amandla-lockersystem/admin/admin.php");
    exit;
}
$adminId = (int)$_SESSION['admin_id'];

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$msg = "";

/* -------------------------
   Register many students (Admin)
------------------------- */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['register_students'])) {
    if (!empty($_POST['students'])) {
        $results = [];
        foreach ($_POST['students'] as $s) {
            $schoolNo = trim($s['schoolno'] ?? '');
            $name     = trim($s['name'] ?? '');
            $surname  = trim($s['surname'] ?? '');
            $grade    = trim($s['grade'] ?? '');
            $parentId = (int)($s['parent_id'] ?? 0);

            if ($schoolNo && $name && $surname && $grade && $parentId) {
                $stmt = $mysqli->prepare("SELECT ParentID, ParentEmail FROM parents WHERE ParentID=?");
                $stmt->bind_param("i", $parentId);
                $stmt->execute();
                $parentRow = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($parentRow) {
                    $status = registerStudent($mysqli, $schoolNo, $name, $surname, $grade, $parentId);
                    if ($status === "success") {
                        $results[] = "Student {$name} {$surname} registered successfully.";
                        sendParentAcknowledgment($parentRow['ParentEmail'], $name, $surname, $grade);
                    } elseif ($status === "duplicate") {
                        $results[] = "Student {$name} {$surname} (No: {$schoolNo}) already registered.";
                    } else {
                        $results[] = "Failed to register student {$name} {$surname}.";
                    }
                } else {
                    $results[] = "Parent ID {$parentId} not found. Register parent first.";
                }
            }
        }
        $msg = implode("<br>", $results);
    }
}

/* -------------------------
   Cancel Registration (Admin)
------------------------- */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['cancel_register'])) {
    // Just clear the form or redirect back without processing
    $msg = "Registration cancelled by administrator.";
}

/* -------------------------
   Apply for lockers (Admin)
------------------------- */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['apply_many_lockers'])) {
    if (!empty($_POST['applications'])) {
        $results = [];
        foreach ($_POST['applications'] as $app) {
            $studentNo = trim($app['student_no'] ?? '');
            $date      = trim($app['date'] ?? date('Y-m-d'));

            if ($studentNo) {
                $bookingId = applyForLockerAdmin($mysqli, $studentNo, $adminId, $date);
                if ($bookingId) {
                    $results[] = "Application for student {$studentNo} processed (BookingID: {$bookingId}).";

                    $stmt = $mysqli->prepare("
                        SELECT p.ParentEmail, p.ParentName, p.ParentSurname,
                               s.StudentName, s.StudentSurname
                        FROM students s
                        JOIN parents p ON s.ParentID = p.ParentID
                        WHERE s.StudentSchoolNumber=?
                    ");
                    $stmt->bind_param("i", $studentNo);
                    $stmt->execute();
                    $row = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if ($row) {
                        sendPaymentEmail(
                            $row['ParentEmail'],
                            $row['ParentName'],
                            $row['ParentSurname'],
                            $row['StudentName'],
                            $row['StudentSurname'],
                            $bookingId
                        );
                    }
                } else {
                    $results[] = "Application for student {$studentNo} failed or deferred.";
                }
            }
        }
        $msg = implode("<br>", $results);
    }
}

/* -------------------------
   Cancel booking (Admin)
   -> delete from bookings only, leave waitinglist intact
------------------------- */
if (isset($_POST['cancel'])) {
    $waitingId = trim($_POST['waiting_id']);
    $bookingId = trim($_POST['booking_id']);

    $mysqli->begin_transaction();
    try {
        // Fetch parent + student details BEFORE deleting booking
        $stmt = $mysqli->prepare("
            SELECT p.ParentName, p.ParentSurname, p.ParentEmail,
                   s.StudentName, s.StudentSurname, s.StudentGrade
            FROM waitinglist w
            JOIN bookings b ON TRIM(w.BookingID) = TRIM(b.BookingID)
            JOIN students s ON b.StudentSchoolNumber = s.StudentSchoolNumber
            JOIN parents p  ON b.ParentID = p.ParentID
            WHERE w.WaitingListID = ?
        ");
        $stmt->bind_param("s", $waitingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $details = $result->fetch_assoc();
        $stmt->close();

        // Remove booking record
        $stmt = $mysqli->prepare("DELETE FROM bookings WHERE BookingID=?");
        $stmt->bind_param("s", $bookingId);
        $stmt->execute();
        $stmt->close();

        // Update waitinglist status
        $stmt = $mysqli->prepare("UPDATE waitinglist SET Status='Cancelled' WHERE WaitingListID=?");
        $stmt->bind_param("s", $waitingId);
        $stmt->execute();
        $stmt->close();

        $mysqli->commit();

        // Build cancellation email
        $subject = "Locker Application Cancelled - Amandla High School";
        $htmlBody = "Dear {$details['ParentName']} {$details['ParentSurname']},<br><br>
                     Your child <strong>{$details['StudentName']} {$details['StudentSurname']}</strong> (Grade {$details['StudentGrade']})
                     has had their locker application <strong>cancelled</strong> by the administrator.<br><br>
                     Regards,<br>Amandla High School Admin";

        $altBody  = "Dear {$details['ParentName']} {$details['ParentSurname']},\n\n"
                  . "Your child {$details['StudentName']} {$details['StudentSurname']} (Grade {$details['StudentGrade']}) "
                  . "has had their locker application cancelled by the administrator.\n\n"
                  . "Regards,\nAmandla High School Admin";

        // Send email
        sendMail($details['ParentEmail'], $subject, $htmlBody, $altBody);

        $success = "Application cancelled successfully and parent notified.";
    } catch (Exception $e) {
        $mysqli->rollback();
        $error = "Cancellation failed: " . $e->getMessage();
    }
}

/* -------------------------
   Fetch parents for dropdown
------------------------- */
$parents = [];
$res = $mysqli->query("SELECT ParentID, ParentName, ParentSurname FROM parents ORDER BY ParentName");
if ($res) $parents = $res->fetch_all(MYSQLI_ASSOC);

/* -------------------------
   Fetch eligible students (no active booking yet)
------------------------- */
$eligibleStudents = [];
$stmt = $mysqli->prepare("
    SELECT s.StudentSchoolNumber, s.StudentName, s.StudentSurname, s.StudentGrade,
           p.ParentName, p.ParentSurname
    FROM students s
    JOIN parents p ON s.ParentID = p.ParentID
    LEFT JOIN bookings b
        ON b.StudentSchoolNumber = s.StudentSchoolNumber
       AND b.Status IN ('Waiting','Allocated')
    WHERE b.BookingID IS NULL
    ORDER BY p.ParentName, s.StudentSurname, s.StudentName
");
$stmt->execute();
$res = $stmt->get_result();
if ($res) $eligibleStudents = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* -------------------------
   Cancel a locker application
------------------------- */
if (isset($_POST['cancel'])) {
    $waitingId = trim($_POST['waiting_id']);
    $bookingId = trim($_POST['booking_id']);

    $mysqli->begin_transaction();
    try {
        // Remove booking record
        $stmt = $mysqli->prepare("DELETE FROM bookings WHERE BookingID=?");
        $stmt->bind_param("s", $bookingId);
        $stmt->execute();
        $stmt->close();

        // Update waitinglist status
        $stmt = $mysqli->prepare("UPDATE waitinglist SET Status='Cancelled' WHERE WaitingListID=?");
        $stmt->bind_param("s", $waitingId);
        $stmt->execute();
        $stmt->close();

        // Fetch parent + student details for email
        $stmt = $mysqli->prepare("
            SELECT p.ParentName, p.ParentSurname, p.ParentEmail,
                   s.StudentName, s.StudentSurname, s.StudentGrade
            FROM waitinglist w
            JOIN bookings b ON TRIM(w.BookingID) = TRIM(b.BookingID)
            JOIN students s ON b.StudentSchoolNumber = s.StudentSchoolNumber
            JOIN parents p  ON b.ParentID = p.ParentID
            WHERE w.WaitingListID = ?
        ");
        $stmt->bind_param("s", $waitingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $details = $result->fetch_assoc();
        $stmt->close();

        $mysqli->commit();

        // Build cancellation email
        $subject = "Locker Application Cancelled - Amandla High School";
        $htmlBody = "Dear {$details['ParentName']} {$details['ParentSurname']},<br><br>
                     Your child <strong>{$details['StudentName']} {$details['StudentSurname']}</strong> (Grade {$details['StudentGrade']})
                     has had their locker application <strong>cancelled</strong> by the administrator.<br><br>
                     Regards,<br>Amandla High School Admin";

        $altBody  = "Dear {$details['ParentName']} {$details['ParentSurname']},\n\n"
                  . "Your child {$details['StudentName']} {$details['StudentSurname']} (Grade {$details['StudentGrade']}) "
                  . "has had their locker application cancelled by the administrator.\n\n"
                  . "Regards,\nAmandla High School Admin";

        // Send email
        sendMail($details['ParentEmail'], $subject, $htmlBody, $altBody);

        $success = "Application cancelled successfully and parent notified.";
    } catch (Exception $e) {
        $mysqli->rollback();
        $error = "Cancellation failed: " . $e->getMessage();
    }
}


/* -------------------------
   Fetch waitinglist entries
------------------------- */
$waitingRows = [];
$stmt = $mysqli->prepare("
    SELECT w.WaitingListID, b.BookingID,
           s.StudentName, s.StudentSurname, s.StudentGrade,
           p.ParentName, p.ParentEmail,
           w.Status, w.RequestedOn
    FROM waitinglist w
    JOIN bookings b ON w.BookingID = b.BookingID
    JOIN students s ON b.StudentSchoolNumber = s.StudentSchoolNumber
    JOIN parents p ON b.ParentID = p.ParentID
    WHERE w.Status = 'Waiting'
    ORDER BY w.WaitingListID ASC
");
$stmt->execute();
$res = $stmt->get_result();
if ($res) $waitingRows = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Applications - Amandla High School Locker System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body{background:url('/amandla-lockersystem/css/images/hallway.jpg') center/cover no-repeat fixed;
   min-height:100vh; display:flex; flex-direction:column; padding-top:72px; color:#000}
  .glass-nav{background:rgba(255,255,255,0.2)!important;
    backdrop-filter:blur(12px); border-bottom:1px solid rgba(255,255,255,0.3);
    box-shadow:0 4px 12px rgba(0,0,0,0.15)}
  .glass-nav .nav-link{color:#000!important; font-weight:600; position:relative}
  .glass-nav .nav-link::after{content:""; position:absolute; width:0; height:2px; left:0; bottom:-4px;
    background:#000; transition:width .3s}
  .glass-nav .nav-link:hover::after,.glass-nav .nav-link.active::after{width:100%}
  .glass-card{background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.3);
    backdrop-filter:blur(12px); border-radius:16px; padding:2rem; margin-top:24px;
    box-shadow:0 6px 25px rgba(0,0,0,0.2)}
  .form-control{background:rgba(255,255,255,0.25)!important; border:1px solid rgba(255,255,255,0.3);
    backdrop-filter:blur(8px); color:#000}
  input[type="date"]{background:rgba(0,0,0,0.5)!important; color:#fff!important;
    border:1px solid rgba(255,255,255,0.3); backdrop-filter:blur(8px)}
  input[type="date"]::-webkit-calendar-picker-indicator{filter:invert(1)}
  .table{background:rgba(255,255,255,0.1)!important; backdrop-filter:blur(6px)}
  .table thead{background:rgba(255,255,255,0.2)!important}
  .btn-modern{background:linear-gradient(135deg,rgba(0,0,0,0.85),rgba(60,60,60,0.95));
    color:#fff!important; border:none; border-radius:999px; padding:.55rem 1.2rem;
    font-weight:600; letter-spacing:.3px; box-shadow:0 4px 12px rgba(0,0,0,.25); transition:all .2s}
  .btn-modern:hover{transform:translateY(-2px); box-shadow:0 6px 16px rgba(0,0,0,.35)}
  .btn-modern-light{background:linear-gradient(135deg,rgba(255,255,255,.35),rgba(255,255,255,.6));
    color:#000!important; border:1px solid rgba(255,255,255,.55); border-radius:999px;
    padding:.5rem 1rem; font-weight:600; transition:all .2s}
  .btn-modern-light:hover{transform:translateY(-1px)}
  .badge-status{font-weight:600; padding:.3rem .6rem; border-radius:999px;
    border:1px solid rgba(0,0,0,.15); background:rgba(255,255,255,.35); backdrop-filter:blur(6px)}
  footer.glass-footer{background:rgba(255,255,255,0.2); backdrop-filter:blur(12px);
    border-top:1px solid rgba(255,255,255,0.3); box-shadow:0 -4px 12px rgba(0,0,0,0.15);
    color:#000; margin-top:auto; padding:.5rem; font-size:.85rem}
  .group-header{background:rgba(255,255,255,0.25)!important; backdrop-filter:blur(6px);
    font-weight:700;}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg glass-nav fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold text-dark" href="admin_dashboard.php">
      Amandla High School Administrator
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav align-items-center">
        <li class="nav-item"><a class="nav-link active" href="/amandla-lockersystem/admin/admin_portal.php">Administrators Portal</a></li>
        <li class="nav-item"><a class="nav-link" href="/amandla-lockersystem/admin/admin_logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container flex-grow-1">
  <div class="glass-card">
    <h2 class="fw-bold mb-3">Admin Applications</h2>
    <?php if (!empty($msg)): ?><div class="alert alert-info"><?= esc($msg) ?></div><?php endif; ?>

    <div class="row">
      <!-- Register students (on behalf of parents) -->
      <div class="col-md-6">
        <h4 class="mb-3">Register students (on behalf of parents)</h4>
        <form method="POST" id="registerForm">
          <div id="studentsWrapper">
            <div class="row g-2 mb-2">
              <div class="col-md-2">
                <input type="text" name="students[0][schoolno]" class="form-control" placeholder="Student no" required>
              </div>
              <div class="col-md-3">
                <input type="text" name="students[0][name]" class="form-control" placeholder="First name" required>
              </div>
              <div class="col-md-3">
                <input type="text" name="students[0][surname]" class="form-control" placeholder="Surname" required>
              </div>
              <div class="col-md-2">
                <input type="text" name="students[0][grade]" class="form-control" placeholder="Grade" required>
              </div>
              <div class="col-md-2">
                <select name="students[0][parent_id]" class="form-control" required>
                  <option value="">Select parent...</option>
                  <?php foreach ($parents as $p): ?>
                    <option value="<?= esc($p['ParentID']) ?>">
                      <?= esc($p['ParentName']) ?> <?= esc($p['ParentSurname']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          <div class="d-flex justify-content-between mt-3">
            <button type="button" id="addRow" class="btn-modern-light">+ Add student</button>
            <button type="submit" name="register_students" class="btn-modern">Register</button>
            <button type="submit" name="cancel_register" class="btn-modern">Cancel</button>
          </div>
        </form>
      </div>

      <!-- Apply for lockers (on behalf of students) -->
      <div class="col-md-6">
        <h4 class="mb-3">Apply for lockers (on behalf of students)</h4>
        <form method="POST" id="lockerForm">
          <div id="lockerWrapper">
            <div class="row g-2 mb-2">
              <div class="col-md-6">
                <select name="applications[0][student_no]" class="form-control" required>
                  <option value="">Select student...</option>
                  <?php foreach ($eligibleStudents as $s): ?>
                    <option value="<?= esc($s['StudentSchoolNumber']) ?>">
                      <?= esc($s['StudentName']) ?> <?= esc($s['StudentSurname']) ?>
                      (Parent: <?= esc($s['ParentName'].' '.$s['ParentSurname']) ?>, Grade: <?= esc($s['StudentGrade']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <input type="date" name="applications[0][date]" class="form-control" value="<?= date('Y-m-d') ?>" required>
              </div>
            </div>
          </div>
          <div class="d-flex justify-content-between mt-3">
            <button type="button" id="addLockerRow" class="btn-modern-light">+ Add application</button>
            <div>
            <button type="submit" name="apply_many_lockers" class="btn-modern">Apply</button>
            <button type="submit" name="cancel_apply" class="btn-modern">Cancel</button>
            </div>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Current applications (grouped by parent) -->
    <h4 class="mt-4 mb-3">Current applications (grouped by parent)</h4>
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
      <tbody>
      <?php if (!empty($groupedBookings)): ?>
        <?php foreach ($groupedBookings as $parentName => $children): ?>
          <tr>
            <td colspan="5" class="group-header"><?= esc($parentName) ?></td>
          </tr>
          <?php foreach ($children as $b): ?>
            <tr>
              <td><?= esc($b['BookingID']) ?></td>
              <td><?= esc($b['StudentName']) ?> <?= esc($b['StudentSurname']) ?></td>
              <td><?= esc($b['StudentGrade']) ?></td>
              <td>
                <?php
                  switch ($b['Status']) {
                    case 'Payment Uploaded':
                    case 'Pending':
                    case 'Paid':      $label = 'Applied for a locker'; break;
                    case 'Waiting':   $label = 'Waiting'; break;
                    case 'Allocated': $label = 'Locker allocated'; break;
                    case 'Cancelled': $label = 'Cancelled'; break;
                    default:          $label = esc($b['Status']);
                  }
                ?>
                <span class="badge-status"><?= esc($label) ?></span>
              </td>
              <td>
                <form method="post" action="">
                  <input type="hidden" name="booking_id" value="<?= esc($b['BookingID']) ?>">
                  <input type="text" name="reason" class="form-control form-control-sm mb-2" placeholder="Reason for cancellation" required>
                  <button type="submit" name="cancel_booking" class="btn-modern-light">Cancel Booking</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="5" class="text-center">No applications yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<footer class="glass-footer text-center">
  &copy; <?= date('Y') ?> Amandla High School Locker System. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Dynamic rows: register students (clone parent dropdown)
let studentCount = 1;
document.getElementById('addRow')?.addEventListener('click', () => {
  const wrapper = document.getElementById('studentsWrapper');
  const firstParentSelect = wrapper.querySelector('select[name="students[0][parent_id]"]');

  const row = document.createElement('div');
  row.className = 'row g-2 mb-2';

  const colSchool = document.createElement('div');
  colSchool.className = 'col-md-2';
  colSchool.innerHTML = `<input type="text" name="students[${studentCount}][schoolno]" class="form-control" placeholder="Student no" required>`;

  const colName = document.createElement('div');
  colName.className = 'col-md-3';
  colName.innerHTML = `<input type="text" name="students[${studentCount}][name]" class="form-control" placeholder="First name" required>`;

  const colSurname = document.createElement('div');
  colSurname.className = 'col-md-3';
  colSurname.innerHTML = `<input type="text" name="students[${studentCount}][surname]" class="form-control" placeholder="Surname" required>`;

  const colGrade = document.createElement('div');
  colGrade.className = 'col-md-2';
  colGrade.innerHTML = `<input type="text" name="students[${studentCount}][grade]" class="form-control" placeholder="Grade" required>`;

  const colParent = document.createElement('div');
  colParent.className = 'col-md-2';
  const parentSelect = document.createElement('select');
  parentSelect.className = 'form-control';
  parentSelect.name = `students[${studentCount}][parent_id]`;
  parentSelect.required = true;
  parentSelect.innerHTML = firstParentSelect.innerHTML; // clone options
  colParent.appendChild(parentSelect);

  row.appendChild(colSchool);
  row.appendChild(colName);
  row.appendChild(colSurname);
  row.appendChild(colGrade);
  row.appendChild(colParent);
  wrapper.appendChild(row);
  studentCount++;
});

// Dynamic rows: locker applications (clone student dropdown)
let lockerCount = 1;
document.getElementById('addLockerRow')?.addEventListener('click', () => {
  const wrapper = document.getElementById('lockerWrapper');
  const today = new Date().toISOString().split('T')[0];
  const firstStudentSelect = wrapper.querySelector('select[name="applications[0][student_no]"]');

  const row = document.createElement('div');
  row.className = 'row g-2 mb-2';

  const colStudent = document.createElement('div');
  colStudent.className = 'col-md-6';
  const studentSelect = document.createElement('select');
  studentSelect.className = 'form-control';
  studentSelect.name = `applications[${lockerCount}][student_no]`;
  studentSelect.required = true;
  studentSelect.innerHTML = firstStudentSelect.innerHTML; // clone options
  colStudent.appendChild(studentSelect);

  const colDate = document.createElement('div');
  colDate.className = 'col-md-6';
  const dateInput = document.createElement('input');
  dateInput.type = 'date';
  dateInput.className = 'form-control';
  dateInput.name = `applications[${lockerCount}][date]`;
  dateInput.required = true;
  dateInput.value = today;
  colDate.appendChild(dateInput);

  row.appendChild(colStudent);
  row.appendChild(colDate);
  wrapper.appendChild(row);
  lockerCount++;
});
</script>
</body>
</html>