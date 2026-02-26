<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.html');
    exit();
}
include 'db_connect.php';
// logout blocked users immediately
$checkStmt = $conn->prepare("SELECT blocked FROM register WHERE username = ?");
$checkStmt->bind_param("s", $_SESSION['username']);
$checkStmt->execute();
$checkRes = $checkStmt->get_result();
if ($r = $checkRes->fetch_assoc()) {
    if (!empty($r['blocked'])) {
        session_unset(); session_destroy();
        header('Location: login.html?blocked=1'); exit();
    }
}
$checkStmt->close();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<h2 style='color:white; text-align:center; margin-top:50px;'>Error: Invalid Complaint ID.</h2>");
}
$complaint_id = intval($_GET['id']);

// Fetch complaint
$sql = "SELECT * FROM complaints WHERE complaint_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Database Error: " . $conn->error);
}
$stmt->bind_param("i", $complaint_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<h2 style='color:white; text-align:center; margin-top:50px;'>Error: Complaint not found.</h2>");
}
$complaint = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Details - #<?php echo $complaint_id; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    <style>
        :root {
            --primary: #1a73e8; --primary-dark: #0d47a1; --secondary: #e91e63;
            --dark: #121212; --dark-light: #1e1e1e; --text: #ffffff;
            --text-secondary: #b0b0b0; --accent: #00e5ff; --success: #4caf50;
            --warning: #ff9800; --danger: #f44336; --card-bg: rgba(30, 30, 46, 0.7);
            --card-border: rgba(255, 255, 255, 0.1); --glow: 0 0 15px rgba(26, 115, 232, 0.5);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: linear-gradient(135deg, #0c0c1d 0%, #1a1a2e 50%, #16213e 100%); color: var(--text); line-height: 1.6; min-height: 100vh; }
        
        .container { max-width: 900px; margin: 4rem auto; padding: 2rem; }
        
        .details-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .header-section { text-align: center; margin-bottom: 2rem; border-bottom: 1px solid var(--card-border); padding-bottom: 1.5rem; }
        .header-section h1 { font-size: 2rem; color: var(--accent); margin-bottom: 0.5rem; }
        .case-id { font-family: monospace; font-size: 1.2rem; color: var(--text-secondary); }

        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; }
        .details-table th { text-align: left; padding: 1rem; color: var(--text-secondary); width: 30%; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .details-table td { padding: 1rem; color: var(--text); font-weight: 500; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .details-table tr:last-child th, .details-table tr:last-child td { border-bottom: none; }

        .status-box { 
            text-align: center; padding: 1.5rem; border-radius: 10px; margin-top: 2rem; 
            background: rgba(255,255,255,0.03); border: 1px solid var(--card-border);
        }
        
        .btn { padding: 0.8rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; font-size: 1rem; color: white; }
        .btn-secondary { background: transparent; color: var(--text); border: 2px solid var(--primary); }
        .btn-secondary:hover { background: var(--primary); color: white; transform: translateY(-3px); }

        footer { background: rgba(18, 18, 30, 0.9); padding: 1.5rem 0; text-align: center; position: fixed; bottom: 0; width: 100%; border-top: 1px solid var(--card-border); }
    </style>
</head>
<body>

    <div class="container">
        <div class="details-card">
            <div class="header-section">
                <h1>Complaint Details</h1>
                <div class="case-id">CASE ID: EC-<?php echo str_pad($complaint['complaint_id'], 5, '0', STR_PAD_LEFT); ?></div>
            </div>

            <table class="details-table">
                <tr><th>Status</th><td style="color: var(--accent); font-weight: bold; text-transform: uppercase;"><?php echo htmlspecialchars($complaint['status'] ?? 'Pending'); ?></td></tr>
                <tr><th>Full Name</th><td><?php echo htmlspecialchars($complaint['full_name']); ?></td></tr>
                <tr><th>Victim Name</th><td><?php echo htmlspecialchars($complaint['victim_name']); ?></td></tr>
                <tr><th>Email</th><td><?php echo htmlspecialchars($complaint['email']); ?></td></tr>
                <tr><th>Phone</th><td><?php echo htmlspecialchars($complaint['phone']); ?></td></tr>
                <tr><th>Relation</th><td><?php echo htmlspecialchars($complaint['relation']); ?></td></tr>
                <tr><th>Type</th><td><?php echo htmlspecialchars($complaint['complaint_type']); ?></td></tr>
                <tr><th>Incident Date</th><td><?php echo htmlspecialchars($complaint['incident_date']); ?></td></tr>
                <tr><th>Complaint Date</th><td><?php echo htmlspecialchars($complaint['complaint_date']); ?></td></tr>
                <tr><th>Description</th><td style="line-height: 1.6;"><?php echo nl2br(htmlspecialchars($complaint['complaint'])); ?></td></tr>
            </table>

            <?php if (strtolower(trim($complaint['status'] ?? '')) == 'denied'): ?>
                <div class="status-box" style="border-color: var(--danger); background: rgba(244, 67, 54, 0.1);">
                    <h3 style="color: var(--danger);">Complaint Denied</h3>
                    <p><strong>Reason:</strong> <?php echo htmlspecialchars($complaint['denial_reason']); ?></p>
                </div>
            <?php elseif (strtolower(trim($complaint['status'] ?? '')) == 'solved'): ?>
                <div class="status-box" style="border-color: var(--success); background: rgba(76, 175, 80, 0.1);">
                    <h3 style="color: var(--success);">Case Solved</h3>
                    <p><?php echo htmlspecialchars($complaint['solved_comment']); ?></p>
                </div>
            <?php endif; ?>

            <div style="margin-top: 2rem; text-align: center;">
                <a href="vicpanel.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>

    <footer>
        <p style="color: #b0b0b0;">&copy; 2025 E-Crime Sentinel. All rights reserved.</p>
    </footer>

</body>
</html>