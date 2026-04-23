<?php
include __DIR__ . '/admin-common.php';

$totalAttractions = $conn->query("SELECT COUNT(*) AS total FROM attractions")->fetch_assoc()['total'];
$totalReviews = $conn->query("SELECT COUNT(*) AS total FROM reviews")->fetch_assoc()['total'];
$pendingReviews = $conn->query("SELECT COUNT(*) AS total FROM reviews WHERE status = 'pending'")->fetch_assoc()['total'];
$totalUsers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'user'")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TripZo Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo adminEscape(adminAssetPath('css/style.css')); ?>">
</head>
<body>

<nav class="navbar navbar-dark navbar-tripzo py-3">
    <div class="container">
        <span class="navbar-brand mb-0 h1">TripZo Admin Dashboard</span>
        <div class="d-flex gap-2">
            <a href="../index.php" class="btn btn-outline-light">Back to Site</a>
            <a href="logout.php" class="btn btn-outline-light">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="content-box">
        <div class="mb-4">
            <h2>Welcome, <?php echo adminEscape($_SESSION['admin_name']); ?>!</h2>
            <p class="mb-0">Manage attractions, location details, reviews, and user activity from one control panel.</p>
        </div>

        <div class="row mt-4 mb-4">
            <div class="col-md-3 mb-3">
                <div class="dashboard-card">
                    <h5>Total Attractions</h5>
                    <h3><?php echo (int) $totalAttractions; ?></h3>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="dashboard-card">
                    <h5>Total Reviews</h5>
                    <h3><?php echo (int) $totalReviews; ?></h3>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="dashboard-card">
                    <h5>Pending Reviews</h5>
                    <h3><?php echo (int) $pendingReviews; ?></h3>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="dashboard-card">
                    <h5>Total Users</h5>
                    <h3><?php echo (int) $totalUsers; ?></h3>
                </div>
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-md-4 col-lg-2 mb-3">
                <a href="add-place.php" class="btn btn-primary w-100 py-3">Add Attraction</a>
            </div>

            <div class="col-md-4 col-lg-2 mb-3">
                <a href="manage-places.php" class="btn btn-secondary w-100 py-3">Manage Attractions</a>
            </div>

            <div class="col-md-4 col-lg-2 mb-3">
                <a href="reviews.php" class="btn btn-warning w-100 py-3 text-dark">Moderate Reviews</a>
            </div>

            <div class="col-md-6 col-lg-2 mb-3">
                <a href="manage-users.php" class="btn btn-info w-100 py-3 text-white">View Users</a>
            </div>

            <div class="col-md-6 col-lg-2 mb-3">
                <a href="../map.php" class="btn btn-outline-dark w-100 py-3">Check Map</a>
            </div>

            <div class="col-md-6 col-lg-2 mb-3">
                <a href="logout.php" class="btn btn-danger w-100 py-3">Logout</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
