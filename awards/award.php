<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../cms/database.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int) $_GET['id'];

    $stmt = $conn->prepare("SELECT * FROM awards WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $award = $result->fetch_assoc();

    if (!$award) {
        echo "Award not found.";
        exit();
    }
} else {
    echo "Invalid award ID.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($award['title']) ?> - Award Details</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/award.css">
</head>

<body>

<section class="award-content">
    <h1 class="award-title"><?= htmlspecialchars($award['title']) ?></h1>

    <div class="award-date mb-3">
        <i class="bi bi-calendar-event"></i>
        <?= date('F j, Y', strtotime($award['date'])) ?>
    </div>
<br>
<br>
    <div class="d-flex flex-row align-items-start" style="gap: 100px;">
        <div class="award-description" style="flex: 1;">
            <?= nl2br(htmlspecialchars($award['description'])) ?>
        </div>

        <div class="award-image" style="width: 400px; flex-shrink: 0;">
            <?php if (!empty($award['image_path']) && file_exists(__DIR__ . '/../uploads/' . $award['image_path'])): ?>
                <img src="../uploads/<?= htmlspecialchars($award['image_path']) ?>" alt="<?= htmlspecialchars($award['title']) ?>" class="img-fluid rounded">
            <?php else: ?>
                <img src="https://via.placeholder.com/400x300?text=No+Image" alt="Placeholder Image" class="img-fluid rounded">
            <?php endif; ?>
        </div>
    </div>

    <a href="awards.php" class="btn-back d-inline-block mt-4">← Back to Awards</a>
</section>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

</body>
</html>
