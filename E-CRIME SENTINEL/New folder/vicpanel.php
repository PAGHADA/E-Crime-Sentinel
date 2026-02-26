<?php
session_start();
include 'db_connect.php';

// Make sure victim is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$victim_email = $_SESSION['email'];

// Fetch victim's complaint details
$sql = "SELECT * FROM complaints WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $victim_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $complaint = $result->fetch_assoc();
} else {
    echo "No complaint found for this user.";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Victim Panel</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
    <h3>Your Complaint Details</h3>

    <table>
        <tr><th>Full Name</th><td><?php echo htmlspecialchars($complaint['full_name']); ?></td></tr>
        <tr><th>Email</th><td><?php echo htmlspecialchars($complaint['email']); ?></td></tr>
        <tr><th>Phone</th><td><?php echo htmlspecialchars($complaint['phone']); ?></td></tr>
        <tr><th>Relation</th><td><?php echo htmlspecialchars($complaint['relation']); ?></td></tr>
        <tr><th>Complaint</th><td><?php echo nl2br(htmlspecialchars($complaint['complaint'])); ?></td></tr>
        <tr><th>Complaint Date</th><td><?php echo htmlspecialchars($complaint['complaint_date']); ?></td></tr>
        <tr><th>Incident Date</th><td><?php echo htmlspecialchars($complaint['incident_date']); ?></td></tr>
        <tr><th>Complaint Type</th><td><?php echo htmlspecialchars($complaint['complaint_type']); ?></td></tr>
        <tr><th>Created At</th><td><?php echo htmlspecialchars($complaint['created_at']); ?></td></tr>
    </table>

    <form action="logout.php" method="post">
        <button type="submit">Logout</button>
    </form>
</body>
</html>
