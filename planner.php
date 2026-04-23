<?php
include 'db.php';
include 'navbar.php';

$message = "";
$saveMessage = "";
$selected_attractions = [];
$travel_mode = 'Car';
$plannedPlaces = [];
$mapReadyPlaces = [];
$isUserLoggedIn = isset($_SESSION['user_id']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selected_attractions = $_POST['attractions'] ?? [];
    $travel_mode = $_POST['travel_mode'] ?? 'Car';
    $savePlan = isset($_POST['save_plan']) && $_POST['save_plan'] === '1';

    if (!empty($selected_attractions)) {
        $ids = implode(",", array_map('intval', $selected_attractions));
        $sql = "SELECT * FROM attractions WHERE attraction_id IN ($ids) ORDER BY distance_km ASC, name ASC";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $plannedPlaces[] = $row;
            }
        }

        if ($savePlan && !empty($plannedPlaces)) {
            if (!$isUserLoggedIn) {
                $saveMessage = "Log in as a user to save this trip plan.";
            } else {
                $userId = (int) $_SESSION['user_id'];
                $insertPlanStmt = $conn->prepare("INSERT INTO tripplans (user_id, start_time, end_time, travel_mode) VALUES (?, NULL, NULL, ?)");
                $insertPlanStmt->bind_param('is', $userId, $travel_mode);

                if ($insertPlanStmt->execute()) {
                    $tripPlanId = (int) $insertPlanStmt->insert_id;
                    $insertItemStmt = $conn->prepare("INSERT INTO tripplanitems (trip_plan_id, attraction_id, visit_order) VALUES (?, ?, ?)");

                    $visitOrder = 1;
                    foreach ($plannedPlaces as $place) {
                        $attractionId = (int) $place['attraction_id'];
                        $insertItemStmt->bind_param('iii', $tripPlanId, $attractionId, $visitOrder);
                        $insertItemStmt->execute();
                        $visitOrder++;
                    }

                    $saveMessage = "Trip plan saved. You can view it in My Trips.";
                } else {
                    $saveMessage = "Unable to save trip right now. Please try again.";
                }
            }
        }
    } else {
        $message = "Please select at least one attraction.";
    }
}

$all_attractions = $conn->query("SELECT * FROM attractions ORDER BY name ASC");
?>

