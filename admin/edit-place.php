<?php
include __DIR__ . '/admin-common.php';

if (!isset($_GET['id'])) {
    die('No attraction selected.');
}

$id = (int) $_GET['id'];
$message = '';
$messageType = 'info';

$placeStmt = $conn->prepare("SELECT * FROM attractions WHERE attraction_id = ? LIMIT 1");
$placeStmt->bind_param('i', $id);
$placeStmt->execute();
$attraction = $placeStmt->get_result()->fetch_assoc();

if (!$attraction) {
    die('Attraction not found.');
}

$galleryStmt = $conn->prepare("SELECT image_id, image_path FROM attraction_images WHERE attraction_id = ? ORDER BY sort_order ASC, image_id ASC");
$galleryStmt->bind_param('i', $id);
$galleryStmt->execute();
$galleryImages = $galleryStmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (!empty($attraction['image'])) {
    $hasPrimaryInGallery = false;
    foreach ($galleryImages as $existingGalleryImage) {
        if ($existingGalleryImage['image_path'] === $attraction['image']) {
            $hasPrimaryInGallery = true;
            break;
        }
    }
    if (!$hasPrimaryInGallery) {
        array_unshift($galleryImages, [
            'image_id' => 0,
            'image_path' => $attraction['image']
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [];
    $fields = [
        'name', 'category_id', 'short_description', 'description', 'address', 'area_name', 'district', 'province',
        'postal_code', 'latitude', 'longitude', 'google_maps_url', 'opening_hours', 'contact_info',
        'nearby_landmarks', 'transport_options', 'best_time_to_visit', 'entry_fee', 'accessibility_info',
        'safety_notes', 'rating', 'distance_km'
    ];

    foreach ($fields as $field) {
        $formData[$field] = trim($_POST[$field] ?? '');
    }

    $selectedCoverPath = trim($_POST['cover_image_path'] ?? '');
    $newCoverIndex = max(0, (int) ($_POST['new_cover_image_index'] ?? 0));

    if ($formData['google_maps_url'] !== '') {
        $mapsData = adminExtractGoogleMapsData($formData['google_maps_url']);
        if ($formData['latitude'] === '' && $mapsData['latitude'] !== '') {
            $formData['latitude'] = $mapsData['latitude'];
        }
        if ($formData['longitude'] === '' && $mapsData['longitude'] !== '') {
            $formData['longitude'] = $mapsData['longitude'];
        }
        if ($formData['name'] === '' && $mapsData['name'] !== '') {
            $formData['name'] = $mapsData['name'];
        }
    }

    $currentImage = trim($_POST['current_image'] ?? '');

    $removalIds = [];
    $removedPaths = [];
    if ($message === '' && !empty($_POST['remove_gallery_images']) && is_array($_POST['remove_gallery_images'])) {
        $removalIds = array_values(array_filter(array_map('intval', $_POST['remove_gallery_images'])));
        $galleryPathMap = [];
        foreach ($galleryImages as $existingGalleryImage) {
            $galleryPathMap[(int) $existingGalleryImage['image_id']] = $existingGalleryImage['image_path'];
        }
        foreach ($removalIds as $removalId) {
            if (isset($galleryPathMap[$removalId])) {
                $removedPaths[] = $galleryPathMap[$removalId];
            }
        }
        adminDeleteGalleryImages($conn, $removalIds);
    }

    $galleryUpload = ['filenames' => [], 'error' => ''];
    if ($message === '') {
        $galleryUpload = adminHandleMultipleImageUploads('attraction_photos');
        if ($galleryUpload['error'] !== '') {
            $message = $galleryUpload['error'];
            $messageType = 'danger';
        }
    }

    if ($message === '' && ($formData['name'] === '' || $formData['category_id'] === '')) {
        $message = 'Name and category are required.';
        $messageType = 'danger';
    } elseif ($message === '' && $formData['latitude'] !== '' && !is_numeric($formData['latitude'])) {
        $message = 'Latitude must be a valid number.';
        $messageType = 'danger';
    } elseif ($message === '' && $formData['longitude'] !== '' && !is_numeric($formData['longitude'])) {
        $message = 'Longitude must be a valid number.';
        $messageType = 'danger';
    } elseif ($message === '') {
        $stmt = $conn->prepare(
            "UPDATE attractions SET
                category_id = ?,
                name = ?,
                short_description = ?,
                description = ?,
                address = ?,
                area_name = ?,
                district = ?,
                province = ?,
                postal_code = ?,
                latitude = NULLIF(?, ''),
                longitude = NULLIF(?, ''),
                google_maps_url = ?,
                opening_hours = ?,
                contact_info = ?,
                nearby_landmarks = ?,
                transport_options = ?,
                best_time_to_visit = ?,
                entry_fee = ?,
                accessibility_info = ?,
                image = ?,
                safety_notes = ?,
                rating = NULLIF(?, ''),
                distance_km = NULLIF(?, '')
             WHERE attraction_id = ?"
        );

        $categoryId = (int) $formData['category_id'];
        $retainedGalleryPaths = [];
        foreach ($galleryImages as $existingGalleryImage) {
            if (!in_array($existingGalleryImage['image_path'], $removedPaths, true)) {
                $retainedGalleryPaths[] = $existingGalleryImage['image_path'];
            }
        }

        if ($newCoverIndex > 0 && isset($galleryUpload['filenames'][$newCoverIndex - 1])) {
            $primaryImage = $galleryUpload['filenames'][$newCoverIndex - 1];
        } elseif ($selectedCoverPath !== '' && !in_array($selectedCoverPath, $removedPaths, true)) {
            $primaryImage = $selectedCoverPath;
        } elseif ($currentImage !== '' && !in_array($currentImage, $removedPaths, true)) {
            $primaryImage = $currentImage;
        } elseif (!empty($retainedGalleryPaths)) {
            $primaryImage = $retainedGalleryPaths[0];
        } else {
            $primaryImage = $galleryUpload['filenames'][0] ?? '';
        }

        $stmt->bind_param(
            'issssssssssssssssssssssi',
            $categoryId,
            $formData['name'],
            $formData['short_description'],
            $formData['description'],
            $formData['address'],
            $formData['area_name'],
            $formData['district'],
            $formData['province'],
            $formData['postal_code'],
            $formData['latitude'],
            $formData['longitude'],
            $formData['google_maps_url'],
            $formData['opening_hours'],
            $formData['contact_info'],
            $formData['nearby_landmarks'],
            $formData['transport_options'],
            $formData['best_time_to_visit'],
            $formData['entry_fee'],
            $formData['accessibility_info'],
            $primaryImage,
            $formData['safety_notes'],
            $formData['rating'],
            $formData['distance_km'],
            $id
        );

        if ($stmt->execute()) {
            adminInsertGalleryImages($conn, $id, $galleryUpload['filenames']);
            $message = 'Attraction updated successfully.';
            $messageType = 'success';
        } else {
            $message = 'Failed to update attraction. Please try again.';
            $messageType = 'danger';
        }
    }
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Attraction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo adminEscape(adminAssetPath('css/style.css')); ?>">
</head>
<body>

<nav class="navbar navbar-dark navbar-tripzo py-3">
    <div class="container">
        <span class="navbar-brand mb-0 h1">Edit Attraction</span>
        <div class="d-flex gap-2">
            <a href="manage-places.php" class="btn btn-outline-light">Manage Attractions</a>
            <a href="dashboard.php" class="btn btn-outline-light">Dashboard</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="content-box content-box-soft">
        <h2 class="mb-2">Update Attraction Details</h2>
        <p class="mb-4">Refine the place record, improve map precision, and keep visitor information up to date.</p>

        <?php if ($message !== '') { ?>
            <div class="alert alert-<?php echo adminEscape($messageType); ?>"><?php echo adminEscape($message); ?></div>
        <?php } ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="current_image" value="<?php echo adminEscape($attraction['image']); ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo adminEscape($attraction['name']); ?>" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Category</label>
                    <select name="category_id" class="form-select" required>
                        <?php while($cat = $categories->fetch_assoc()) { ?>
                            <option value="<?php echo (int) $cat['category_id']; ?>" <?php echo (int) $cat['category_id'] === (int) $attraction['category_id'] ? 'selected' : ''; ?>>
                                <?php echo adminEscape($cat['name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Short Description</label>
                    <input type="text" name="short_description" class="form-control" value="<?php echo adminEscape($attraction['short_description']); ?>">
                </div>

                <div class="col-12 mb-3">
                    <label>Attraction Photos</label>
                    <input type="file" name="attraction_photos[]" class="form-control" accept=".jpg,.jpeg,.png,.webp" multiple>
                    <div class="form-text">Upload one or more additional photos for this attraction. The current main cover image is kept unless the attraction has no main image yet.</div>
                </div>

                <div class="col-md-4 mb-3">
                    <label>Use New Upload As Cover</label>
                    <select name="new_cover_image_index" class="form-select">
                        <option value="0">Keep current / existing cover</option>
                        <?php for ($i = 1; $i <= 8; $i++) { ?>
                            <option value="<?php echo $i; ?>">Use new photo <?php echo $i; ?> as cover</option>
                        <?php } ?>
                    </select>
                    <div class="form-text">If you upload new photos in this update, you can choose one of them as the new cover image.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Address</label>
                    <input type="text" name="address" class="form-control" value="<?php echo adminEscape($attraction['address']); ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Area / Village</label>
                    <input type="text" name="area_name" class="form-control" value="<?php echo adminEscape($attraction['area_name'] ?? ''); ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label>District</label>
                    <input type="text" name="district" class="form-control" value="<?php echo adminEscape($attraction['district'] ?? ''); ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Province</label>
                    <input type="text" name="province" class="form-control" value="<?php echo adminEscape($attraction['province'] ?? ''); ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Postal Code</label>
                    <input type="text" name="postal_code" class="form-control" value="<?php echo adminEscape($attraction['postal_code'] ?? ''); ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Latitude</label>
                    <input type="text" name="latitude" class="form-control" value="<?php echo adminEscape($attraction['latitude']); ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Longitude</label>
                    <input type="text" name="longitude" class="form-control" value="<?php echo adminEscape($attraction['longitude']); ?>">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Google Maps URL</label>
                    <input type="url" name="google_maps_url" class="form-control" value="<?php echo adminEscape($attraction['google_maps_url'] ?? ''); ?>">
                    <div class="form-text">TripZo can auto-read coordinates from many Google Maps links. Richer map metadata still requires a Places API integration.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Opening Hours</label>
                    <input type="text" name="opening_hours" class="form-control" value="<?php echo adminEscape($attraction['opening_hours']); ?>">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Contact Info</label>
                    <input type="text" name="contact_info" class="form-control" value="<?php echo adminEscape($attraction['contact_info']); ?>">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Safety Notes</label>
                    <input type="text" name="safety_notes" class="form-control" value="<?php echo adminEscape($attraction['safety_notes']); ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Rating</label>
                    <input type="text" name="rating" class="form-control" value="<?php echo adminEscape($attraction['rating']); ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Distance (km)</label>
                    <input type="text" name="distance_km" class="form-control" value="<?php echo adminEscape($attraction['distance_km']); ?>">
                </div>

                <div class="col-12 mb-3">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="4"><?php echo adminEscape($attraction['description']); ?></textarea>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Nearby Landmarks</label>
                    <textarea name="nearby_landmarks" class="form-control" rows="3"><?php echo adminEscape($attraction['nearby_landmarks'] ?? ''); ?></textarea>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Transport Options</label>
                    <textarea name="transport_options" class="form-control" rows="3"><?php echo adminEscape($attraction['transport_options'] ?? ''); ?></textarea>
                </div>

                <div class="col-md-4 mb-3">
                    <label>Best Time to Visit</label>
                    <input type="text" name="best_time_to_visit" class="form-control" value="<?php echo adminEscape($attraction['best_time_to_visit'] ?? ''); ?>">
                </div>

                <div class="col-md-4 mb-3">
                    <label>Entry Fee</label>
                    <input type="text" name="entry_fee" class="form-control" value="<?php echo adminEscape($attraction['entry_fee'] ?? ''); ?>">
                </div>

                <div class="col-md-4 mb-3">
                    <label>Accessibility Info</label>
                    <textarea name="accessibility_info" class="form-control" rows="2"><?php echo adminEscape($attraction['accessibility_info'] ?? ''); ?></textarea>
                </div>

                <?php if (!empty($galleryImages)) { ?>
                    <div class="col-12 mb-3">
                        <label class="mb-3">Existing Gallery</label>
                        <div class="row g-3">
                            <?php foreach ($galleryImages as $galleryImage) { ?>
                                <div class="col-md-3 col-sm-6">
                                    <div class="content-box content-box-soft p-2 h-100">
                                        <img src="../images/<?php echo adminEscape($galleryImage['image_path']); ?>" alt="Gallery image" class="img-fluid rounded mb-2" style="height: 160px; width: 100%; object-fit: cover;">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="cover_image_path" value="<?php echo adminEscape($galleryImage['image_path']); ?>" id="gallery-cover-<?php echo (int) $galleryImage['image_id']; ?>" <?php echo $galleryImage['image_path'] === $attraction['image'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label small" for="gallery-cover-<?php echo (int) $galleryImage['image_id']; ?>">
                                                Use as cover image
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <?php if ((int) $galleryImage['image_id'] !== 0) { ?>
                                                <input class="form-check-input" type="checkbox" name="remove_gallery_images[]" value="<?php echo (int) $galleryImage['image_id']; ?>" id="gallery-remove-<?php echo (int) $galleryImage['image_id']; ?>">
                                                <label class="form-check-label small" for="gallery-remove-<?php echo (int) $galleryImage['image_id']; ?>">
                                                    Remove this image
                                                </label>
                                            <?php } else { ?>
                                                <input class="form-check-input" type="checkbox" disabled id="gallery-remove-main-image">
                                                <label class="form-check-label small" for="gallery-remove-main-image">
                                                    Primary image is managed as cover
                                                </label>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <button type="submit" class="btn btn-success">Update Attraction</button>
            <a href="manage-places.php" class="btn btn-secondary">Back</a>
        </form>
    </div>
</div>

</body>
</html>
