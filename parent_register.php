<?php
session_start();

// DB connection
$mysqli = new mysqli('127.0.0.1', 'root', '', 'amandlahighschool_lockersystem');
if ($mysqli->connect_errno) {
    die('DB connection failed: ' . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");

$msg = "";

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parentId = $_POST['id']; // South African ID number (13 digits)
    $title    = $_POST['title'];
    $name     = $_POST['name'];
    $surname  = $_POST['surname'];
    $email    = $_POST['email'];
    $address  = $_POST['address'];
    $phone    = $_POST['phone'];
    $password = $_POST['password']; // plain text for now

    // Validate ParentID: must be exactly 13 digits
    if (!preg_match('/^\d{13}$/', $parentId)) {
        $msg = "ParentID must be exactly 13 digits long.";
    } else {
        // Insert new parent (no email uniqueness check)
        $sql = "INSERT INTO parents 
                   (ParentID, ParentTitle, ParentName, ParentSurname, ParentEmail, ParentHomeAddress, ParentPhoneNumber, Password)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $mysqli->error);
        }

        // Treat ParentID as string to avoid overflow
        $stmt->bind_param("ssssssss", $parentId, $title, $name, $surname, $email, $address, $phone, $password);

        if ($stmt->execute()) {
            $msg = "Registration successful. You may now log in.";
        } else {
            $msg = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Parent Registration</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: url('/amandla-lockersystem/css/images/hallway.jpg') center/cover no-repeat;
      margin: 0;
      padding: 0;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .glass-form {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.3);
      border-radius: 16px;
      padding: 32px;
      width: 420px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.25);
      color: black;
    }
    .glass-form h2 { text-align: center; margin-top: 0; }
    .glass-form label { font-weight: bold; display: block; margin-top: 12px; }
    .glass-form input, .glass-form select {
      width: 100%; padding: 10px; margin-top: 6px;
      border: 1px solid rgba(255,255,255,0.4);
      border-radius: 6px;
      background: rgba(255,255,255,0.6);
      color: black;
    }
    .glass-form button {
      margin-top: 20px; width: 100%; padding: 10px;
      background-color: black; color: white;
      border: none; border-radius: 6px;
      font-weight: bold; cursor: pointer;
    }
    .glass-form button:hover { opacity: 0.85; }
    .message {
      margin-top: 16px; background: rgba(0,0,0,0.75);
      color: white; padding: 10px; border-radius: 8px;
      text-align: center;
    }
  </style>
</head>
<body>
  <form class="glass-form" method="POST">
    <h2>Parent Registration</h2>

    <label for="title">Title</label>
    <select name="title" id="title" required>
      <option value="">-- Select --</option>
      <option value="Mr">Mr</option>
      <option value="Mrs">Mrs</option>
      <option value="Ms">Ms</option>
      <option value="Dr">Dr</option>
    </select>

    <label for ="id">ID</label>
    <input type="text" name="id" id="ID" required>
    <label for="name">First Name</label>
    <input type="text" name="name" id="name" required>

    <label for="surname">Surname</label>
    <input type="text" name="surname" id="surname" required>

    <label for="email">Email Address</label>
    <input type="email" name="email" id="email" required>

    <label for="address">Home Address</label>
    <input type="text" name="address" id="address" required>

    <label for="phone">Phone Number</label>
    <input type="text" name="phone" id="phone" required>

    <label for="password">Password</label>
    <input type="password" name="password" id="password" required>

    <button type="submit">Register</button>

    <p style="text-align:center; margin-top:15px;">
  Already registered? <a href="/amandla-lockersystem/parents/parents.php">Login here</a>
</p>
<p style="text-align:center; margin-top:10px;">
  <a href="/amandla-lockersystem/index.php">← Back to Home</a>
</p>
    <?php if (!empty($msg)) { echo '<div class="message">'.htmlspecialchars($msg).'</div>'; } ?>
  </form>
</body>
</html>