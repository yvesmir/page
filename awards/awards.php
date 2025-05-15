<?php
include '../cms/database.php';
$sql = "SELECT * FROM awards ORDER BY date DESC";
$result = $conn->query($sql);

$awards = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $awards[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Awards and Recognitions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/awards.css">
</head>
<body>

    <div class="header">
         AWARDS AND RECOGNITIONS
    </div>

    <div class="content">
    <ul class="award-list">
        <?php foreach ($awards as $award): ?>
            <li>
                <a class="award-title" href="award.php?id=<?= $award['id'] ?>">
                    <?= htmlspecialchars($award['title']) ?>
                </a> —
                <span class="award-year"><?= date('Y', strtotime($award['date'])) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>


</body>
</html>