<div class="container mt-5">
    <div class="page-header text-center">
        <h2>One-Day Trip Planner</h2>
        <p>Select attractions and generate a route-ready plan.</p>
    </div>

    <?php if (!empty($message)) { ?>
        <div class="alert alert-warning"><?php echo $message; ?></div>
    <?php } ?>
    <?php if (!empty($saveMessage)) { ?>
        <div class="alert <?php echo strpos($saveMessage, 'saved') !== false ? 'alert-success' : 'alert-info'; ?>"><?php echo htmlspecialchars($saveMessage); ?></div>
    <?php } ?>

    <form method="POST" class="content-box content-box-soft" data-aos="fade-up">
        <div class="mb-3">
            <label class="form-label">Select Attractions</label>
            <?php while ($place = $all_attractions->fetch_assoc()) { ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="attractions[]" value="<?php echo $place['attraction_id']; ?>" <?php echo in_array((string) $place['attraction_id'], array_map('strval', $selected_attractions), true) ? 'checked' : ''; ?>>
                    <label class="form-check-label">
                        <?php echo htmlspecialchars($place['name']); ?> (<?php echo htmlspecialchars($place['distance_km']); ?> km)
                    </label>
                </div>
            <?php } ?>
        </div>

        <div class="row align-items-end">
            <div class="col-md-4 mb-3">
                <label>Travel Mode</label>
                <select name="travel_mode" class="form-select" required>
                    <option value="Car" <?php echo $travel_mode === 'Car' ? 'selected' : ''; ?>>Car</option>
                    <option value="Bike" <?php echo $travel_mode === 'Bike' ? 'selected' : ''; ?>>Bike</option>
                    <option value="Walking" <?php echo $travel_mode === 'Walking' ? 'selected' : ''; ?>>Walking</option>
                </select>
            </div>
            <div class="col-md-8 mb-3 text-md-end">
                <div class="form-check d-inline-flex align-items-center me-3">
                    <input class="form-check-input me-2" type="checkbox" id="save_plan" name="save_plan" value="1">
                    <label class="form-check-label small" for="save_plan">Save this plan to my account</label>
                </div>
                <button type="submit" class="btn btn-success">Generate Trip Plan</button>
            </div>
        </div>

        <?php if (!$isUserLoggedIn) { ?>
            <p class="small text-muted mb-0">To save plans, <a href="user-login.php">log in</a> or <a href="signup.php">create an account</a>.</p>
        <?php } else { ?>
            <p class="small text-muted mb-0">Signed in as <?php echo htmlspecialchars((string) $_SESSION['user_name']); ?>. Saved plans appear in <a href="my-trips.php">My Trips</a>.</p>
        <?php } ?>
    </form>

    <?php if (!empty($plannedPlaces)) {
        foreach ($plannedPlaces as $place) {
            $latitude = isset($place['latitude']) ? trim((string) $place['latitude']) : '';
            $longitude = isset($place['longitude']) ? trim((string) $place['longitude']) : '';
            if ($latitude !== '' && $longitude !== '' && is_numeric($latitude) && is_numeric($longitude)) {
                $mapReadyPlaces[] = [
                    'id' => (int) $place['attraction_id'],
                    'name' => $place['name'],
                    'latitude' => (float) $latitude,
                    'longitude' => (float) $longitude
                ];
            }
        }
    ?>
        <div class="mt-5">
            <h4>Your Suggested Trip Plan</h4>
            <p><strong>Travel Mode:</strong> <?php echo htmlspecialchars($travel_mode); ?></p>

            <ol class="list-group list-group-numbered">
                <?php foreach ($plannedPlaces as $row) {
                    $lat = isset($row['latitude']) && is_numeric($row['latitude']) ? (float) $row['latitude'] : null;
                    $lng = isset($row['longitude']) && is_numeric($row['longitude']) ? (float) $row['longitude'] : null;
                ?>
                    <li class="list-group-item d-flex flex-column gap-2">
                        <div>
                            <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                            <?php echo htmlspecialchars($row['short_description']); ?><br>
                            Distance: <?php echo htmlspecialchars($row['distance_km']); ?> km
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-sm btn-outline-primary" href="map.php?focus=<?php echo (int) $row['attraction_id']; ?>">View on TripZo Map</a>
                            <?php if ($lat !== null && $lng !== null) { ?>
                                <a class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener noreferrer" href="https://www.google.com/maps/search/?api=1&query=<?php echo rawurlencode((string) $lat . ',' . (string) $lng); ?>">Open in Google Maps</a>
                            <?php } ?>
                        </div>
                    </li>
                <?php } ?>
            </ol>

            <div class="content-box content-box-soft mt-4" data-aos="fade-up">
                <h5 class="mb-2">Route Preview</h5>
                <p class="mb-3">Trip path between selected attractions (closest-first plan).</p>
                <div id="planner-route-map" class="tripzo-map"></div>
                <small class="text-muted d-block mt-2">If routing service is unavailable, a direct line path is shown.</small>
            </div>
        </div>
    <?php } ?>
</div>

<?php if (!empty($mapReadyPlaces)) { ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
        if (typeof L === 'undefined') {
            return;
        }

        var places = <?php echo json_encode($mapReadyPlaces, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
        if (!places.length) {
            return;
        }

        var modeMap = {
            'Car': 'driving',
            'Bike': 'cycling',
            'Walking': 'foot'
        };
        var selectedMode = <?php echo json_encode($travel_mode); ?>;
        var profile = modeMap[selectedMode] || 'driving';
        var map = L.map('planner-route-map');
        var bounds = L.latLngBounds([]);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var markerPoints = places.map(function (place, index) {
            var latLng = [place.latitude, place.longitude];
            bounds.extend(latLng);
            L.marker(latLng).addTo(map).bindPopup((index + 1) + '. ' + place.name);
            return place.longitude + ',' + place.latitude;
        });

        if (bounds.isValid()) {
            map.fitBounds(bounds, { padding: [24, 24] });
        }

        if (markerPoints.length >= 2) {
            fetch('https://router.project-osrm.org/route/v1/' + profile + '/' + markerPoints.join(';') + '?overview=full&geometries=geojson')
                .then(function (response) {
                    return response.ok ? response.json() : Promise.reject(new Error('Route service failed'));
                })
                .then(function (data) {
                    if (!data.routes || !data.routes.length) {
                        throw new Error('No route data available');
                    }

                    var routeCoordinates = data.routes[0].geometry.coordinates.map(function (point) {
                        return [point[1], point[0]];
                    });
                    var routeLine = L.polyline(routeCoordinates, { color: '#0d6efd', weight: 5, opacity: 0.85 }).addTo(map);
                    map.fitBounds(routeLine.getBounds(), { padding: [28, 28] });
                })
                .catch(function () {
                    var fallbackLine = places.map(function (place) {
                        return [place.latitude, place.longitude];
                    });
                    L.polyline(fallbackLine, { color: '#198754', weight: 4, opacity: 0.75, dashArray: '8 6' }).addTo(map);
                });
        }
    });
    </script>
<?php } ?>

<?php include 'footer.php'; ?>
