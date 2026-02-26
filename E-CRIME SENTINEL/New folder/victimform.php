<?php
session_start();
include 'db_connect.php'; // Your database connection

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// Handle form submission
$success = false;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Remove user_id since it's not part of your table
    $full_name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $relation = $_POST['relation'];
    $complaint = $_POST['complaint'];
    $complaintDate = $_POST['cdate'];
    $incidentDate = $_POST['idate'];
    $complaintType = $_POST['complaintType'];

    $sql = "INSERT INTO complaints (full_name, email, phone, relation, complaint, complaint_date, incident_date, complaint_type) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssssssss", $full_name, $email, $phone, $relation, $complaint, $complaintDate, $incidentDate, $complaintType);

    if ($stmt->execute()) {
        $success = true;
    } else {
        $error = "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Cyber Crime Complaint Form</title>
    <link rel="stylesheet" href="style.css" />
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    <script src="/lenis-init.js"></script>
</head>

<body>
    <header class="site-header" style="position:fixed;top:0;left:0;width:100%;z-index:1000;background-color:var(--color-header-bg);border-bottom:1px solid var(--color-border);">
        <div style="display:flex;align-items:center;height:60px;padding:0 2rem;">
            <h1 style="margin:0;font-size:1.3rem;"><a href="../index.html" class="site-title" style="color:var(--color-text-primary);text-decoration:none;">E-Crime Sentinel</a></h1>
            <nav style="display:flex;align-items:center;gap:2rem;margin-left:auto;">
                <a href="../index.html" style="color:var(--color-text-secondary);font-size:1rem;padding:4px 0;">Home</a>
                <a href="../login.html" style="color:var(--color-text-secondary);font-size:1rem;padding:4px 0;">Login</a>
                <a href="../register.html" style="color:var(--color-text-secondary);font-size:1rem;padding:4px 0;">Register</a>
            </nav>
        </div>
    </header>
    <div style="height:60px;min-width:100%;"></div> <!-- Spacer for fixed header, ensure not hidden -->

    <?php if ($success): ?>
        <div class="form-container">
            <p>✅ Complaint submitted successfully!</p>
        </div>
    <?php elseif (!empty($error)): ?>
        <div class="form-container">
            <p style="color: red;">❌ <?= $error ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" class="form-container">
        <h2>Complaint Form</h2>

        <label for="name">Full Name:</label>
        <input type="text" id="name" name="name" placeholder="Full Name" required />

        <label for="email">Email Address:</label>
        <input type="email" id="email" name="email" placeholder="Email Address" required />

        <label for="phone">Phone Number:</label>
        <input type="tel" id="phone" name="phone" placeholder="10-digit phone number" pattern="[0-9]{10}" required />

        <label for="relation">Relationship With Victim:</label>
        <select id="relation" name="relation" required>
            <option value="">-- Select Relation --</option>
            <option value="Father">Father</option>
            <option value="Mother">Mother</option>
            <option value="Brother">Brother</option>
            <option value="Sister">Sister</option>
            <option value="Spouse">Spouse</option>
            <option value="Self">Self</option>
        </select>

        <label for="complaint">Complaint:</label>
        <textarea id="complaint" name="complaint" placeholder="Write complaint details here" required></textarea>

        <label for="cdate">Complaint Date:</label>
        <input type="date" id="cdate" name="cdate" required />

        <label for="idate">Incident Date:</label>
        <input type="date" id="idate" name="idate" required />

        <label for="complaintType">Type of Complaint:</label>
        <select id="complaintType" name="complaintType" required>
            <option value="">-- Select Complaint Type --</option>
            <option value="hacking">Hacking</option>
            <option value="financialFraud">Online Financial Fraud</option>
            <option value="socialMediaHarassment">Social Media Harassment</option>
        </select>

        <button type="submit">Register</button>
    </form>
</body>
</html>
