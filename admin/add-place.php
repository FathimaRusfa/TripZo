<?php
include __DIR__ . '/admin-common.php';

$message = '';
$messageType = 'info';
$formData = [
    'name' => '',
    'category_id' => '',
    'short_description' => '',
    'description' => '',
    'address' => '',
    'area_name' => '',
    'district' => '',
    'province' => '',
    'postal_code' => '',
    'latitude' => '',
    'longitude' => '',
    'google_maps_url' => '',
    'opening_hours' => '',
    'contact_info' => '',
    'nearby_landmarks' => '',
    'transport_options' => '',
    'best_time_to_visit' => '',
    'entry_fee' => '',
    'accessibility_info' => '',
    'cover_image_index' => '1',
    'safety_notes' => '',
    'rating' => '',
    'distance_km' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($formData as $key => $value) {
        $formData[$key] = trim($_POST[$key] ?? '');
    }

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
            "INSERT INTO attractions (
                category_id, name, short_description, description, address, area_name, district, province, postal_code,
                latitude, longitude, google_maps_url, opening_hours, contact_info, nearby_landmarks, transport_options,
                best_time_to_visit, entry_fee, accessibility_info, image, safety_notes, rating, distance_km
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''))"
        );

        $categoryId = (int) $formData['category_id'];
        $coverImageIndex = max(1, (int) ($formData['cover_image_index'] !== '' ? $formData['cover_image_index'] : '1'));
        $primaryImage = $galleryUpload['filenames'][$coverImageIndex - 1] ?? ($galleryUpload['filenames'][0] ?? '');

        $stmt->bind_param(
            'issssssssssssssssssssss',
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
            $formData['distance_km']
        );

        if ($stmt->execute()) {
            $newAttractionId = (int) $conn->insert_id;
            adminInsertGalleryImages($conn, $newAttractionId, $galleryUpload['filenames']);
            $message = 'Attraction added successfully.';
            $messageType = 'success';
            foreach ($formData as $key => $value) {
                $formData[$key] = '';
            }
        } else {
            $message = 'Failed to add attraction. Please try again.';
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
    <title>Add Attraction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo adminEscape(adminAssetPath('css/style.css')); ?>">
</head>
<body>

<nav class="navbar navbar-dark navbar-tripzo py-3">
    <div class="container">
        <span class="navbar-brand mb-0 h1">Add Attraction</span>
        <div class="d-flex gap-2">
            <a href="manage-places.php" class="btn btn-outline-light">Manage Attractions</a>
            <a href="dashboard.php" class="btn btn-outline-light">Dashboard</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="content-box content-box-soft">
        <h2 class="mb-2">Create a New Attraction</h2>
        <p class="mb-4">Capture accurate visitor details, map data, and travel notes so the public site feels complete.</p>

        <?php if ($message !== '') { ?>
            <div class="alert alert-<?php echo adminEscape($messageType); ?>"><?php echo adminEscape($message); ?></div>
        <?php } ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" required value="<?php echo adminEscape($formData['name']); ?>">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Category</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Select Category</option>
                        <?php while($cat = $categories->fetch_assoc()) { ?>
                            <option value="<?php echo (int) $cat['category_id']; ?>" <?php echo (string) $cat['category_id'] === $formData['category_id'] ? 'selected' : ''; ?>>
                                <?php echo adminEscape($cat['name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Short Description</label>
                    <input type="text" name="short_description" class="form-control" value="<?php echo adminEscape($formData['short_description']); ?>">
                </div>

                <div class="col-12 mb-3">
                    <label>Attraction Photos</label>
                    <input type="file" name="attraction_photos[]" class="form-control" accept=".jpg,.jpeg,.png,.webp" multiple>
                    <div class="form-text">Upload one or more photos. The first photo becomes the main cover image and all uploaded photos appear in the attraction slideshow.</div>
                </div>

                <div class="col-md-4 mb-3">
                    <label>Cover Photo Position</label>
                    <select name="cover_image_index" class="form-select">
                        <?php for ($i = 1; $i <= 8; $i++) { ?>
                            <option value="<?php echo $i; ?>" <?php echo $formData['cover_image_index'] === (string) $i ? 'selected' : ''; ?>>
                                Photo <?php echo $i; ?>
                            </option>
                        <?php } ?>
                    </select>
                    <div class="form-text">Choose which uploaded photo should be used as the cover image. Photo 1 means the first selected file.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Address</label>
                    <input type="text" name="address" class="form-control" value="<?php echo adminEscape($formData['address']); ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Area / Village</label>
                    <input type="text" name="area_name" class="form-control" value="<?php echo adminEscape($formData['area_name']); ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label>District</label>
                    <input type="text" name="district" class="form-control" value="<?php echo adminEscape($formData['district']); ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Province</label>
                    <input type="text" name="province" class="form-control" value="<?php echo adminEscape($formData['province']); ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Postal Code</label>
                    <input type="text" name="postal_code" class="form-control" value="<?php echo adminEscape($formData['postal_code']); ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Latitude</label>
                    <input type="text" name="latitude" class="form-control" value="<?php echo adminEscape($formData['latitude']); ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Longitude</label>
                    <input type="text" name="longitude" class="form-control" value="<?php echo adminEscape($formData['longitude']); ?>">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Google Maps URL</label>
                    <input type="url" name="google_maps_url" class="form-control" value="<?php echo adminEscape($formData['google_maps_url']); ?>" placeholder="https://maps.google.com/...">
                    <div class="form-text">If the link contains coordinates, TripZo will auto-fill latitude and longitude. Full Google Maps data import still needs a Maps/Places API service.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Opening Hours</label>
                    <input type="text" name="opening_hours" class="form-control" value="<?php echo adminEscape($formData['opening_hours']); ?>">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Contact Info</label>
                    <input type="text" name="contact_info" class="form-control" value="<?php echo adminEscape($formData['contact_info']); ?>">
                </div>

                <div class="col-md-6 mb-3">
                    <label>Safety Notes</label>
                    <input type="text" name="safety_notes" class="form-control" value="<?php echo adminEscape($formData['safety_notes']); ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Rating</label>
                    <input type="text" name="rating" class="form-control" value="<?php echo adminEscape($formData['rating']); ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Distance (km)</label>
                    <input type="text" name="distance_km" class="form-control" value="<?php echo adminEscape($formData['distance_km']); ?>">
                </div>

                <div class="col-12 mb-3">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="4"><?php echo adminEscape($formData['description']); ?></textarea>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Nearby Landmarks</label>
                    <textarea name="nearby_landmarks" class="form-control" rows="3"><?php echo adminEscape($formData['nearby_landmarks']); ?></textarea>
                </div>

                <div class="col-md-6 mb-3">
                    <label>Transport Options</label>
                    <textarea name="transport_options" class="form-control" rows="3"><?php echo adminEscape($formData['transport_options']); ?></textarea>
                </div>

                <div class="col-md-4 mb-3">
                    <label>Best Time to Visit</label>
                    <input type="text" name="best_time_to_visit" class="form-control" value="<?php echo adminEscape($formData['best_time_to_visit']); ?>">
                </div>

                <div class="col-md-4 mb-3">
                    <label>Entry Fee</label>
                    <input type="text" name="entry_fee" class="form-control" value="<?php echo adminEscape($formData['entry_fee']); ?>" placeholder="Free / LKR 500">
                </div>

                <div class="col-md-4 mb-3">
                    <label>Accessibility Info</label>
                    <textarea name="accessibility_info" class="form-control" rows="2"><?php echo adminEscape($formData['accessibility_info']); ?></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-success">Add Attraction</button>
            <a href="dashboard.php" class="btn btn-secondary">Back</a>
        </form>
    </div>
</div>

</body>
</html>
