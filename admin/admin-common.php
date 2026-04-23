<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../user-login.php?mode=admin');
    exit;
}

include __DIR__ . '/../db.php';

function adminEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function adminAssetPath($relativePath)
{
    $fullPath = dirname(__DIR__) . '/' . ltrim($relativePath, '/');
    $version = file_exists($fullPath) ? ('?v=' . filemtime($fullPath)) : '';
    return '../' . ltrim($relativePath, '/') . $version;
}

function adminEnsureUploadDirectory()
{
    $directory = dirname(__DIR__) . '/images/uploads';

    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }

    return $directory;
}

function adminHandleImageUpload($fieldName, $currentImage = '')
{
    if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        return ['filename' => $currentImage, 'error' => ''];
    }

    $file = $_FILES[$fieldName];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['filename' => $currentImage, 'error' => ''];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['filename' => $currentImage, 'error' => 'Image upload failed. Please try again.'];
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        return ['filename' => $currentImage, 'error' => 'Image must be smaller than 5 MB.'];
    }

    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($extension, $allowedExtensions, true)) {
        return ['filename' => $currentImage, 'error' => 'Only JPG, JPEG, PNG, and WEBP files are allowed.'];
    }

    $uploadDir = adminEnsureUploadDirectory();
    $safeBase = preg_replace('/[^a-z0-9]+/i', '-', pathinfo((string) $file['name'], PATHINFO_FILENAME));
    $safeBase = trim((string) $safeBase, '-');
    $safeBase = $safeBase !== '' ? strtolower($safeBase) : 'attraction';
    $fileName = 'uploads/' . $safeBase . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $extension;
    $targetPath = dirname(__DIR__) . '/images/' . $fileName;

    if (!move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
        return ['filename' => $currentImage, 'error' => 'Failed to save the uploaded image.'];
    }

    return ['filename' => $fileName, 'error' => ''];
}

function adminHandleMultipleImageUploads($fieldName)
{
    $result = ['filenames' => [], 'error' => ''];

    if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName]['name'] ?? null)) {
        return $result;
    }

    $names = $_FILES[$fieldName]['name'];
    $tmpNames = $_FILES[$fieldName]['tmp_name'];
    $errors = $_FILES[$fieldName]['error'];
    $sizes = $_FILES[$fieldName]['size'];

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    adminEnsureUploadDirectory();

    for ($i = 0; $i < count($names); $i++) {
        if (($errors[$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if (($errors[$i] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $result['error'] = 'One of the gallery images failed to upload.';
            return $result;
        }

        if (($sizes[$i] ?? 0) > 5 * 1024 * 1024) {
            $result['error'] = 'Each gallery image must be smaller than 5 MB.';
            return $result;
        }

        $extension = strtolower(pathinfo((string) $names[$i], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            $result['error'] = 'Gallery images must be JPG, JPEG, PNG, or WEBP.';
            return $result;
        }

        $safeBase = preg_replace('/[^a-z0-9]+/i', '-', pathinfo((string) $names[$i], PATHINFO_FILENAME));
        $safeBase = trim((string) $safeBase, '-');
        $safeBase = $safeBase !== '' ? strtolower($safeBase) : 'gallery';
        $fileName = 'uploads/' . $safeBase . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $extension;
        $targetPath = dirname(__DIR__) . '/images/' . $fileName;

        if (!move_uploaded_file((string) $tmpNames[$i], $targetPath)) {
            $result['error'] = 'Failed to save one of the gallery images.';
            return $result;
        }

        $result['filenames'][] = $fileName;
    }

    return $result;
}

function adminInsertGalleryImages(mysqli $conn, $attractionId, array $fileNames)
{
    if (empty($fileNames)) {
        return;
    }

    $existingCount = 0;
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM attraction_images WHERE attraction_id = ?");
    $countStmt->bind_param('i', $attractionId);
    $countStmt->execute();
    $countResult = $countStmt->get_result()->fetch_assoc();
    if ($countResult) {
        $existingCount = (int) $countResult['total'];
    }

    $insertStmt = $conn->prepare("INSERT INTO attraction_images (attraction_id, image_path, sort_order) VALUES (?, ?, ?)");
    foreach ($fileNames as $offset => $fileName) {
        $sortOrder = $existingCount + $offset;
        $insertStmt->bind_param('isi', $attractionId, $fileName, $sortOrder);
        $insertStmt->execute();
    }
}

function adminDeleteGalleryImages(mysqli $conn, array $imageIds)
{
    if (empty($imageIds)) {
        return;
    }

    $normalizedIds = array_values(array_filter(array_map('intval', $imageIds)));
    if (empty($normalizedIds)) {
        return;
    }

    $placeholders = implode(',', array_fill(0, count($normalizedIds), '?'));
    $types = str_repeat('i', count($normalizedIds));

    $selectStmt = $conn->prepare("SELECT image_path FROM attraction_images WHERE image_id IN ($placeholders)");
    $selectStmt->bind_param($types, ...$normalizedIds);
    $selectStmt->execute();
    $result = $selectStmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $path = dirname(__DIR__) . '/images/' . $row['image_path'];
        if (is_file($path) && str_contains(str_replace('\\', '/', $row['image_path']), 'uploads/')) {
            @unlink($path);
        }
    }

    $deleteStmt = $conn->prepare("DELETE FROM attraction_images WHERE image_id IN ($placeholders)");
    $deleteStmt->bind_param($types, ...$normalizedIds);
    $deleteStmt->execute();
}

function adminExtractGoogleMapsData($url)
{
    $data = [
        'google_maps_url' => trim($url),
        'name' => '',
        'latitude' => '',
        'longitude' => ''
    ];

    if ($data['google_maps_url'] === '') {
        return $data;
    }

    $decoded = urldecode($data['google_maps_url']);

    if (preg_match('/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', $decoded, $matches)) {
        $data['latitude'] = $matches[1];
        $data['longitude'] = $matches[2];
    } elseif (preg_match('/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/', $decoded, $matches)) {
        $data['latitude'] = $matches[1];
        $data['longitude'] = $matches[2];
    } else {
        $parts = parse_url($data['google_maps_url']);
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
            if (!empty($query['q']) && preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/', $query['q'], $matches)) {
                $data['latitude'] = $matches[1];
                $data['longitude'] = $matches[2];
            }
        }
    }

    if (preg_match('#/place/([^/@]+)#', $decoded, $matches)) {
        $name = str_replace('+', ' ', $matches[1]);
        $name = preg_replace('/\s+/', ' ', $name);
        $data['name'] = trim((string) $name);
    }

    return $data;
}

ensureTripzoSchema($conn);
