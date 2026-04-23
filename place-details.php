<?php
include 'db.php';
include 'navbar.php';

if (!isset($_GET['id'])) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>No attraction selected.</div></div>";
    include 'footer.php';
    exit;
}

$id = (int) $_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
    $rating = (int) ($_POST['rating'] ?? 0);
    $review_text = trim($_POST['review_text'] ?? '');

    if ($rating >= 1 && $rating <= 5 && $review_text !== '') {
        $reviewStmt = $conn->prepare("INSERT INTO reviews (user_id, attraction_id, rating, review_text, status) VALUES (?, ?, ?, ?, 'pending')");
        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 1;
        $reviewStmt->bind_param('iiis', $userId, $id, $rating, $review_text);
        $reviewStmt->execute();

        echo "<div class='container mt-4'><div class='alert alert-success'>Review submitted successfully and is pending approval.</div></div>";
    }
}

$sql = "SELECT attractions.*, categories.name AS category_name
        FROM attractions
        LEFT JOIN categories ON attractions.category_id = categories.category_id
        WHERE attractions.attraction_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div class='container mt-5'><div class='alert alert-warning'>Attraction not found.</div></div>";
    include 'footer.php';
    exit;
}

$row = $result->fetch_assoc();
$galleryStmt = $conn->prepare("SELECT image_path FROM attraction_images WHERE attraction_id = ? ORDER BY sort_order ASC, image_id ASC");
$galleryStmt->bind_param('i', $id);
$galleryStmt->execute();
$galleryResult = $galleryStmt->get_result();

$galleryImages = [];
if (!empty($row['image'])) {
    $galleryImages[] = $row['image'];
}

while ($galleryImage = $galleryResult->fetch_assoc()) {
    if (!empty($galleryImage['image_path']) && !in_array($galleryImage['image_path'], $galleryImages, true)) {
        $galleryImages[] = $galleryImage['image_path'];
    }
}
?>

