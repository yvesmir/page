<?php
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$change_id = $_GET['id'] ?? 0;
$success = '';
$error = '';

// Get change details
$stmt = $pdo->prepare("
    SELECT 
        cc.*,
        f.full_name,
        f.username,
        f.department
    FROM content_changes cc
    JOIN faculty f ON cc.faculty_id = f.id
    WHERE cc.id = ?
");
$stmt->execute([$change_id]);
$change = $stmt->fetch();

if (!$change) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $comment = $_POST['comment'] ?? '';
    
    try {
        $pdo->beginTransaction();
        
        // Update the content_changes status with proper status value
        $stmt = $pdo->prepare("UPDATE content_changes SET status = ?, admin_comment = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$action, $comment, $change_id]);
        
        // If approved, apply the changes to the actual content
        if ($action === 'approved') { // Changed from 'approve' to 'approved' to match enum values
            $new_content = json_decode($change['new_content'], true);
            
            try {
                // Apply changes based on the page_name
                if ($change['page_name'] === 'president_bio') {
                    $stmt = $pdo->prepare("
                        INSERT INTO president_bio (title, name, biography, bg_color, photo_path)
                        VALUES (?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                        title = VALUES(title),
                        name = VALUES(name),
                        biography = VALUES(biography),
                        bg_color = VALUES(bg_color),
                        photo_path = VALUES(photo_path)
                    ");
                    $stmt->execute([
                        $new_content['title'],
                        $new_content['name'],
                        $new_content['biography'],
                        $new_content['bg_color'],
                        $new_content['photo_path']
                    ]);
                } elseif ($change['page_name'] === 'welcome_page') {
                    $stmt = $pdo->prepare("
                        INSERT INTO welcome_page (title, quote, message_title, message_label, message_content, bg_color, header_photo, message_photo)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                        title = VALUES(title),
                        quote = VALUES(quote),
                        message_title = VALUES(message_title),
                        message_label = VALUES(message_label),
                        message_content = VALUES(message_content),
                        bg_color = VALUES(bg_color),
                        header_photo = VALUES(header_photo),
                        message_photo = VALUES(message_photo)
                    ");
                    $stmt->execute([
                        $new_content['title'],
                        $new_content['quote'],
                        $new_content['message_title'],
                        $new_content['message_label'],
                        $new_content['message_content'],
                        $new_content['bg_color'],
                        $new_content['header_photo'],
                        $new_content['message_photo']
                    ]);
                }
            } catch (PDOException $e) {
                throw new Exception('Database error: ' . $e->getMessage());
            }
        }
        
        $pdo->commit();
        $success = 'Changes have been ' . ($action === 'approved' ? 'approved' : 'rejected') . ' successfully';
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Error processing the request: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Change - WMSU</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: #7C0A02;
            padding: 2rem 1rem;
            color: white;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: #7C0A02;
            margin-bottom: 2rem;
            font-size: 2.2rem;
            border-bottom: 3px solid #7C0A02;
            padding-bottom: 0.5rem;
        }

        .change-details {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .change-info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .change-info-table th,
        .change-info-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .change-info-table th {
            background: #7C0A02;
            color: white;
            font-weight: 500;
        }

        .change-info-table tr:last-child td {
            border-bottom: none;
        }

        .change-info-table tr:hover td {
            background: #f9f9f9;
        }

        .content-comparison {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin: 2rem 0;
            overflow-x: auto;
        }

        .comparison-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .comparison-table th {
            background: #7C0A02;
            color: white;
            padding: 1.2rem;
            text-align: left;
            font-weight: 600;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            border-bottom: 3px solid rgba(0,0,0,0.1);
        }

        .comparison-table td {
            padding: 1.5rem;
            border: 1px solid #eee;
            vertical-align: top;
            transition: all 0.3s ease;
        }

        .field-label {
            font-weight: 600;
            color: #444;
            text-transform: capitalize;
            min-width: 150px;
            background: #f8f9fa;
        }

        .original-content {
            background-color: #fff8f8;
            width: 35%;
        }

        .proposed-content {
            background-color: #f8fff8;
            width: 35%;
        }

        .content-diff {
            padding: 1rem;
            border-radius: 6px;
            background: rgba(255,255,255,0.7);
            font-family: inherit;
            line-height: 1.8;
            font-size: 0.95rem;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .content-diff img {
            max-width: 250px;
            height: auto;
            border-radius: 8px;
            margin: 0.5rem 0;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .content-diff img:hover {
            transform: scale(1.05);
        }

        .comparison-table tr:hover td {
            background-color: rgba(255,255,255,0.9);
        }

        .comparison-table tr:nth-child(even) .field-label {
            background-color: #f0f2f5;
        }

        @media (max-width: 768px) {
            .comparison-table {
                display: block;
                width: 100%;
            }

            .comparison-table th {
                min-width: 120px;
                font-size: 1rem;
                padding: 1rem;
            }

            .comparison-table td {
                min-width: 250px;
                padding: 1rem;
            }

            .content-diff img {
                max-width: 200px;
            }
        }

        /* Enhanced textarea styling */
        textarea {
            width: 100%;
            min-height: 120px;
            padding: 1.2rem;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            margin: 1.2rem 0;
            font-family: inherit;
            font-size: 1rem;
            line-height: 1.6;
            resize: vertical;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        textarea:focus {
            border-color: #7C0A02;
            outline: none;
            box-shadow: 0 0 0 3px rgba(124, 10, 2, 0.1);
            background: white;
        }

        /* Enhanced action buttons */
        .action-buttons {
            display: flex;
            gap: 1.5rem;
            margin-top: 2.5rem;
        }

        .action-btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .approve-btn {
            background: #28a745;
            color: white;
        }

        .approve-btn:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
        }

        .reject-btn {
            background: #dc3545;
            color: white;
        }

        .reject-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
        }

        .back-link {
            display: inline-block;
            padding: 0.8rem 1.2rem;
            margin-bottom: 1.5rem;
            color: #7C0A02;
            text-decoration: none;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: rgba(124, 10, 2, 0.1);
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }

        .status-info {
            background: #e9ecef;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 2rem;
        }

        .status-info p {
            margin: 0.5rem 0;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                padding: 1rem;
            }

            .main-content {
                padding: 1rem;
            }

            .content-comparison {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <!-- ... existing sidebar ... -->
        </div>
        <div class="main-content">
            <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
            <h1>Review Change</h1>
            
            <?php if($success): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="change-details">
                <h3>Change Details</h3>
                <table class="change-info-table">
                    <tr>
                        <th>Faculty</th>
                        <td><?php echo htmlspecialchars($change['full_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Department</th>
                        <td><?php echo htmlspecialchars($change['department']); ?></td>
                    </tr>
                    <tr>
                        <th>Page</th>
                        <td><?php echo htmlspecialchars($change['page_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Submitted</th>
                        <td><?php echo date('M d, Y H:i', strtotime($change['created_at'])); ?></td>
                    </tr>
                </table>
                
                <div class="content-comparison">
                    <table class="comparison-table">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Original Content</th>
                                <th>Proposed Changes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $original = json_decode($change['original_content'], true);
                            $proposed = json_decode($change['new_content'], true);
                            
                            foreach($original as $key => $value): 
                            ?>
                            <tr>
                                <td class="field-label"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $key))); ?></td>
                                <td class="original-content">
                                    <div class="content-diff">
                                        <?php 
                                        if(strpos($key, 'photo') !== false || strpos($key, 'path') !== false) {
                                            echo '<img src="../' . htmlspecialchars($value) . '" alt="Original ' . htmlspecialchars($key) . '">';
                                        } else {
                                            echo nl2br(htmlspecialchars($value));
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td class="proposed-content">
                                    <div class="content-diff">
                                        <?php 
                                        if(strpos($key, 'photo') !== false || strpos($key, 'path') !== false) {
                                            echo '<img src="../' . htmlspecialchars($proposed[$key]) . '" alt="Proposed ' . htmlspecialchars($key) . '">';
                                        } else {
                                            echo nl2br(htmlspecialchars($proposed[$key]));
                                        }
                                        ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if($change['status'] === 'pending'): ?>
                <form method="POST" action="">
                    <div>
                        <label for="comment">Admin Comment:</label>
                        <textarea name="comment" id="comment"></textarea>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" name="action" value="approved" class="action-btn approve-btn">Approve Changes</button>
                        <button type="submit" name="action" value="rejected" class="action-btn reject-btn">Reject Changes</button>
                    </div>
                </form>
                <?php else: ?>
                <div class="status-info">
                    <p><strong>Status:</strong> <?php echo ucfirst($change['status']); ?></p>
                    <?php if($change['admin_comment']): ?>
                        <p><strong>Admin Comment:</strong> <?php echo nl2br(htmlspecialchars($change['admin_comment'])); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <a href="dashboard.php">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>