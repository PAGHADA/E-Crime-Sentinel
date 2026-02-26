<?php include 'header.php'; ?>
<?php
include 'db_connect.php';

// utility used in login.php as well, so keep it in one place
function isAdminUsername(string $username): bool {
    $lower = strtolower($username);
    if (function_exists('str_ends_with')) {
        return str_ends_with($lower, '.sentinel');
    }
    return substr($lower, -9) === '.sentinel';
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    // normalise username by trimming spaces
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check for admin suffix
    $is_admin = isAdminUsername($username);
    $table = $is_admin ? 'reg_admin' : 'register';

    $sql = "INSERT INTO $table (name, dob, gender, email, phone, address, username, password) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $name, $dob, $gender, $email, $phone, $address, $username, $hashed_password);

    if ($stmt->execute()) {
        header("Location: login.html");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
<?php include 'footer.php'; ?>