<div class="container mt-5">
    <div class="content-box" data-aos="fade-up">
        <div class="row g-4">
            <div class="col-md-6">
                <?php if (!empty($galleryImages)) { ?>
                    <div id="placeGalleryCarousel" class="carousel slide place-gallery-carousel" data-bs-ride="carousel">
                        <?php if (count($galleryImages) > 1) { ?>
                            <div class="carousel-indicators place-gallery-indicators">
                                <?php foreach ($galleryImages as $index => $galleryImagePath) { ?>
                                    <button type="button" data-bs-target="#placeGalleryCarousel" data-bs-slide-to="<?php echo $index; ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>" <?php echo $index === 0 ? 'aria-current="true"' : ''; ?> aria-label="Slide <?php echo $index + 1; ?>"></button>
                                <?php } ?>
                            </div>
                        <?php } ?>

                        <div class="carousel-inner rounded shadow">
                            <?php foreach ($galleryImages as $index => $galleryImagePath) { ?>
                                <div class="carousel-item<?php echo $index === 0 ? ' active' : ''; ?>">
                                    <a href="images/<?php echo htmlspecialchars($galleryImagePath); ?>" class="tripzo-lightbox detail-image-link" data-gallery="attraction-gallery" data-glightbox="title: <?php echo htmlspecialchars($row['name']); ?>">
                                        <img src="images/<?php echo htmlspecialchars($galleryImagePath); ?>" class="img-fluid place-gallery-image" alt="<?php echo htmlspecialchars($row['name']); ?>">
                                        <span class="detail-image-badge"><i class="bi bi-arrows-fullscreen me-2"></i>View photo</span>
                                    </a>
                                </div>
                            <?php } ?>
                        </div>

                        <?php if (count($galleryImages) > 1) { ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#placeGalleryCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#placeGalleryCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
            <div class="col-md-6">
                <span class="badge-soft"><?php echo htmlspecialchars($row['category_name']); ?></span>
                <h2 class="mt-3"><?php echo htmlspecialchars($row['name']); ?></h2>

                <div class="info-list mt-3">
                    <p><strong><i class="bi bi-geo-alt me-2"></i>Address:</strong> <?php echo htmlspecialchars($row['address']); ?></p>
                    <p><strong><i class="bi bi-pin-map me-2"></i>Area:</strong> <?php echo htmlspecialchars(trim(implode(', ', array_filter([$row['area_name'] ?? '', $row['district'] ?? '', $row['province'] ?? ''])))); ?></p>
                    <p><strong><i class="bi bi-clock me-2"></i>Opening Hours:</strong> <?php echo htmlspecialchars($row['opening_hours']); ?></p>
                    <p><strong><i class="bi bi-telephone me-2"></i>Contact:</strong> <?php echo htmlspecialchars($row['contact_info']); ?></p>
                    <p><strong><i class="bi bi-signpost me-2"></i>Distance:</strong> <?php echo htmlspecialchars($row['distance_km']); ?> km</p>
                    <p><strong><i class="bi bi-star-fill me-2"></i>Rating:</strong> <?php echo htmlspecialchars($row['rating']); ?>/5</p>
                    <p><strong><i class="bi bi-shield-check me-2"></i>Safety Notes:</strong> <?php echo htmlspecialchars($row['safety_notes']); ?></p>
                    <p><strong><i class="bi bi-signpost-2 me-2"></i>Nearby Landmarks:</strong> <?php echo htmlspecialchars($row['nearby_landmarks'] ?? 'Not specified'); ?></p>
                    <p><strong><i class="bi bi-car-front me-2"></i>Transport:</strong> <?php echo htmlspecialchars($row['transport_options'] ?? 'Not specified'); ?></p>
                    <p><strong><i class="bi bi-calendar-event me-2"></i>Best Time to Visit:</strong> <?php echo htmlspecialchars($row['best_time_to_visit'] ?? 'Not specified'); ?></p>
                    <p><strong><i class="bi bi-ticket-perforated me-2"></i>Entry Fee:</strong> <?php echo htmlspecialchars($row['entry_fee'] ?? 'Not specified'); ?></p>
                    <p><strong><i class="bi bi-universal-access me-2"></i>Accessibility:</strong> <?php echo htmlspecialchars($row['accessibility_info'] ?? 'Not specified'); ?></p>
                    <?php if (!empty($row['google_maps_url'])) { ?>
                        <p><strong><i class="bi bi-box-arrow-up-right me-2"></i>Directions:</strong> <a href="<?php echo htmlspecialchars($row['google_maps_url']); ?>" target="_blank" rel="noopener">Open in Google Maps</a></p>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <h4>Description</h4>
            <p><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
        </div>

        <div class="mt-5">
            <h4>Submit a Review</h4>
            <form method="POST">
                <div class="mb-3">
                    <label>Rating (1-5)</label>
                    <select name="rating" class="form-control" required>
                        <option value="">Select Rating</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Review</label>
                    <textarea name="review_text" class="form-control" rows="4" required></textarea>
                </div>

                <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
            </form>
        </div>

        <div class="mt-5">
            <h4>Approved Reviews</h4>
            <?php
            $reviews = $conn->query("SELECT * FROM reviews WHERE attraction_id = $id AND status = 'approved' ORDER BY created_at DESC");

            if ($reviews->num_rows > 0) {
                while ($review = $reviews->fetch_assoc()) {
                    echo "<div class='card mb-3'>";
                    echo "<div class='card-body'>";
                    echo "<h6>Rating: " . htmlspecialchars($review['rating']) . "/5</h6>";
                    echo "<p>" . htmlspecialchars($review['review_text']) . "</p>";
                    echo "<small class='text-muted'>" . htmlspecialchars($review['created_at']) . "</small>";
                    echo "</div></div>";
                }
            } else {
                echo "<p>No approved reviews yet.</p>";
            }
            ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
