<?php
session_start();
include 'db_connect.php'; // Ensure this file contains the correct database connection details

// helper to determine whether a supplied username should be treated as an admin
function isAdminUsername(string $username): bool {
    // use lowercase for case‑insensitive comparison and PHP8 helper if available
    $lower = strtolower($username);
    if (function_exists('str_ends_with')) {
        return str_ends_with($lower, '.sentinel');
    }
    return substr($lower, -9) === '.sentinel';
}

// Function to hash and verify passwords
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($inputPassword, $storedHash) {
    return password_verify($inputPassword, $storedHash);
}

// Check if the login form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $isAdmin = isAdminUsername($username);
    $table = $isAdmin ? "reg_admin" : "register";

    // Prepare SQL query to prevent SQL injection
    $sql = "SELECT * FROM $table WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // if the user isn't found in the expected table and the username looked like an admin, try the other table
    if (!$user && $isAdmin) {
        $fallbackSql = "SELECT * FROM register WHERE username = ?";
        $fallbackStmt = $conn->prepare($fallbackSql);
        $fallbackStmt->bind_param("s", $username);
        $fallbackStmt->execute();
        $user = $fallbackStmt->get_result()->fetch_assoc();
        if ($user) {
            // promote the account to the admin table so future logins go through properly
            $promoteSql = "INSERT INTO reg_admin (name,dob,gender,email,phone,address,username,password) VALUES (?,?,?,?,?,?,?,?)";
            $promoteStmt = $conn->prepare($promoteSql);
            $promoteStmt->bind_param("ssssssss",
                $user['name'],
                $user['dob'],
                $user['gender'],
                $user['email'],
                $user['phone'],
                $user['address'],
                $user['username'],
                $user['password']
            );
            if ($promoteStmt->execute()) {
                $deleteSql = "DELETE FROM register WHERE username = ?";
                $deleteStmt = $conn->prepare($deleteSql);
                $deleteStmt->bind_param("s", $username);
                $deleteStmt->execute();
            }
        }
        // regardless of promotion, treat result as admin if it existed
        $table = 'reg_admin';
    }

    // Check if the user exists and the password is correct
    if ($user) {
        if (verifyPassword($password, $user['password'])) {
            // Set session variables
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_id'] = $user['id']; // Assuming 'id' is the primary key
            $_SESSION['user_role'] = $isAdmin ? 'admin' : 'user';

            // Check if the user is blocked
            if (isset($user['blocked']) && $user['blocked'] == 1) {
                echo '<script>window.location.href = "login.html?blocked=1";</script>';
                exit();
            }

            // Redirect to the appropriate dashboard
            if ($isAdmin) {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: user_dashboard.php");
            }
            exit();
        } else {
            // Password is incorrect
            echo '<script>window.location.href = "login.html?error=password";</script>';
            exit();
        }
    } else {
        // Username does not exist
        echo '<script>window.location.href = "login.html?error=username";</script>';
        exit();
    }
}

