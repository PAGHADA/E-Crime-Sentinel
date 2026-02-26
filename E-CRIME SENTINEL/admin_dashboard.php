<?php
session_start();
include 'db_connect.php';

// only administrators may access this page
if (!isset($_SESSION['username'])) {
    header('Location: login.html');
    exit();
}
// if the role is missing or incorrect, force a fresh login rather than bouncing to user dashboard
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.html');
    exit();
}

// --- 1. DETERMINE CURRENT VIEW ---
$current_view = isset($_GET['show']) ? $_GET['show'] : 'pending';

// --- 2. HANDLE FORM SUBMISSIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Update Complaint Status
    if (isset($_POST['complaint_id']) && isset($_POST['status'])) {
        $complaint_id = $_POST['complaint_id'];
        $status = $_POST['status'];

        $denial_reason = isset($_POST['denial_reason']) ? $_POST['denial_reason'] : NULL;
        $solved_comment = isset($_POST['solved_comment']) ? $_POST['solved_comment'] : NULL;

        if ($status === 'denied' && $denial_reason) {
            $updateSql = "UPDATE complaints SET status = ?, denial_reason = ? WHERE complaint_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ssi", $status, $denial_reason, $complaint_id);
        } elseif ($status === 'solved' && $solved_comment) {
            $updateSql = "UPDATE complaints SET status = ?, solved_comment = ? WHERE complaint_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ssi", $status, $solved_comment, $complaint_id);
        } else {
            $updateSql = "UPDATE complaints SET status = ?, denial_reason = NULL, solved_comment = NULL WHERE complaint_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("si", $status, $complaint_id);
        }

        if (isset($updateStmt))
            $updateStmt->execute();

        header("Location: admin_dashboard.php?show=" . $current_view);
        exit();
    }

    // Block User
    if (isset($_POST['block_email'])) {
        $block_email = $_POST['block_email'];
        $blockSql = "UPDATE register SET blocked = 1 WHERE email = ?";
        $blockStmt = $conn->prepare($blockSql);
        $blockStmt->bind_param("s", $block_email);
        $blockStmt->execute();
        // also mark any existing complaints
        $complaintBlock = "UPDATE complaints SET status = 'denied', denial_reason='User blocked by admin' WHERE email = ?";
        $complaintStmt = $conn->prepare($complaintBlock);
        $complaintStmt->bind_param("s", $block_email);
        $complaintStmt->execute();
        header("Location: admin_dashboard.php?show=users");
        exit();
    }
}

// --- 3. FETCH DATA ---
$dataResult = null;

