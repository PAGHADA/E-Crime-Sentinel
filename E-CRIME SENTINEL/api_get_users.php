<?php
session_start();
// only admins may fetch user list
if (!isset($_SESSION['username']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    exit();
}
include 'db_connect.php';
header('Content-Type: application/json');
$rows=$conn->query("SELECT id,name,email,phone,username FROM register ORDER BY id DESC");
echo json_encode($rows->fetch_all(MYSQLI_ASSOC));
?>