<?php
session_start();
include 'db_connect.php';

// 1. Redirect if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}
// verify user isn't blocked
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

$username = $_SESSION['username'];
$error = "";
$success_message = "";

// --- FETCH REGISTERED USER DETAILS ---
// We use the 'register' table as confirmed by your screenshots
$userSql = "SELECT name, email, phone FROM register WHERE username = ?";
$userStmt = $conn->prepare($userSql);

if ($userStmt === false) {
    die("Database Error (User Fetch): " . $conn->error);
}

$userStmt->bind_param("s", $username);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userData = $userResult->fetch_assoc()) {
    $registered_name = $userData['name'];
    $registered_email = $userData['email'];
    $registered_phone = $userData['phone'];
} else {
    $registered_name = $username; // Fallback
    $registered_email = "";
    $registered_phone = "";
}
$userStmt->close();
// -----------------------------------------------


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 2. PREPARE DATA
    // Use read-only data from DB for reliability
    $full_name = $registered_name;
    $email = $registered_email;
    $phone = $registered_phone;

    // Get user inputs
    $relation = $_POST['relation'];
    $complaint = $_POST['complaint'];
    $complaintType = $_POST['complaintType'];

    // Format Dates for MySQL (YYYY-MM-DD)
    $complaintDate = date('Y-m-d', strtotime($_POST['cdate']));
    $incidentDate = date('Y-m-d', strtotime($_POST['idate']));

    // Logic for Victim Name
    if ($relation === 'Self') {
        $victim_name = $full_name;
    } else {
        $victim_name = isset($_POST['victim_name']) ? trim($_POST['victim_name']) : '';
        if ($relation !== 'Self' && empty($victim_name)) {
            $error = "Please enter the victim's name.";
        }
    }

    // 3. INSERTION
    if (empty($error)) {
        $sql = "INSERT INTO complaints (full_name, victim_name, email, phone, relation, complaint, complaint_date, incident_date, complaint_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }

        // Bind parameters (9 strings)
        $stmt->bind_param("sssssssss", $full_name, $victim_name, $email, $phone, $relation, $complaint, $complaintDate, $incidentDate, $complaintType);

        if ($stmt->execute()) {
            // store a simple flag or message for the success page
            $_SESSION['complaint_success'] = true;
            header("Location: complaint_success.php");
            exit();
        } else {
            // This will show you exactly why it failed if it happens again
            $error = "Database Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File a Complaint - E-Crime Sentinel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary-color: #00b4d8;
            --secondary-color: #0077b6;
            --background-dark: #0a0e17;
            --card-dark: #111827;
            --text-light: #e2e8f0;
            --text-muted: #94a3b8;
            --border-color: #1e293b;
            --success-color: #10b981;
            --error-color: #ef4444;
            --font-main: 'Inter', sans-serif;
            --font-heading: 'Poppins', sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-main);
            background-color: var(--background-dark);
            color: var(--text-light);
            line-height: 1.6;
            background-image: radial-gradient(circle at 10% 20%, rgba(0, 180, 216, 0.1) 0%, transparent 20%), radial-gradient(circle at 90% 80%, rgba(123, 44, 191, 0.1) 0%, transparent 20%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem 1rem;
        }

        .container {
            width: 100%;
            max-width: 650px;
            animation: slideUp 0.6s ease-out;
        }

        .card {
            background-color: rgba(17, 24, 39, 0.95);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .card-header {
            background: linear-gradient(to right, rgba(0, 180, 216, 0.1), rgba(0, 119, 182, 0.1));
            padding: 2.5rem 2rem 2rem;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }

        .card-header h2 {
            font-family: var(--font-heading);
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--text-light), var(--primary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.75rem;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .card-header p {
            color: var(--text-muted);
            font-size: 1.05rem;
            max-width: 80%;
            margin: 0 auto;
        }

        .card-body {
            padding: 2.5rem 2rem;
        }

        .info-banner {
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--success-color);
            padding: 1rem 1.25rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .info-banner i {
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .form-group {
            margin-bottom: 1.75rem;
        }

        .form-label {
            display: block;
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--text-light);
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            transition: color 0.3s ease;
            z-index: 1;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            background-color: rgba(30, 41, 59, 0.7);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 1rem 1rem 1rem 3rem;
            font-family: var(--font-main);
            font-size: 1rem;
            color: var(--text-light);
            transition: all 0.3s ease;
        }

        .form-textarea {
            padding-left: 1rem;
            resize: vertical;
            min-height: 120px;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: rgba(30, 41, 59, 1);
            box-shadow: 0 0 0 4px rgba(0, 180, 216, 0.15);
        }

        .form-input:focus+.input-icon {
            color: var(--primary-color);
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 10px;
            padding: 1.1rem;
            font-family: var(--font-heading);
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 20px -10px rgba(0, 180, 216, 0.5);
            position: relative;
            overflow: hidden;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px -15px rgba(0, 180, 216, 0.6);
        }

        .error-message {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--error-color);
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: shake 0.5s ease-in-out;
        }

        .form-input[readonly] {
            background-color: rgba(30, 41, 59, 0.4);
            color: rgba(226, 232, 240, 0.7);
            cursor: not-allowed;
            border-color: rgba(30, 41, 59, 0.6);
        }

        .victim-name-box {
            margin-top: 0;
            display: none;
        }

        .victim-name-box.show {
            display: block;
            animation: fadeInUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-file-shield"></i> File a Complaint</h2>
                <p>Report cybercrime incidents securely</p>
            </div>
            <div class="card-body">
                <div class="info-banner">
                    <i class="fas fa-shield-check"></i>
                    <span><strong>Your information is protected:</strong> All data is encrypted.</span>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-triangle-exclamation"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">

                    <div class="form-group">
                        <label for="name" class="form-label">Full Name (Reporter)</label>
                        <div class="input-group">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="name" name="name" class="form-input"
                                value="<?php echo htmlspecialchars($registered_name); ?>" readonly required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" id="email" name="email" class="form-input"
                                value="<?php echo htmlspecialchars($registered_email); ?>" readonly required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number</label>
                        <div class="input-group">
                            <i class="fas fa-phone input-icon"></i>
                            <input type="tel" id="phone" name="phone" class="form-input"
                                value="<?php echo htmlspecialchars($registered_phone); ?>" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="relation" class="form-label">Relation to Victim</label>
                        <div class="input-group">
                            <i class="fas fa-user-friends input-icon" style="z-index: 2;"></i>
                            <select id="relation" name="relation" class="form-select" required>
                                <option value="">-- Select Relation --</option>
                                <option value="Self">Self</option>
                                <option value="Family">Family</option>
                                <option value="Friend">Friend</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group victim-name-box" id="victimNameBox">
                        <label for="victim_name" class="form-label">Victim's Name</label>
                        <div class="input-group">
                            <i class="fas fa-user-injured input-icon"></i>
                            <input type="text" id="victim_name" name="victim_name" class="form-input"
                                placeholder="Enter victim's full name">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="complaintType" class="form-label">Complaint Type</label>
                        <div class="input-group">
                            <i class="fas fa-list-ul input-icon" style="z-index: 2;"></i>
                            <select id="complaintType" name="complaintType" class="form-select" required>
                                <option value="">-- Select Complaint Type --</option>
                                <option value="hacking">Hacking</option>
                                <option value="financialFraud">Financial Fraud</option>
                                <option value="socialMediaHarassment">Social Media Harassment</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                        <div class="form-group" style="flex: 1 1 250px;">
                            <label for="cdate" class="form-label">Complaint Date</label>
                            <div class="input-group">
                                <i class="fas fa-calendar-alt input-icon"></i>
                                <input type="date" id="cdate" name="cdate" class="form-input" required>
                            </div>
                        </div>
                        <div class="form-group" style="flex: 1 1 250px;">
                            <label for="idate" class="form-label">Incident Date</label>
                            <div class="input-group">
                                <i class="fas fa-calendar-times input-icon"></i>
                                <input type="date" id="idate" name="idate" class="form-input" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="complaint" class="form-label">Complaint Details</label>
                        <textarea id="complaint" name="complaint" class="form-textarea"
                            placeholder="Describe the incident in detail..." required></textarea>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Submit Complaint
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('cdate').value = today;
        document.getElementById('idate').setAttribute('max', today);

        const relationSelect = document.getElementById('relation');
        const victimNameBox = document.getElementById('victimNameBox');
        const victimNameInput = document.getElementById('victim_name');

        relationSelect.addEventListener('change', function () {
            if (this.value === 'Family' || this.value === 'Friend' || this.value === 'Other') {
                victimNameBox.classList.add('show');
                victimNameInput.setAttribute('required', 'required');
            } else {
                victimNameBox.classList.remove('show');
                victimNameInput.removeAttribute('required');
                victimNameInput.value = '';
            }
        });
    </script>
</body>

</html>