if ($current_view === 'users') {
    $userSql = "SELECT * FROM register";
    $dataResult = $conn->query($userSql);
} else {
    $complaintSql = "SELECT * FROM complaints ORDER BY created_at DESC";
    $dataResult = $conn->query($complaintSql);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admindash.css">
    <style>
        /* --- STYLES --- */

        body {
            overflow-x: hidden;
        }

        .content-panel {
            width: 98%;
            margin: 0 auto;
            padding: 20px 0;
        }

        .table-wrapper {
            width: 100%;
            background: rgba(26, 41, 64, 0.5);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            overflow: hidden;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            /* Strict columns */
        }

        .data-table th {
            background: rgba(30, 30, 40, 0.9);
            color: #00e5ff;
            text-align: left;
            padding: 15px 8px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            font-size: 0.9rem;
        }

        .data-table td {
            padding: 15px 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: #eee;
            vertical-align: top;
            font-size: 0.9rem;
            word-wrap: break-word;
            white-space: normal;
        }

        /* Left Spacing */
        .data-table th:first-child,
        .data-table td:first-child {
            padding-left: 20px;
        }

        /* --- OPTIMIZED COLUMN WIDTHS (Total 100%) --- */
        .data-table th:nth-child(1) {
            width: 12%;
        }

        /* Name */
        .data-table th:nth-child(2) {
            width: 11%;
        }

        /* Victim */
        .data-table th:nth-child(3) {
            width: 8%;
        }

        /* Relation */
        .data-table th:nth-child(4) {
            width: 22%;
        }

        /* Complaint Details */
        .data-table th:nth-child(5) {
            width: 11%;
        }

        /* Dates */
        .data-table th:nth-child(6) {
            width: 10%;
        }

        /* Type */
        .data-table th:nth-child(7) {
            width: 8%;
        }

        /* Status */
        /* The 8th column changes based on view (Action OR Resolution) */
        .data-table th:nth-child(8) {
            width: 18%;
        }

        /* Status Badges */
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.75rem;
            display: inline-block;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.15);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        .status-solved {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .status-denied {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        /* Widgets */
        .info-boxes {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .info-box {
            cursor: pointer;
            background: rgba(30, 30, 40, 0.8);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid #444;
            transition: 0.3s;
        }

        .info-box:hover {
            transform: translateY(-3px);
            border-color: #666;
        }

        .info-box.active-pending {
            background: rgba(255, 193, 7, 0.1);
            border-color: #ffc107;
        }

        .info-box.active-users {
            background: rgba(0, 229, 255, 0.1);
            border-color: #00e5ff;
        }

        .info-box.active-solved {
            background: rgba(40, 167, 69, 0.1);
            border-color: #28a745;
        }

        .info-box.active-denied {
            background: rgba(220, 53, 69, 0.1);
            border-color: #dc3545;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: white;
            letter-spacing: 1px;
        }

        h2 {
            color: #00e5ff;
            margin-bottom: 15px;
            font-weight: 500;
            text-align: center;
            letter-spacing: 0.5px;
        }

        @media (max-width: 1000px) {
            .info-boxes {
                grid-template-columns: repeat(2, 1fr);
            }

            .table-wrapper {
                overflow-x: auto;
            }

            .data-table {
                min-width: 900px;
            }
        }
    </style>
</head>

<body class="body-center-panel">
    <?php $hideNav = true;
    include 'header.php'; ?>

    <div style="height:80px;"></div>

    <main class="content-panel">
        <h1>Admin Dashboard</h1>

        <div class="info-boxes">
            <div class="info-box <?php echo $current_view == 'users' ? 'active-users' : ''; ?>"
                onclick="window.location.href='admin_dashboard.php?show=users'">
                <div class="box-icon" style="font-size: 2.2rem; margin-bottom: 15px; color: #00e5ff;"><i
                        class="fas fa-users"></i></div>
                <h3 style="color:white; margin: 5px 0;">All User Data</h3>
                <p style="color:#aaa; font-size: 0.9rem;">View registered users</p>
            </div>

            <div class="info-box <?php echo $current_view == 'pending' ? 'active-pending' : ''; ?>"
                onclick="window.location.href='admin_dashboard.php?show=pending'">
                <div class="box-icon" style="font-size: 2.2rem; margin-bottom: 15px; color: #ffc107;"><i
                        class="fas fa-clock"></i></div>
                <h3 style="color:#ffc107; margin: 5px 0;">Pending Complaints</h3>
                <p style="color:#aaa; font-size: 0.9rem;">View new submissions</p>
            </div>

            <div class="info-box <?php echo $current_view == 'solved' ? 'active-solved' : ''; ?>"
                onclick="window.location.href='admin_dashboard.php?show=solved'">
                <div class="box-icon" style="font-size: 2.2rem; margin-bottom: 15px; color: #28a745;"><i
                        class="fas fa-check-circle"></i></div>
                <h3 style="color:white; margin: 5px 0;">Solved</h3>
                <p style="color:#aaa; font-size: 0.9rem;">View solved cases</p>
            </div>

            <div class="info-box <?php echo $current_view == 'denied' ? 'active-denied' : ''; ?>"
                onclick="window.location.href='admin_dashboard.php?show=denied'">
                <div class="box-icon" style="font-size: 2.2rem; margin-bottom: 15px; color: #ff6b6b;"><i
                        class="fas fa-times-circle"></i></div>
                <h3 style="color:#ff6b6b; margin: 5px 0;">Denied</h3>
                <p style="color:#ff6b6b; font-size: 0.9rem;">View denied cases</p>
            </div>
        </div>

        <?php if ($current_view === 'users'): ?>
            <div id="usersSection">
                <h2>Registered Users</h2>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Account Status</th>
                                <th style="text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($dataResult)
                                $dataResult->data_seek(0);
                            while ($user = $dataResult->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                                    <td>@<?php echo htmlspecialchars($user['username']); ?></td>
                                    <td>
                                        <?php echo (isset($user['blocked']) && $user['blocked'] == 1) ? '<span style="color:#e74c3c;font-weight:bold;">Blocked</span>' : '<span style="color:#28a745;">Active</span>'; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if (!isset($user['blocked']) || $user['blocked'] == 0): ?>
                                        <button
                                            class="block-btn"
                                            style="background:#ff3333; color:#fff; border:none; padding:8px 16px; border-radius:4px; font-size:0.8rem; cursor: pointer;"
                                            onclick="openBlockModal(this, '<?php echo htmlspecialchars($user['email']); ?>')">
                                            Block
                                        </button>
                                        <?php else: ?>
                                        &mdash;
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php else:
            $sectionTitle = "Pending Complaints";
            if ($current_view === 'solved')
                $sectionTitle = "Solved Complaints";
            if ($current_view === 'denied')
                $sectionTitle = "Denied Complaints";
            ?>
            <div id="complaintsSection">
                <h2><?php echo $sectionTitle; ?></h2>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Victim</th>
                                <th>Relation</th>
                                <th>Complaint Details</th>
                                <th>Dates (C/I)</th>
                                <th>Type</th>
                                <th>Status</th>
                                <?php if ($current_view === 'pending'): ?>
                                    <th>Action</th>
                                <?php elseif ($current_view === 'solved'): ?>
                                    <th>Resolution Details</th>
                                <?php elseif ($current_view === 'denied'): ?>
                                    <th>Denial Reason</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($dataResult)
                                $dataResult->data_seek(0);
                            $rowCount = 0;

                            while ($complaint = $dataResult->fetch_assoc()):
                                $status = isset($complaint['status']) ? strtolower($complaint['status']) : 'pending';
                                if (empty($status))
                                    $status = 'pending';

                                // FILTERING
                                if ($current_view === 'pending' && $status !== 'pending')
                                    continue;
                                if ($current_view === 'solved' && $status !== 'solved')
                                    continue;
                                if ($current_view === 'denied' && $status !== 'denied')
                                    continue;

                                $rowCount++;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($complaint['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($complaint['victim_name']); ?></td>
                                    <td><?php echo htmlspecialchars($complaint['relation']); ?></td>

                                    <td class="complaint-desc">
                                        <?php echo nl2br(htmlspecialchars(substr($complaint['complaint'], 0, 200))); ?>
                                        <?php if (strlen($complaint['complaint']) > 200)
                                            echo "..."; ?>
                                    </td>

                                    <td style="font-size: 0.85rem; white-space: nowrap;">
                                        <?php echo $complaint['complaint_date']; ?><br>
                                        <span style="color:#aaa;"><?php echo $complaint['incident_date']; ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($complaint['complaint_type']); ?></td>

                                    <td>
                                        <span class="status-badge status-<?php echo $status; ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </td>

                                    <?php if ($current_view === 'pending'): ?>
                                        <td style="min-width: 140px;">
                                            <div style="display:flex; gap:5px; align-items: center;">
                                                <select id="status-select-<?php echo $complaint['complaint_id']; ?>"
                                                    onchange="handleStatusChange(this, '<?php echo $complaint['complaint_id']; ?>')"
                                                    style="padding: 4px; border-radius: 4px; border: 1px solid #555; background: #222; color: white; font-size: 0.85rem;">
                                                    <option value="pending" selected>Pending</option>
                                                    <option value="solved">Solved</option>
                                                    <option value="denied">Denied</option>
                                                </select>
                                            </div>
                                        </td>
                                    <?php elseif ($current_view === 'solved'): ?>
                                        <td style="color: #28a745; font-style: italic; font-size: 0.9rem; line-height: 1.4;">
                                            <?php echo htmlspecialchars($complaint['solved_comment']); ?>
                                        </td>
                                    <?php elseif ($current_view === 'denied'): ?>
                                        <td style="color: #ff6b6b; font-style: italic; font-size: 0.9rem; line-height: 1.4;">
                                            <?php echo htmlspecialchars($complaint['denial_reason']); ?>
                                        </td>
                                    <?php endif; ?>

                                </tr>
                            <?php endwhile; ?>

                            <?php if ($rowCount === 0): ?>
                                <tr>
                                    <td colspan="8" style="text-align:center; padding: 40px; color: #aaa;">
                                        <i class="fas fa-folder-open"
                                            style="font-size: 2rem; display:block; margin-bottom:10px; opacity:0.5;"></i>
                                        No items found in this list.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <div id="blockModal"
        style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.7); z-index:9999; justify-content:center; align-items:center;">
        <div
            style="background:#1a2940; padding:32px; border-radius:16px; width:350px; text-align:center; border: 1px solid rgba(255,255,255,0.1);">
            <h2 style="color:#fff; margin-bottom:15px;">Block User</h2>
            <p style="color:#ccc; margin-bottom:25px;">Are you sure you want to block this user?</p>
            <form method="post" action="admin_dashboard.php?show=users">
                <input type="hidden" name="block_email" id="blockModalEmail">
                <button type="submit"
                    style="background:#ff3333; color:white; padding:10px 20px; border:none; border-radius:5px; margin-right:10px; cursor: pointer;">Yes,
                    Block</button>
                <button type="button" onclick="document.getElementById('blockModal').style.display='none'"
                    style="background:#444; color:white; padding:10px 20px; border:none; border-radius:5px; cursor: pointer;">Cancel</button>
            </form>
        </div>
    </div>

    <div id="denialModal"
        style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.7); z-index:9999; justify-content:center; align-items:center;">
        <div
            style="background:#1a2940; padding:32px; border-radius:16px; width:400px; text-align:center; border: 1px solid rgba(255,255,255,0.1);">
            <h2 style="color:#fff; margin-bottom:15px;">Denial Reason</h2>
            <form method="post" action="admin_dashboard.php?show=<?php echo $current_view; ?>">
                <input type="hidden" name="complaint_id" id="denialComplaintId">
                <input type="hidden" name="status" value="denied">

                <select id="denialModalReason" name="denial_reason"
                    style="width:100%; padding:10px; margin-bottom:15px; border-radius:5px; background: #222; color: white; border: 1px solid #555;"
                    onchange="checkOther(this)">
                    <option value="">-- Select Reason --</option>
                    <option value="Insufficient Evidence">Insufficient Evidence</option>
                    <option value="Not a Cybercrime">Not a Cybercrime</option>
                    <option value="Duplicate Complaint">Duplicate Complaint</option>
                    <option value="Out of Jurisdiction">Out of Jurisdiction</option>
                    <option value="Other">Other</option>
                </select>
                <input type="text" id="denialModalOtherReason" name="denial_reason_custom"
                    placeholder="Enter custom reason..."
                    style="width:100%; padding:10px; margin-bottom:20px; border-radius:5px; background: #222; color: white; border: 1px solid #555; display:none;">

                <button type="submit"
                    style="background:#dc3545; color:white; padding:10px 20px; border:none; border-radius:5px; margin-right:10px; cursor: pointer;">Confirm
                    Denial</button>
                <button type="button" onclick="closeModals()"
                    style="background:#444; color:white; padding:10px 20px; border:none; border-radius:5px; cursor: pointer;">Cancel</button>
            </form>
        </div>
    </div>

    <div id="solvedModal"
        style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.7); z-index:9999; justify-content:center; align-items:center;">
        <div
            style="background:#1a2940; padding:32px; border-radius:16px; width:450px; text-align:center; border: 1px solid rgba(255,255,255,0.1);">
            <h2 style="color:#28a745; margin-bottom:15px;">Case Solved</h2>
            <p style="color:#ccc; margin-bottom:15px;">Resolution details for the victim:</p>

            <form method="post" action="admin_dashboard.php?show=<?php echo $current_view; ?>">
                <input type="hidden" name="complaint_id" id="solvedComplaintId">
                <input type="hidden" name="status" value="solved">

                <textarea name="solved_comment" rows="4"
                    style="width:100%; padding:10px; margin-bottom:20px; border-radius:5px; background: #222; color: white; border: 1px solid #555; resize: none;"
                    placeholder="e.g. The suspect was apprehended..." required></textarea>

                <button type="submit"
                    style="background:#28a745; color:white; padding:10px 20px; border:none; border-radius:5px; margin-right:10px; cursor: pointer;">Confirm
                    Solved</button>
                <button type="button" onclick="closeModals()"
                    style="background:#444; color:white; padding:10px 20px; border:none; border-radius:5px; cursor: pointer;">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function openBlockModal(btn, email) {
            // disable the clicked button to prevent double-submission
            if (btn) {
                btn.disabled = true;
                btn.style.opacity = 0.5;
                btn.style.cursor = 'not-allowed';
            }
            document.getElementById('blockModalEmail').value = email;
            document.getElementById('blockModal').style.display = 'flex';
        }

        let currentSelectElement = null;

        function handleStatusChange(select, id) {
            currentSelectElement = select;
            if (select.value === 'denied') {
                document.getElementById('denialComplaintId').value = id;
                document.getElementById('denialModal').style.display = 'flex';
            } else if (select.value === 'solved') {
                document.getElementById('solvedComplaintId').value = id;
                document.getElementById('solvedModal').style.display = 'flex';
            }
        }

        function closeModals() {
            document.getElementById('denialModal').style.display = 'none';
            document.getElementById('solvedModal').style.display = 'none';
            if (currentSelectElement) currentSelectElement.value = 'pending';
        }

        function checkOther(select) {
            const otherInput = document.getElementById('denialModalOtherReason');
            if (select.value === 'Other') {
                otherInput.style.display = 'block';
                otherInput.setAttribute('name', 'denial_reason');
                select.removeAttribute('name');
            } else {
                otherInput.style.display = 'none';
                select.setAttribute('name', 'denial_reason');
                otherInput.removeAttribute('name');
            }
        }

    </script>

</body>

</html>