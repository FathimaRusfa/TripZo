<?php
include 'db.php';
include 'navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: user-login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_trip_id'])) {
    $tripId = (int) $_POST['delete_trip_id'];

    $ownerStmt = $conn->prepare("SELECT trip_plan_id FROM tripplans WHERE trip_plan_id = ? AND user_id = ? LIMIT 1");
    $ownerStmt->bind_param('ii', $tripId, $userId);
    $ownerStmt->execute();
    $ownerResult = $ownerStmt->get_result();

    if ($ownerResult && $ownerResult->num_rows === 1) {
        $deleteItemsStmt = $conn->prepare("DELETE FROM tripplanitems WHERE trip_plan_id = ?");
        $deleteItemsStmt->bind_param('i', $tripId);
        $deleteItemsStmt->execute();

        $deleteTripStmt = $conn->prepare("DELETE FROM tripplans WHERE trip_plan_id = ? AND user_id = ?");
        $deleteTripStmt->bind_param('ii', $tripId, $userId);
        $deleteTripStmt->execute();

        $message = 'Trip deleted successfully.';
    }
}

$tripsStmt = $conn->prepare("SELECT trip_plan_id, travel_mode FROM tripplans WHERE user_id = ? ORDER BY trip_plan_id DESC");
$tripsStmt->bind_param('i', $userId);
$tripsStmt->execute();
$tripsResult = $tripsStmt->get_result();
?>

<div class="container mt-5">
    <div class="page-header text-center">
        <h2>My Saved Trips</h2>
        <p>Review and manage your personal trip plans.</p>
    </div>

    <?php if ($message !== '') { ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php } ?>

    <?php if ($tripsResult && $tripsResult->num_rows > 0) { ?>
        <div class="d-flex flex-column gap-4">
            <?php while ($trip = $tripsResult->fetch_assoc()) {
                $tripId = (int) $trip['trip_plan_id'];
                $itemsStmt = $conn->prepare("SELECT a.attraction_id, a.name, a.short_description, a.distance_km
                    FROM tripplanitems tpi
                    INNER JOIN attractions a ON a.attraction_id = tpi.attraction_id
                    WHERE tpi.trip_plan_id = ?
                    ORDER BY tpi.visit_order ASC");
                $itemsStmt->bind_param('i', $tripId);
                $itemsStmt->execute();
                $itemsResult = $itemsStmt->get_result();
            ?>
                <div class="content-box content-box-soft">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                        <div>
                            <h5 class="mb-1">Trip #<?php echo $tripId; ?></h5>
                            <p class="mb-0 text-muted">Travel Mode: <?php echo htmlspecialchars($trip['travel_mode'] ?: 'N/A'); ?></p>
                        </div>
                        <form method="POST" action="">
                            <input type="hidden" name="delete_trip_id" value="<?php echo $tripId; ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete Trip</button>
                        </form>
                    </div>

                    <?php if ($itemsResult && $itemsResult->num_rows > 0) { ?>
                        <ol class="list-group list-group-numbered">
                            <?php while ($item = $itemsResult->fetch_assoc()) { ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between flex-wrap gap-2">
                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                        <span class="text-muted"><?php echo htmlspecialchars((string) $item['distance_km']); ?> km</span>
                                    </div>
                                    <p class="mb-2"><?php echo htmlspecialchars($item['short_description']); ?></p>
                                    <a class="btn btn-sm btn-outline-primary" href="place-details.php?id=<?php echo (int) $item['attraction_id']; ?>">View Attraction</a>
                                </li>
                            <?php } ?>
                        </ol>
                    <?php } else { ?>
                        <p class="mb-0 text-muted">No attractions linked to this trip.</p>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    <?php } else { ?>
        <div class="content-box text-center py-5">
            <h5 class="mb-2">No saved trips yet</h5>
            <p class="mb-3">Create a trip from the planner and save it to your account.</p>
            <a href="planner.php" class="btn btn-primary">Open Planner</a>
        </div>
    <?php } ?>
</div>

<?php include 'footer.php'; ?>
