<?php
// Set the content type to JSON
header('Content-Type: application/json');
session_start();

// Security check: Only allow access if an admin is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

include 'db_connect.php';

// Get the POST data sent from the JavaScript
$data = json_decode(file_get_contents('php://input'), true);

$complaint_id = $data['complaint_id'] ?? null;
$new_status = $data['new_status'] ?? null;

// --- Validation ---
if (!$complaint_id || !$new_status) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit();
}

$allowed_statuses = ['pending', 'solved', 'denied'];
if (!in_array($new_status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit();
}

// --- NEW: Check if the status can be changed ---
$checkSql = "SELECT status FROM complaints WHERE complaint_id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("i", $complaint_id);
$checkStmt->execute();
$result = $checkStmt->get_result();
$current_complaint = $result->fetch_assoc();

if ($current_complaint['status'] !== 'pending') {
    echo json_encode(['success' => false, 'message' => 'This complaint has been finalized and its status cannot be changed again.']);
    $checkStmt->close();
    exit();
}
$checkStmt->close();


// --- Update the Database ---
$updateSql = "UPDATE complaints SET status = ? WHERE complaint_id = ?";
$stmt = $conn->prepare($updateSql);
$stmt->bind_param("si", $new_status, $complaint_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Status updated successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error or status was unchanged.']);
}

$stmt->close();
$conn->close();

?>