<?php
include __DIR__ . '/admin-common.php';

$search = trim($_GET['q'] ?? '');
$sql = "SELECT attractions.*, categories.name AS category_name
        FROM attractions
        LEFT JOIN categories ON attractions.category_id = categories.category_id";

if ($search !== '') {
    $sql .= " WHERE attractions.name LIKE ? OR attractions.address LIKE ? OR attractions.area_name LIKE ? OR attractions.district LIKE ?";
}

$sql .= " ORDER BY attractions.attraction_id DESC";
$stmt = $conn->prepare($sql);

if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt->bind_param('ssss', $like, $like, $like, $like);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Attractions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo adminEscape(adminAssetPath('css/style.css')); ?>">
</head>
<body>

<nav class="navbar navbar-dark navbar-tripzo py-3">
    <div class="container">
        <span class="navbar-brand mb-0 h1">Manage Attractions</span>
        <div class="d-flex gap-2">
            <a href="add-place.php" class="btn btn-primary">Add Attraction</a>
            <a href="dashboard.php" class="btn btn-outline-light">Dashboard</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="content-box content-box-soft">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4">
            <div>
                <h2 class="mb-1">Place and Location Records</h2>
                <p class="mb-0">Search, edit, and verify attraction details including coordinates and visitor notes.</p>
            </div>
            <form method="GET" class="d-flex gap-2 flex-wrap">
                <input type="text" class="form-control" name="q" placeholder="Search name, area, district" value="<?php echo adminEscape($search); ?>">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="manage-places.php" class="btn btn-outline-secondary">Reset</a>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Place</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Coordinates</th>
                        <th>Distance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result && $result->num_rows > 0) { ?>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td>
                                <strong><?php echo adminEscape($row['name']); ?></strong><br>
                                <small class="text-muted"><?php echo adminEscape($row['short_description']); ?></small>
                            </td>
                            <td><?php echo adminEscape($row['category_name']); ?></td>
                            <td>
                                <?php echo adminEscape($row['address']); ?><br>
                                <small class="text-muted">
                                    <?php echo adminEscape(trim(implode(', ', array_filter([$row['area_name'] ?? '', $row['district'] ?? '', $row['province'] ?? ''])))); ?>
                                </small>
                            </td>
                            <td>
                                <span class="d-block"><?php echo adminEscape($row['latitude']); ?></span>
                                <span class="text-muted"><?php echo adminEscape($row['longitude']); ?></span>
                            </td>
                            <td><?php echo adminEscape($row['distance_km']); ?> km</td>
                            <td class="text-nowrap">
                                <a href="edit-place.php?id=<?php echo (int) $row['attraction_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="../place-details.php?id=<?php echo (int) $row['attraction_id']; ?>" class="btn btn-sm btn-outline-secondary">Preview</a>
                                <a href="delete-place.php?id=<?php echo (int) $row['attraction_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this attraction?');">Delete</a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">No attractions match your search.</td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
