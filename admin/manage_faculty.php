<?php
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $department = $_POST['department'] ?? '';
    
    // Handle file upload
    $photo_path = null;
    if(isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/faculty/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        
        if(in_array($file_extension, $allowed_extensions)) {
            $unique_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $unique_filename;
            
            if(move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                $photo_path = 'uploads/faculty/' . $unique_filename;
            } else {
                $error = 'Error uploading file';
            }
        } else {
            $error = 'Invalid file type. Allowed types: jpg, jpeg, png, gif';
        }
    }

    if (!empty($username) && !empty($password) && !empty($full_name)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO faculty (username, password, full_name, department, photo_path) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $full_name, $department, $photo_path]);
            $success = 'Faculty account created successfully';
        } catch(PDOException $e) {
            $error = 'Error creating account: ' . $e->getMessage();
        }
    } else {
        $error = 'Please fill in all required fields';
    }
}

// Fetch existing faculty accounts
$stmt = $pdo->query("SELECT id, username, full_name, department, photo_path FROM faculty");
$faculty_members = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Faculty - WMSU</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
        }
        input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            padding: 0.75rem 1.5rem;
            background-color: #7C0A02;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #5c0701;
        }
        .error {
            color: red;
            margin-bottom: 1rem;
        }
        .success {
            color: green;
            margin-bottom: 1rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
        }
        th, td {
            padding: 0.75rem;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Faculty Accounts</h1>
        <a href="dashboard.php" style="margin-bottom: 20px; display: inline-block;">← Back to Dashboard</a>
        
        <?php if($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <h2>Add New Faculty Account</h2>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="username">Username*</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password*</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="full_name">Full Name*</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="department">Department</label>
                <input type="text" id="department" name="department">
            </div>
            <div class="form-group">
                <label for="photo">Photo</label>
                <input type="file" id="photo" name="photo" accept="image/*">
            </div>
            <button type="submit">Create Faculty Account</button>
        </form>

        <h2>Existing Faculty Accounts</h2>
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Department</th>
                    <th>Photo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($faculty_members as $faculty): ?>
                <tr>
                    <td><?php echo htmlspecialchars($faculty['username']); ?></td>
                    <td><?php echo htmlspecialchars($faculty['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($faculty['department']); ?></td>
                    <td>
                        <?php if($faculty['photo_path']): ?>
                            <img src="../<?php echo htmlspecialchars($faculty['photo_path']); ?>" alt="Faculty photo" style="max-width: 50px;">
                        <?php else: ?>
                            No photo
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>