<?php
// Set the content type to JSON, as this is an API endpoint
header('Content-Type: application/json');

include 'db_connect.php';

// Get parameters from the AJAX request
$search = $_GET['search'] ?? '';
$filter_type = $_GET['complaint_type'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Base SQL query
$sql = "SELECT complaint_id, full_name, complaint_type, created_at, status FROM complaints WHERE 1=1";
$params = [];
$types = "";

// Append search term if provided
if (!empty($search)) {
    $sql .= " AND (full_name LIKE ? OR email LIKE ?)";
    $searchTerm = "%" . $search . "%";
    array_push($params, $searchTerm, $searchTerm);
    $types .= "ss";
}

// Append complaint type filter if selected
if (!empty($filter_type)) {
    $sql .= " AND complaint_type = ?";
    $params[] = $filter_type;
    $types .= "s";
}

// Append status filter if selected
if (!empty($filter_status)) {
    $sql .= " AND status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Fetch all results into an array
$complaints = [];
while ($row = $result->fetch_assoc()) {
    $complaints[] = $row;
}

// Close the statement and connection
$stmt->close();
$conn->close();

// Encode the array into JSON and output it
echo json_encode($complaints);
?>