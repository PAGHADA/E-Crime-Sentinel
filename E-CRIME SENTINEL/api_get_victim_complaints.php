<?php
session_start(); if(!isset($_SESSION['email'])) http_response_code(403);
include 'db_connect.php'; header('Content-Type: application/json');
$stmt=$conn->prepare("SELECT * FROM complaints WHERE email=? ORDER BY created_at DESC");
$stmt->bind_param('s',$_SESSION['email']); $stmt->execute();
echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
?>