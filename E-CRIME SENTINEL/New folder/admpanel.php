<?php
session_start();
include 'db_connect.php'; // DB connection

// Block direct access if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

// Handle delete request
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $sql = "DELETE FROM register WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: admpanel.php"); // refresh after delete
    exit();
}

// Fetch all victims
$sql = "SELECT id, name, email, phone, username FROM register";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - E-Crime Sentinel</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    <script src="/lenis-init.js"></script>
</head>

<body class="body-center-panel">

<main class="content-panel">
    
    <h1>Welcome, Admin <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Username</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                        echo "<td><a href='admpanel.php?delete=" . $row['id'] . "' class='delete-btn'>Delete</a></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align: center;'>No victim accounts found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Logout button form -->
    <form action="logout.php" method="post" style="text-align: center; margin-top: 20px;">
        <button type="submit" class="logout-btn">Logout</button>
    </form>

</main>

</body>
</html>
