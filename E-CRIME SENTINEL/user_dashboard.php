<?php
session_start();
// redirect if not logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.html');
    exit();
}
// check for blocked status on each page load
include 'db_connect.php';
$checkStmt = $conn->prepare("SELECT blocked FROM register WHERE username = ?");
$checkStmt->bind_param("s", $_SESSION['username']);
$checkStmt->execute();
$result = $checkStmt->get_result();
if ($row = $result->fetch_assoc()) {
    if (isset($row['blocked']) && $row['blocked'] == 1) {
        session_unset();
        session_destroy();
        header('Location: login.html?blocked=1');
        exit();
    }
}
$checkStmt->close();

// admins should not see the user dashboard
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header('Location: admin_dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - E-Crime Sentinel</title>
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
            background: radial-gradient(circle at 10% 20%, rgba(26, 115, 232, 0.1) 0%, transparent 20%), radial-gradient(circle at 90% 80%, rgba(233, 30, 99, 0.1) 0%, transparent 20%), radial-gradient(circle at 50% 50%, rgba(0, 229, 255, 0.05) 0%, transparent 20%);
            z-index: -1;
        }

        /* Header */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0rem 5%;
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

        nav a:hover,
        nav a.active {
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

        /* Dashboard Specifics */
        .main-content {
            padding: 3rem 5%;
            min-height: calc(100vh - 160px);
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .dashboard-header h1 {
            font-size: 2.5rem;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 1rem;
        }

        .dashboard-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .welcome-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .welcome-card h2 {
            color: var(--accent);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

        .stat-card:hover {
            transform: translateY(-5px);
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

        .dashboard-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .dashboard-box {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2.5rem 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(10px);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            text-align: center;
        }

        .dashboard-box:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4), var(--glow);
        }

        .dashboard-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s ease;
        }

        .dashboard-box:hover::before {
            transform: scaleX(1);
        }

        .box-icon {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            color: var(--primary);
            transition: all 0.3s ease;
        }

        .dashboard-box:hover .box-icon {
            color: var(--accent);
            transform: scale(1.1);
        }

        .box-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text);
        }

        .box-description {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        footer {
            background: rgba(18, 18, 30, 0.9);
            padding: 2rem 5%;
            text-align: center;
            border-top: 1px solid var(--card-border);
        }

        footer p {
            color: var(--text-secondary);
        }

        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(26, 115, 232, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            border: 1px solid rgba(26, 115, 232, 0.3);
            margin-top: 1rem;
        }

        .security-badge i {
            color: var(--accent);
        }

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

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 1rem;
            }

            .dashboard-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <header>
        <h1><a href="index.html" class="site-title">E-Crime Sentinel</a></h1>
        <nav>
            <a href="index.html">Home</a>
            <a href="user_dashboard.php" class="active">Dashboard</a>
            <a href="logout.php">Logout</a>
            <div class="user-info">
                <i class="fas fa-user-shield"></i>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
        </nav>
    </header>

    <div class="main-content">
        <div class="dashboard-header fade-in">
            <h1><i class="fas fa-tachometer-alt"></i> User Dashboard</h1>
            <p>Manage your cybercrime complaints and track their status</p>
            <div class="security-badge">
                <i class="fas fa-shield-alt"></i>
                <span>Secure Session Active</span>
            </div>
        </div>

        <div class="welcome-card fade-in delay-1">
            <h2><i class="fas fa-user-circle"></i> Welcome Back!</h2>
            <p>You are logged in as <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>.
                Use the dashboard below to report new cybercrime incidents or check the status of your existing
                complaints.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card fade-in delay-1">
                <div class="stat-icon"><i class="fas fa-file-alt"></i></div><span class="stat-number">3</span><span
                    class="stat-label">Active Complaints</span>
            </div>
            <div class="stat-card fade-in delay-1">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div><span class="stat-number">7</span><span
                    class="stat-label">Resolved Cases</span>
            </div>
            <div class="stat-card fade-in delay-1">
                <div class="stat-icon"><i class="fas fa-clock"></i></div><span class="stat-number">2</span><span
                    class="stat-label">Pending Actions</span>
            </div>
            <div class="stat-card fade-in delay-1">
                <div class="stat-icon"><i class="fas fa-shield-alt"></i></div><span class="stat-number">100%</span><span
                    class="stat-label">Account Security</span>
            </div>
        </div>

        <div class="dashboard-container">
            <div class="dashboard-box fade-in delay-2" onclick="window.location.href='victimform.php'">
                <div class="box-icon"><i class="fas fa-plus-circle"></i></div>
                <h3 class="box-title">Add Complaint</h3>
                <p class="box-description">Report a new cybercrime incident.</p>
            </div>

            <div class="dashboard-box fade-in delay-2" onclick="window.location.href='vicpanel.php'">
                <div class="box-icon"><i class="fas fa-search"></i></div>
                <h3 class="box-title">View Complaints</h3>
                <p class="box-description">Check the status of your submitted complaints and view investigation
                    progress.</p>
            </div>
        </div>
    </div>

    <footer>
        <p
            style="position:fixed;left:0;bottom:0;width:100%;background:rgba(18,18,30,0.95);color:#b0b0b0;border-top:1px solid rgba(255,255,255,0.1);padding:1.2rem 0;text-align:center;font-size:1.05em;box-shadow:0 -2px 12px rgba(0,0,0,0.12);z-index:1000;margin:0;">
            &copy; 2025 E-Crime Sentinel. All rights reserved. | User Dashboard</p>
    </footer>

    <script>
        const lenis = new Lenis({ duration: 1.2, easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)), direction: 'vertical', smooth: true });
        function raf(time) { lenis.raf(time); requestAnimationFrame(raf); }
        requestAnimationFrame(raf);

        let sessionTimer = 0;
        setInterval(() => {
            sessionTimer++;
            document.querySelector('.security-badge span').textContent = `Secure Session Active (${Math.floor(sessionTimer / 60)}m ${sessionTimer % 60}s)`;
        }, 1000);
    </script>
</body>

</html>