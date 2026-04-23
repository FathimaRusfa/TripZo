<?php
include __DIR__ . '/admin-common.php';

$usersSql = "SELECT users.user_id,
                    users.name,
                    users.email,
                    users.role,
                    COUNT(DISTINCT tripplans.trip_plan_id) AS trip_count,
                    COUNT(DISTINCT reviews.review_id) AS review_count
             FROM users
             LEFT JOIN tripplans ON tripplans.user_id = users.user_id
             LEFT JOIN reviews ON reviews.user_id = users.user_id
             GROUP BY users.user_id, users.name, users.email, users.role
             ORDER BY users.role DESC, users.name ASC";
$usersResult = $conn->query($usersSql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo adminEscape(adminAssetPath('css/style.css')); ?>">
</head>
<body>
<nav class="navbar navbar-dark navbar-tripzo py-3">
    <div class="container">
        <span class="navbar-brand mb-0 h1">TripZo User Directory</span>
        <div class="d-flex gap-2">
            <a href="dashboard.php" class="btn btn-outline-light">Dashboard</a>
            <a href="../index.php" class="btn btn-outline-light">View Site</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="content-box content-box-soft">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h2 class="mb-1">Users</h2>
                <p class="mb-0">Review basic account details and activity across saved trips and reviews.</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Saved Trips</th>
                        <th>Reviews</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($usersResult && $usersResult->num_rows > 0) { ?>
                        <?php while ($user = $usersResult->fetch_assoc()) { ?>
                            <tr>
                                <td>
                                    <strong><?php echo adminEscape($user['name']); ?></strong><br>
                                    <small class="text-muted">User ID: <?php echo (int) $user['user_id']; ?></small>
                                </td>
                                <td><?php echo adminEscape($user['email']); ?></td>
                                <td><span class="badge-soft"><?php echo adminEscape(ucfirst($user['role'])); ?></span></td>
                                <td><?php echo (int) $user['trip_count']; ?></td>
                                <td><?php echo (int) $user['review_count']; ?></td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">No users found yet.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
