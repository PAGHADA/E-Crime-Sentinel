<?php
session_start();
// logout blocked users immediately
if (isset($_SESSION['username'])) {
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
}
// only show this page when a complaint was just filed
if (!isset($_SESSION['complaint_success']) || $_SESSION['complaint_success'] !== true) {
    header('Location: vicpanel.php');
    exit();
}
// clear the flag so refreshing the page won't show it again
unset($_SESSION['complaint_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Submitted - E-Crime Sentinel</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .success-container {
            background: var(--card-bg);
            border-radius: 25px;
            padding: 4rem 3rem;
            width: 100%;
            max-width: 700px;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4), var(--glow);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .success-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--success), var(--accent));
        }

        .success-icon {
            font-size: 6rem;
            color: var(--success);
            margin-bottom: 2rem;
            display: inline-block;
            animation: bounce 2s ease infinite, glow 2s ease-in-out infinite alternate;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-20px);
            }
            60% {
                transform: translateY(-10px);
            }
        }

        @keyframes glow {
            from {
                filter: drop-shadow(0 0 10px rgba(76, 175, 80, 0.5));
            }
            to {
                filter: drop-shadow(0 0 20px rgba(76, 175, 80, 0.8));
            }
        }

        .success-container h1 {
            font-size: 3.5rem;
            background: linear-gradient(90deg, var(--success), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 1rem;
            font-weight: 800;
            letter-spacing: 1px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .success-container p {
            font-size: 1.4rem;
            color: var(--text);
            margin-bottom: 2.5rem;
            font-weight: 500;
            line-height: 1.5;
        }

        .success-details {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 1.5rem;
            margin: 2rem 0;
            border-left: 4px solid var(--success);
        }

        .success-details h3 {
            color: var(--accent);
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            text-align: left;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .detail-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .detail-value {
            color: var(--text);
            font-size: 1rem;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            margin-top: 2.5rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            text-decoration: none;
            min-width: 200px;
            justify-content: center;
        }
            .btn {
                padding: 0.8rem 2rem;
                border: none;
                border-radius: 50px;
                font-weight: 600;
                font-size: 1rem;
                cursor: pointer;
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
                z-index: 1;
                text-decoration: none;
                display: inline-block;
            }

        .btn-primary {
            background: linear-gradient(90deg, var(--success), #2e7d32);
            color: white;
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.4);
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
            .btn:hover::before {
                left: 100%;
            }

        .btn-primary:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(76, 175, 80, 0.6);
        }
            .btn-primary:hover {
                transform: translateY(-3px);
                box-shadow: 0 10px 20px rgba(26, 115, 232, 0.4);
            }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-5px);
        }

        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background: var(--success);
            border-radius: 50%;
            animation: confetti-fall 5s linear infinite;
        }

        @keyframes confetti-fall {
            0% {
                transform: translateY(-100px) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
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
            animation-delay: 0.3s;
        }

        .delay-2 {
            animation-delay: 0.6s;
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
            
            .success-container {
                padding: 3rem 2rem;
                margin: 1rem;
            }
            
            .success-container h1 {
                font-size: 2.5rem;
            }
            
            .success-container p {
                font-size: 1.2rem;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
            }
        }

        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(76, 175, 80, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            border: 1px solid rgba(76, 175, 80, 0.3);
            margin-top: 1rem;
        }

        .security-badge i {
            color: var(--success);
        }
    </style>
</head>
<body>
    <header>
        <h1><a href="index.html" class="site-title">E-Crime Sentinel</a></h1>
        <nav>
            <a href="user_dashboard.php">Dashboard</a>
            <a href="victimform.php">Add Complaint</a>
            <a href="vicpanel.php">View Complaints</a>
            <?php if (isset($_SESSION['username'])): ?>
            <div class="user-info">
                <i class="fas fa-user-shield"></i>
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <?php endif; ?>
        </nav>
    </header>

    <div class="main-content">
        <div class="success-container fade-in">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <h1 class="fade-in delay-1">COMPLAINT SUBMITTED!</h1>
            <p class="fade-in delay-1">Your complaint has been successfully registered with E-Crime Sentinel</p>
            
            <div class="security-badge fade-in delay-1">
                <i class="fas fa-shield-alt"></i>
                <span>Case ID: #<?php echo rand(10000, 99999); ?></span>
            </div>

            <div class="success-details fade-in delay-2">
                <h3><i class="fas fa-info-circle"></i> What Happens Next?</h3>
                <div class="details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Initial Review</span>
                        <span class="detail-value">Within 24-48 hours</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Investigation</span>
                        <span class="detail-value">Assigned to cybercrime expert</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Updates</span>
                        <span class="detail-value">Email notifications</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Confidentiality</span>
                        <span class="detail-value">100% secured</span>
                    </div>
                </div>
            </div>

            <div class="action-buttons fade-in delay-2">
                <a href="vicpanel.php" class="btn btn-primary">
                    <i class="fas fa-eye"></i> View Complaint Status
                </a>
                <a href="victimform.php" class="btn btn-secondary">
                    <i class="fas fa-plus"></i> File Another Complaint
                </a>
            </div>

            <div class="security-badge fade-in delay-2" style="margin-top: 2rem;">
                <i class="fas fa-clock"></i>
                <span>Your case will be reviewed shortly. Thank you for helping make the internet safer.</span>
            </div>
        </div>
    </div>

    <footer>
        <p style="position:fixed;left:0;bottom:0;width:100%;background:rgba(18,18,30,0.95);color:#b0b0b0;border-top:1px solid rgba(255,255,255,0.1);padding:1.2rem 0;text-align:center;font-size:1.05em;box-shadow:0 -2px 12px rgba(0,0,0,0.12);z-index:1000;margin:0;">&copy; 2025 E-Crime Sentinel. All rights reserved. | Complaint Success</p>
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

        // Confetti animation removed
            
            // Add hover effects to buttons
            document.querySelectorAll('.btn').forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                btn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });

        // Auto-redirect to complaints page after 10 seconds
        setTimeout(() => {
            // Optional: Uncomment to enable auto-redirect
            // window.location.href = 'vicpanel.php';
        }, 10000);
    </script>
</body>
</html>