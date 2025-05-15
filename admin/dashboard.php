<?php
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Get statistics with proper handling of NULL values
$stats = [
    'pending' => $pdo->query("SELECT COUNT(*) FROM content_changes WHERE status = 'pending' OR status IS NULL")->fetchColumn(),
    'approved' => $pdo->query("SELECT COUNT(*) FROM content_changes WHERE status = 'approved'")->fetchColumn(),
    'rejected' => $pdo->query("SELECT COUNT(*) FROM content_changes WHERE status = 'rejected'")->fetchColumn()
];

// Get latest pending changes with proper JOIN
$stmt = $pdo->query("
    SELECT 
        cc.*,
        f.full_name,
        f.username,
        f.department
    FROM content_changes cc
    JOIN faculty f ON cc.faculty_id = f.id
    WHERE cc.status = 'pending' OR cc.status IS NULL
    ORDER BY cc.created_at DESC
");
$pending_changes = $stmt->fetchAll();

// Get latest changes including approved and rejected ones
$stmt = $pdo->query("
    SELECT 
        cc.*,
        f.full_name,
        f.username,
        f.department
    FROM content_changes cc
    JOIN faculty f ON cc.faculty_id = f.id
    ORDER BY cc.created_at DESC
");
$pending_changes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - WMSU</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background-color: #7C0A02;
            color: white;
            padding: 2rem;
        }
        .main-content {
            flex: 1;
            padding: 2rem;
        }
        .header {
            margin-bottom: 2rem;
        }
        .nav-link {
            color: white;
            text-decoration: none;
            display: block;
            padding: 0.5rem 0;
        }
        .nav-link:hover {
            opacity: 0.8;
        }
        .logout-btn {
            margin-top: 2rem;
            padding: 0.5rem 1rem;
            background-color: #5c0701;
            border: none;
            color: white;
            cursor: pointer;
            border-radius: 4px;
        }
        .logout-btn:hover {
            background-color: #4a0601;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #7C0A02;
        }
        .stat-label {
            color: #666;
            margin-top: 0.5rem;
        }
        .changes-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
        }
        .changes-table th,
        .changes-table td {
            padding: 1rem;
            border: 1px solid #ddd;
            text-align: left;
        }
        .changes-table th {
            background: #f5f5f5;
        }
        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 0.5rem;
        }
        .approve-btn {
            background: #28a745;
            color: white;
        }
        .reject-btn {
            background: #dc3545;
            color: white;
        }
        .filter-buttons {
            margin-bottom: 20px;
        }
        .filter-btn {
            padding: 8px 16px;
            margin-right: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .filter-btn.all {
            background-color: #f8f9fa;
            color: #333;
        }
        .filter-btn.pending {
            background-color: #ffd700;
            color: #000;
        }
        .filter-btn.approved {
            background-color: #28a745;
            color: white;
        }
        .filter-btn.rejected {
            background-color: #dc3545;
            color: white;
        }
        .filter-btn:hover {
            opacity: 0.9;
        }
        .content-section {
            margin-bottom: 1rem;
        }
        .content-header {
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 0;
            padding: 8px 0;
        }
        .content-header:hover {
            opacity: 0.8;
        }
        .edit-history {
            padding: 2px;
            margin-top: 2px;
            font-size: 0.9em;
        }
        .history-items {
            max-height: 200px;
            overflow-y: auto;
        }
        .history-item {
            padding: 5px 0;
            border-bottom: 1px solid #ddd;
        }
        .toggle-icon {
            font-size: 12px;
            transition: transform 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <h2>Admin Panel</h2>
            <nav>
                <a href="#" class="nav-link">Dashboard</a>
                <a href="manage_faculty.php" class="nav-link">Manage Faculty</a>
            </nav>

            <h2>Content Management</h2>
            <nav>
                <div class="content-section">
                    <p class="content-header" onclick="toggleEditHistory('about_wmsu')">About WMSU <span class="toggle-icon">▼</span></p>
                    <div id="about_wmsu" class="edit-about" style="display: none;">
                        <h4 style="margin: 17px; padding: 0; font-weight: normal;"><a href="../WMSU-Homepage/cms/history_list.php" style="text-decoration: none; color: inherit;">Edit History</a></h4>
                    </div>
                </div>

                
                <div class="content-section">
                    <p class="content-header" onclick="toggleEditHistory('facts_figures')">Facts and Figures <span class="toggle-icon">▼</span></p>
                    <div id="facts_figures" class="edit-about" style="display: none;">
                        <h4 style="margin: 17px; padding: 0; font-weight: normal;"><a href="../WMSU-Homepage/cms/cms_facts.php" style="text-decoration: none; color: inherit;">Edit Facts</a></h4>
                       <!-- <h4 style="margin: 17px; padding: 0; font-weight: normal;"><a href="" style="text-decoration: none; color: inherit;">Edit Figures</a></h4> -->
                    </div>               
                </div>
                

                <div class="content-section">
                    <p class="content-header" onclick="toggleEditHistory('awards_recognitions')">Awards and Recognitions <span class="toggle-icon">▼</span></p>
                    <div id="awards_recognitions" class="edit-about" style="display: none;">
                        <h4 style="margin: 17px; padding: 0; font-weight: normal;"><a href="../WMSU-Homepage/cms/csm_awards.php" style="text-decoration: none; color: inherit;">Edit Awards</a></h4>
                       <!-- <h4 style="margin: 17px; padding: 0; font-weight: normal;">Edit Recognitions</h4> -->
                    </div>
                </div>

                 <!--
                <div class="content-section">
                    <p class="content-header" onclick="toggleEditHistory('office_president')">Office of The President <span class="toggle-icon">▼</span></p>
                    <div id="office_president" class="edit-about" style="display: none;">
                        <h4 style="margin: 17px; padding: 0; font-weight: normal;">Edit Biography</h4>
                        <h4 style="margin: 17px; padding: 0; font-weight: normal;">Edit Welcome Page</h4>
                    </div>
                   
                </div>  
                -->
            </nav>
            <form action="logout.php" method="POST">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>

        
        <div class="main-content">
            <h1>Admin Dashboard</h1>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['pending']; ?></div>
                    <div class="stat-label">Pending Changes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['approved']; ?></div>
                    <div class="stat-label">Approved Changes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['rejected']; ?></div>
                    <div class="stat-label">Rejected Changes</div>
                </div>
            </div>

            <h2>Recent Pending Changes</h2>
            <div class="filter-buttons">
                <button class="filter-btn all" onclick="filterChanges('all')">All Changes</button>
                <button class="filter-btn pending" onclick="filterChanges('pending')">Pending</button>
                <button class="filter-btn approved" onclick="filterChanges('approved')">Approved</button>
                <button class="filter-btn rejected" onclick="filterChanges('rejected')">Rejected</button>
            </div>
            <table class="changes-table">
                <thead>
                    <tr>
                        <th>Faculty</th>
                        <th>Department</th>
                        <th>Page</th>
                        <th>Date Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pending_changes as $change): ?>
                    <tr class="change-row" data-status="<?php echo htmlspecialchars($change['status'] ?? 'pending'); ?>">
                        <td><?php echo htmlspecialchars($change['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($change['department']); ?></td>
                        <td><?php echo htmlspecialchars($change['page_name']); ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($change['created_at'])); ?></td>
                        <td>
                            <a href="review_change.php?id=<?php echo $change['id']; ?>" class="action-btn">Review</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <script>
                function filterChanges(status) {
                    const rows = document.querySelectorAll('.change-row');
                    rows.forEach(row => {
                        if (status === 'all' || row.dataset.status === status) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    // Update active state of filter buttons
                    const buttons = document.querySelectorAll('.filter-btn');
                    buttons.forEach(btn => {
                        btn.style.opacity = btn.classList.contains(status) ? '1' : '0.6';
                    });
                }

                function toggleEditHistory(section) {
                    const historyDiv = document.getElementById(section);
                    const icon = event.currentTarget.querySelector('.toggle-icon');
                    
                    if (historyDiv.style.display === 'none') {
                        historyDiv.style.display = 'block';
                        icon.style.transform = 'rotate(180deg)';
                    } else {
                        historyDiv.style.display = 'none';
                        icon.style.transform = 'rotate(0deg)';
                    }
                }

                function fetchEditHistory(section) {
                    const historyItems = document.querySelector(`#${section}-history .history-items`);
                    
                    // Example of populating edit history - replace with actual AJAX call
                    const dummyHistory = [
                        { date: '2024-03-20', editor: 'John Doe', type: 'Update' },
                        { date: '2024-03-19', editor: 'Jane Smith', type: 'Revision' },
                        { date: '2024-03-18', editor: 'Admin', type: 'Initial Content' }
                    ];

                    historyItems.innerHTML = dummyHistory.map(item => `
                        <div class="history-item">
                            <div>${item.date} - ${item.editor}</div>
                            <div>${item.type}</div>
                        </div>
                    `).join('');
                }
            </script>
            
            <div style="margin-top: 2rem;">
                <a href="review_changes.php" style="color: #7C0A02;">View All Changes →</a>
            </div>
        </div>
    </div>
</body>
</html>