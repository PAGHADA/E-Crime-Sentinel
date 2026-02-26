<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.html');
    exit();
}
include 'db_connect.php';
// block check
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

$user_email = $_SESSION['email'];
$sql = "SELECT * FROM complaints WHERE email = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
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
        :root {
            --primary: #1a73e8;
            --primary-dark: #0d47a1;
            --secondary: #e91e63;
            --dark: #121212;
            --dark-light: #1e1e1e;
            --text: #ffffff;
            --text-secondary: #b0b0b0;
            --accent: #00e5ff;
            --success: #4caf50;
            --warning: #ff9800;
            --danger: #f44336;
            --card-bg: rgba(30, 30, 46, 0.7);
            --card-border: rgba(255, 255, 255, 0.1);
            --glow: 0 0 15px rgba(26, 115, 232, 0.5);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0c0c1d 0%, #1a1a2e 50%, #16213e 100%);
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 10% 20%, rgba(26, 115, 232, 0.1) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(233, 30, 99, 0.1) 0%, transparent 20%),
                radial-gradient(circle at 50% 50%, rgba(0, 229, 255, 0.05) 0%, transparent 20%);
            z-index: -1;
        }

        /* Header Styles */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 5%;
            background: rgba(18, 18, 30, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--card-border);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .site-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text);
            text-decoration: none;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            position: relative;
            padding: 0.5rem 0;
        }

        .site-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            transition: width 0.3s ease;
        }

        .site-title:hover::after {
            width: 100%;
        }

        nav {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        nav a {
            color: var(--text);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s ease;
        }

        nav a:hover::before {
            left: 100%;
        }

        nav a:hover, nav a.active {
            background: rgba(255, 255, 255, 0.1);
            color: var(--accent);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
        }

        .user-info i {
            color: var(--accent);
        }

        /* Main Content */
        .main-content {
            padding: 2rem 5%;
            min-height: calc(100vh - 160px);
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-header h1 {
            font-size: 2.8rem;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 1rem;
        }

        .page-header p {
            color: var(--text-secondary);
            font-size: 1.2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            border: 1px solid var(--card-border);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.3);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: block;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .info-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .info-card h2 {
            color: var(--accent);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-size: 1.8rem;
        }

        .info-card h2 i {
            font-size: 1.5rem;
        }

        .user-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border-left: 4px solid var(--accent);
        }

        .detail-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .detail-value {
            color: var(--text);
            font-size: 1.1rem;
            font-weight: 600;
        }

        .complaints-section {
            margin-top: 3rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .section-header h2 {
            color: var(--accent);
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .complaints-table-container {
            background: var(--card-bg);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            border: 1px solid var(--card-border);
        }

        .complaints-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
        }

        .complaints-table th {
            background: rgba(26, 115, 232, 0.2);
            padding: 1.2rem 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--accent);
            border-bottom: 1px solid var(--card-border);
            font-size: 0.95rem;
        }

        .complaints-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--card-border);
            color: var(--text);
            font-size: 0.9rem;
        }

        .complaints-table tr:last-child td {
            border-bottom: none;
        }

        .complaints-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateX(5px);
            transition: all 0.3s ease;
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: rgba(255, 152, 0, 0.2);
            color: var(--warning);
            border: 1px solid rgba(255, 152, 0, 0.3);
        }

        .status-investigating {
            background: rgba(26, 115, 232, 0.2);
            color: var(--primary);
            border: 1px solid rgba(26, 115, 232, 0.3);
        }

        .status-resolved {
            background: rgba(76, 175, 80, 0.2);
            color: var(--success);
            border: 1px solid rgba(76, 175, 80, 0.3);
        }

        .status-denied {
            background: rgba(244, 67, 54, 0.2);
            color: var(--danger);
            border: 1px solid rgba(244, 67, 54, 0.3);
        }

        .denial-reason {
            display: block;
            font-size: 0.8rem;
            color: var(--danger);
            margin-top: 0.3rem;
            font-style: italic;
        }

        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: var(--glow);
        }

        .btn-secondary {
            background: transparent;
            color: var(--text);
            border: 2px solid var(--primary);
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
            z-index: -1;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(26, 115, 232, 0.4);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
        }

        /* Footer */
        footer {
            background: rgba(18, 18, 30, 0.9);
            padding: 2rem 5%;
            text-align: center;
            border-top: 1px solid var(--card-border);
        }

        footer p {
            color: var(--text-secondary);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeInUp 0.8s ease forwards;
        }

        .delay-1 {
            animation-delay: 0.2s;
        }

        .delay-2 {
            animation-delay: 0.4s;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 1rem;
            }
            
            nav {
                gap: 1rem;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .page-header h1 {
                font-size: 2.2rem;
            }
            
            .complaints-table-container {
                overflow-x: auto;
            }
            
            .complaints-table {
                min-width: 1000px;
            }
            
            .user-details-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--text-secondary);
            opacity: 0.5;
        }

        .complaint-type-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.1);
            color: var(--text);
            font-size: 0.8rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <header>
        <h1><a href="index.html" class="site-title">E-Crime Sentinel</a></h1>
        <nav>
            <a href="user_dashboard.php">Dashboard</a>
            <a href="victimform.php">Add Complaint</a>
            <a href="view_complaint.php" class="active">My Complaints</a>
            <div class="user-info">
                <i class="fas fa-user-shield"></i>
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
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
        $pending_count = 0;
        $resolved_count = 0;
        $investigating_count = 0;
        $denied_count = 0;
        
        // Count statuses
        $result->data_seek(0);
        while ($row = $result->fetch_assoc()) {
            $status = isset($row['status']) ? $row['status'] : 'pending';
            switch ($status) {
                case 'pending': $pending_count++; break;
                case 'resolved': $resolved_count++; break;
                case 'investigating': $investigating_count++; break;
                case 'denied': $denied_count++; break;
                default: $pending_count++;
            }
        }
        $result->data_seek(0);
        ?>

        <div class="stats-grid fade-in">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <span class="stat-number"><?php echo $total_complaints; ?></span>
                <span class="stat-label">Total Complaints</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <span class="stat-number"><?php echo $pending_count; ?></span>
                <span class="stat-label">Pending</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-search"></i>
                </div>
                <span class="stat-number"><?php echo $investigating_count; ?></span>
                <span class="stat-label">Investigating</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <span class="stat-number"><?php echo $resolved_count; ?></span>
                <span class="stat-label">Resolved</span>
            </div>
        </div>

        <div class="info-card fade-in delay-1">
            <h2><i class="fas fa-user-circle"></i> User Profile</h2>
            <div class="user-details-grid">
                <div class="detail-item">
                    <span class="detail-label">Full Name</span>
                    <span class="detail-value"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Email Address</span>
                    <span class="detail-value"><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Phone Number</span>
                    <span class="detail-value"><?php echo isset($_SESSION['phone']) ? htmlspecialchars($_SESSION['phone']) : 'Not provided'; ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Member Since</span>
                    <span class="detail-value"><?php echo date('M Y'); ?></span>
                </div>
            </div>
        </div>

        <div class="complaints-section fade-in delay-2">
            <div class="section-header">
                <h2><i class="fas fa-list-ul"></i> Complaint History</h2>
                <div class="action-buttons" style="margin: 0;">
                    <a href="victimform.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Complaint
                    </a>
                </div>
            </div>

            <div class="complaints-table-container">
                <?php if ($total_complaints > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="complaints-table">
                            <thead>
                                <tr>
                                    <th>Case ID</th>
                                    <th>Type</th>
                                    <th>Victim</th>
                                    <th>Incident Date</th>
                                    <th>Complaint Date</th>
                                    <th>Status</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $result->data_seek(0);
                                while ($row = $result->fetch_assoc()): 
                                    $status = isset($row['status']) ? $row['status'] : 'pending';
                                    $statusClass = 'status-' . $status;
                                       $caseId = 'EC-' . str_pad($row['complaint_id'], 5, '0', STR_PAD_LEFT);
                                ?>
                                <tr>
                                    <td><strong><?php echo $caseId; ?></strong></td>
                                    <td>
                                        <span class="complaint-type-badge">
                                            <?php echo isset($row['complaint_type']) ? htmlspecialchars($row['complaint_type']) : 'General'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        if (isset($row['relation']) && $row['relation'] === 'Self') {
                                            echo 'Self';
                                        } else {
                                            echo isset($row['victim_name']) && !empty($row['victim_name']) ? 
                                                htmlspecialchars($row['victim_name']) : 'Self';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo isset($row['incident_date']) ? htmlspecialchars($row['incident_date']) : '-'; ?></td>
                                    <td><?php echo isset($row['complaint_date']) ? htmlspecialchars($row['complaint_date']) : '-'; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                        <?php if ($status === 'denied' && !empty($row['denial_reason'])): ?>
                                            <span class="denial-reason">
                                                <i class="fas fa-info-circle"></i>
                                                <?php echo htmlspecialchars($row['denial_reason']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                           <button class="btn btn-secondary" onclick='showComplaintModal(<?php echo json_encode($row); ?>)'
                                                style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
    <!-- Complaint Details Modal (single instance) -->
    <div id="complaintModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center;">
        <div class="modal-content" style="background:#222; color:#fff; padding:2rem; border-radius:16px; max-width:500px; width:90%; position:relative;">
            <span class="close" onclick="closeComplaintModal()" style="position:absolute; top:16px; right:24px; font-size:2rem; cursor:pointer;">&times;</span>
            <h2 style="margin-bottom:1rem;"><i class="fas fa-file-alt"></i> Complaint Details</h2>
            <div id="modalDetails"></div>
        </div>
    </div>
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

        <div class="action-buttons fade-in delay-2">
            <a href="user_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <a href="victimform.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> File New Complaint
            </a>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 E-Crime Sentinel. All rights reserved.</p>
    </footer>

    <script>
        // Initialize Lenis for smooth scrolling
        const lenis = new Lenis({
            duration: 1.2,
            easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
            direction: 'vertical',
            gestureDirection: 'vertical',
            smooth: true,
            smoothTouch: false,
            touchMultiplier: 2,
        });

        function raf(time) {
            lenis.raf(time);
            requestAnimationFrame(raf);
        }
        requestAnimationFrame(raf);

        // Function to view complaint details
        function showComplaintModal(complaint) {
            var modal = document.getElementById('complaintModal');
            var details = `
                <table style="width:100%; border-collapse:collapse;">
                    <tr><td style='font-weight:bold; padding:6px;'>Case ID:</td><td style='padding:6px;'>EC-${String(complaint.complaint_id).padStart(5,'0')}</td></tr>
                    <tr><td style='font-weight:bold; padding:6px;'>Type:</td><td style='padding:6px;'>${complaint.complaint_type}</td></tr>
                    <tr><td style='font-weight:bold; padding:6px;'>Victim:</td><td style='padding:6px;'>${complaint.relation === 'Self' ? 'Self' : (complaint.victim_name || 'Self')}</td></tr>
                    <tr><td style='font-weight:bold; padding:6px;'>Incident Date:</td><td style='padding:6px;'>${complaint.incident_date}</td></tr>
                    <tr><td style='font-weight:bold; padding:6px;'>Complaint Date:</td><td style='padding:6px;'>${complaint.complaint_date}</td></tr>
                    <tr><td style='font-weight:bold; padding:6px;'>Status:</td><td style='padding:6px;'>${complaint.status ? complaint.status : 'Pending'}</td></tr>
                    <tr><td style='font-weight:bold; padding:6px;'>Full Name:</td><td style='padding:6px;'>${complaint.full_name}</td></tr>
                    <tr><td style='font-weight:bold; padding:6px;'>Email:</td><td style='padding:6px;'>${complaint.email}</td></tr>
                    <tr><td style='font-weight:bold; padding:6px;'>Phone:</td><td style='padding:6px;'>${complaint.phone}</td></tr>
                    <tr><td style='font-weight:bold; padding:6px;'>Relation:</td><td style='padding:6px;'>${complaint.relation}</td></tr>
                    <tr><td style='font-weight:bold; padding:6px; vertical-align:top;'>Complaint Details:</td><td style='padding:6px; word-break:break-word;'>${complaint.complaint}</td></tr>
                </table>
            `;
            document.getElementById('modalDetails').innerHTML = details;
            modal.style.display = 'flex';
        }

        function closeComplaintModal() {
            document.getElementById('complaintModal').style.display = 'none';
        }

        // Add hover effects to table rows
        document.querySelectorAll('.complaints-table tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(5px)';
                this.style.transition = 'transform 0.3s ease';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });
    </script>
</body>
</html>