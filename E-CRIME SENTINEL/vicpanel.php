<?php
session_start();
// Make sure user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.html');
    exit();
}
// verify account hasn't been blocked
include 'db_connect.php';
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

include 'db_connect.php';

$username = $_SESSION['username'];

// --- FETCH REGISTERED DETAILS FOR THE PANEL HEADER ---
// FIX: Changed table name from 'users' to 'register' to match your database
$userSql = "SELECT email, phone FROM register WHERE username = ?";
$userStmt = $conn->prepare($userSql);

if ($userStmt === false) {
    die("Database Error (User Fetch): " . $conn->error);
}

$userStmt->bind_param("s", $username);
$userStmt->execute();
$userResult = $userStmt->get_result();
$userData = $userResult->fetch_assoc();

// Use fetched email, or fallback to an empty string if somehow missing
$user_email = $userData['email'] ?? '';
$user_phone = $userData['phone'] ?? 'Not provided';
// ---------------------------------------------------------


// --- FETCH COMPLAINTS ---
// This looks for complaints where the Full Name OR Email matches the logged-in user
$sql = "SELECT * FROM complaints WHERE full_name = ? OR email = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Database Error (Complaint Fetch): " . $conn->error);
}

// Bind both parameters (string, string)
$stmt->bind_param("ss", $username, $user_email);
$stmt->execute();
$result = $stmt->get_result();
// ------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Complaints - E-Crime Sentinel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    <style>
        :root { --primary: #1a73e8; --primary-dark: #0d47a1; --secondary: #e91e63; --dark: #121212; --dark-light: #1e1e1e; --text: #ffffff; --text-secondary: #b0b0b0; --accent: #00e5ff; --success: #4caf50; --warning: #ff9800; --danger: #f44336; --card-bg: rgba(30, 30, 46, 0.7); --card-border: rgba(255, 255, 255, 0.1); --glow: 0 0 15px rgba(26, 115, 232, 0.5); }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: linear-gradient(135deg, #0c0c1d 0%, #1a1a2e 50%, #16213e 100%); color: var(--text); line-height: 1.6; min-height: 100vh; overflow-x: hidden; }
        body::before { content: ''; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at 10% 20%, rgba(26, 115, 232, 0.1) 0%, transparent 20%), radial-gradient(circle at 90% 80%, rgba(233, 30, 99, 0.1) 0%, transparent 20%); z-index: -1; }
        header { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem 5%; background: rgba(18, 18, 30, 0.9); backdrop-filter: blur(10px); border-bottom: 1px solid var(--card-border); position: sticky; top: 0; z-index: 1000; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
        .site-title { font-size: 1.8rem; font-weight: 700; color: var(--text); text-decoration: none; background: linear-gradient(90deg, var(--primary), var(--accent)); -webkit-background-clip: text; background-clip: text; color: transparent; }
        nav { display: flex; gap: 2rem; align-items: center; }
        nav a { color: var(--text); text-decoration: none; font-weight: 500; padding: 0.5rem 1rem; border-radius: 6px; transition: all 0.3s ease; }
        nav a:hover, nav a.active { background: rgba(255, 255, 255, 0.1); color: var(--accent); }
        .user-info { display: flex; align-items: center; gap: 1rem; padding: 0.5rem 1rem; background: rgba(255, 255, 255, 0.05); border-radius: 8px; }
        .user-info i { color: var(--accent); }
        .main-content { padding: 2rem 5%; min-height: calc(100vh - 160px); }
        .page-header { text-align: center; margin-bottom: 3rem; }
        .page-header h1 { font-size: 2.8rem; background: linear-gradient(90deg, var(--primary), var(--accent)); -webkit-background-clip: text; background-clip: text; color: transparent; margin-bottom: 1rem; }
        .page-header p { color: var(--text-secondary); font-size: 1.2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 3rem; }
        .stat-card { background: var(--card-bg); border-radius: 15px; padding: 1.5rem; text-align: center; box-shadow: 0 8px 25px rgba(0,0,0,0.2); border: 1px solid var(--card-border); position: relative; overflow: hidden; transition: transform 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, var(--primary), var(--accent)); }
        .stat-icon { font-size: 2.5rem; margin-bottom: 1rem; color: var(--primary); }
        .stat-number { font-size: 2.2rem; font-weight: 700; color: var(--accent); display: block; }
        .stat-label { color: var(--text-secondary); font-size: 1rem; }
        
        .info-card { background: var(--card-bg); border-radius: 20px; padding: 2.5rem; margin-bottom: 3rem; box-shadow: 0 10px 30px rgba(0,0,0,0.3); border: 1px solid var(--card-border); backdrop-filter: blur(10px); position: relative; overflow: hidden; }
        .info-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 5px; background: linear-gradient(90deg, var(--primary), var(--accent)); }
        .info-card h2 { color: var(--accent); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.8rem; font-size: 1.5rem; }
        .user-details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; }
        .detail-item { display: flex; flex-direction: column; gap: 0.5rem; padding: 1rem; background: rgba(255, 255, 255, 0.05); border-radius: 10px; border-left: 3px solid var(--accent); }
        .detail-label { color: var(--text-secondary); font-size: 0.9rem; font-weight: 500; }
        .detail-value { color: var(--text); font-size: 1.1rem; font-weight: 600; }

        .complaints-section { margin-top: 3rem; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .section-header h2 { color: var(--accent); font-size: 1.8rem; display: flex; align-items: center; gap: 0.8rem; }
        .complaints-table-container { background: var(--card-bg); border-radius: 15px; overflow: hidden; box-shadow: 0 8px 25px rgba(0,0,0,0.2); border: 1px solid var(--card-border); overflow-x: auto; }
        .complaints-table { width: 100%; border-collapse: collapse; min-width: 800px; }
        .complaints-table th { background: rgba(26, 115, 232, 0.2); padding: 1.2rem 1rem; text-align: left; color: var(--accent); border-bottom: 1px solid var(--card-border); }
        .complaints-table td { padding: 1rem; border-bottom: 1px solid var(--card-border); color: var(--text); }
        .complaints-table tr:hover { background: rgba(255, 255, 255, 0.05); }
        .status-badge { display: inline-block; padding: 0.3rem 0.8rem; border-radius: 50px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: rgba(255, 152, 0, 0.2); color: var(--warning); border: 1px solid rgba(255, 152, 0, 0.3); }
        .status-solved { background: rgba(76, 175, 80, 0.2); color: var(--success); border: 1px solid rgba(76, 175, 80, 0.3); }
        .status-denied { background: rgba(244, 67, 54, 0.2); color: var(--danger); border: 1px solid rgba(244, 67, 54, 0.3); }
        .complaint-type-badge { display: inline-block; padding: 0.3rem 0.8rem; border-radius: 6px; background: rgba(255, 255, 255, 0.1); font-size: 0.8rem; }
        .btn { padding: 0.8rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; font-size: 1rem; color: white; }
        .btn-primary { background: linear-gradient(90deg, var(--primary), var(--primary-dark)); color: white; box-shadow: var(--glow); }
        .btn-secondary { background: transparent; color: var(--text); border: 2px solid var(--primary); }
        .btn-sm { padding: 0.4rem 0.8rem; font-size: 0.85rem; }
        .btn-primary:hover { transform: translateY(-3px); }
        .btn-secondary:hover { background: var(--primary); color: white; transform: translateY(-3px); }
        .empty-state { text-align: center; padding: 4rem 2rem; color: var(--text-secondary); }
        .empty-state i { font-size: 4rem; margin-bottom: 1rem; opacity: 0.5; }
        footer { background: rgba(18, 18, 30, 0.9); padding: 2rem 5%; text-align: center; border-top: 1px solid var(--card-border); margin-top: auto; }
        .fade-in { animation: fadeInUp 0.8s ease forwards; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <header>
        <h1><a href="index.html" class="site-title">E-Crime Sentinel</a></h1>
        <nav>
            <a href="user_dashboard.php">Dashboard</a>
            <a href="victimform.php">Add Complaint</a>
            <a href="vicpanel.php" class="active">My Complaints</a>
            <div class="user-info">
                <i class="fas fa-user-shield"></i>
                <span><?php echo htmlspecialchars($username); ?></span>
            </div>
        </nav>
    </header>

    <div class="main-content">
        <div class="page-header fade-in">
            <h1><i class="fas fa-clipboard-list"></i> My Complaints</h1>
            <p>Track and manage all your submitted cybercrime complaints</p>
        </div>

        <?php
        $total_complaints = $result->num_rows;
        $pending_count = 0; $resolved_count = 0; $investigating_count = 0;
        while ($row = $result->fetch_assoc()) {
            $s = strtolower(trim($row['status']));
            if($s == 'pending') $pending_count++;
            elseif($s == 'solved' || $s == 'resolved') $resolved_count++;
            elseif($s == 'investigating') $investigating_count++;
        }
        $result->data_seek(0);
        ?>

        <div class="stats-grid fade-in">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                <span class="stat-number"><?php echo $total_complaints; ?></span>
                <span class="stat-label">Total Complaints</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <span class="stat-number"><?php echo $pending_count; ?></span>
                <span class="stat-label">Pending</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <span class="stat-number"><?php echo $resolved_count; ?></span>
                <span class="stat-label">Resolved</span>
            </div>
        </div>

        <div class="info-card fade-in">
             <h2><i class="fas fa-user-circle"></i> Reporter Profile (Registered Details)</h2>
             <div class="user-details-grid">
                 <div class="detail-item">
                     <span class="detail-label">Username</span>
                     <span class="detail-value"><?php echo htmlspecialchars($username); ?></span>
                 </div>
                 <div class="detail-item">
                     <span class="detail-label">Email Address</span>
                     <span class="detail-value"><?php echo htmlspecialchars($user_email); ?></span>
                 </div>
                 <div class="detail-item">
                     <span class="detail-label">Phone Number</span>
                     <span class="detail-value"><?php echo htmlspecialchars($user_phone); ?></span>
                 </div>
             </div>
         </div>


        <div class="complaints-section fade-in">
            <div class="section-header">
                <h2><i class="fas fa-list-ul"></i> Complaint History</h2>
                <a href="victimform.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Complaint</a>
            </div>

            <div class="complaints-table-container">
                <?php if ($total_complaints > 0): ?>
                    <table class="complaints-table">
                        <thead>
                            <tr>
                                <th>Case ID</th>
                                <th>Type</th>
                                <th>Victim</th>
                                <th>Incident Date</th>
                                <th>Complaint Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): 
                                $status = strtolower(trim($row['status']));
                                $status = empty($status) ? 'pending' : $status;
                                $statusClass = 'status-' . $status;
                                $caseId = 'EC-' . str_pad($row['complaint_id'], 5, '0', STR_PAD_LEFT);
                            ?>
                            <tr>
                                <td><strong><?php echo $caseId; ?></strong></td>
                                <td><span class="complaint-type-badge"><?php echo htmlspecialchars($row['complaint_type']); ?></span></td>
                                <td>
                                    <?php echo (isset($row['relation']) && $row['relation'] === 'Self') ? 'Self' : htmlspecialchars($row['victim_name'] ?? 'Self'); ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['incident_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['complaint_date']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view_complaint_details.php?id=<?php echo $row['complaint_id']; ?>" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No Complaints Found</h3>
                        <p>You haven't submitted any complaints yet.</p>
                        <a href="victimform.php" class="btn btn-primary" style="margin-top: 1rem;">
                            <i class="fas fa-plus"></i> File Your First Complaint
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="margin-top: 2rem; text-align: right;" class="fade-in">
             <a href="user_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 E-Crime Sentinel. All rights reserved.</p>
    </footer>

    <script>
        const lenis = new Lenis({ duration: 1.2, easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)), smooth: true });
        function raf(time) { lenis.raf(time); requestAnimationFrame(raf); }
        requestAnimationFrame(raf);
    </script>
</body>
</html>