<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

include '../db.php';

if (isset($_GET['approve'])) {
    $review_id = (int) $_GET['approve'];
    $conn->query("UPDATE reviews SET status='approved' WHERE review_id=$review_id");
    header("Location: reviews.php");
    exit;
}

if (isset($_GET['delete'])) {
    $review_id = (int) $_GET['delete'];
    $conn->query("DELETE FROM reviews WHERE review_id=$review_id");
    header("Location: reviews.php");
    exit;
}

$sql = "SELECT reviews.*, attractions.name AS attraction_name
        FROM reviews
        LEFT JOIN attractions ON reviews.attraction_id = attractions.attraction_id
        WHERE reviews.status = 'pending'
        ORDER BY reviews.created_at DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderate Reviews</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h2 class="mb-4">Moderate Reviews</h2>
    <a href="dashboard.php" class="btn btn-secondary mb-3">Back to Dashboard</a>

    <?php if ($result->num_rows > 0) { ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Attraction</th>
                    <th>Rating</th>
                    <th>Review</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $row['review_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['attraction_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['rating']); ?>/5</td>
                    <td><?php echo htmlspecialchars($row['review_text']); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                    <td>
                        <a href="reviews.php?approve=<?php echo $row['review_id']; ?>" class="btn btn-sm btn-success">Approve</a>
                        <a href="reviews.php?delete=<?php echo $row['review_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this review?');">Delete</a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <div class="alert alert-info">No pending reviews.</div>
    <?php } ?>
</div>

</body>
</html>