<?php
// Database connection settings
$host = "localhost";        // Database host (usually localhost)
$user = "root";             // Database username
$pass = "";                 // Database password (default empty for XAMPP)
$db = "ecrime_sentinal";    // Database name (replace with your DB name)

// Create connection
$conn = new mysqli($host, $user, $pass, $db, 3300);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form only if submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get data from form
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $relation = $_POST['relation'];
    $complaint = $_POST['complaint'];
    $complaintDate = $_POST['cdate'];
    $incidentDate = $_POST['idate'];
    $complaintType = $_POST['complaintType'];

    // Insert query
    $sql = "INSERT INTO complaints (full_name, email, phone, relation, complaint, complaint_date, incident_date, complaint_type) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    // Prepare statement
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $name, $email, $phone, $relation, $complaint, $complaintDate, $incidentDate, $complaintType);

    if ($stmt->execute()) {
        echo "<h2 style='color:lime;'>✅ Complaint Registered Successfully!</h2>";
    } else {
        echo "<h2 style='color:red;'>❌ Error: " . $stmt->error . "</h2>";
    }

    // Close
    $stmt->close();
    $conn->close();
}
?>