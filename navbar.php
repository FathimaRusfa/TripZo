<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
function isActiveNav($pageName, $currentPage) {
    return $pageName === $currentPage ? ' active' : '';
}

$isUserLoggedIn = isset($_SESSION['user_id']);
$userDisplayName = $isUserLoggedIn ? (string) ($_SESSION['user_name'] ?? 'User') : '';
$isAdminLoggedIn = isset($_SESSION['admin_id']);
$adminDisplayName = $isAdminLoggedIn ? (string) ($_SESSION['admin_name'] ?? 'Admin') : '';

$styleHref = 'css/style.css';
$leafletCssHref = 'vendor/leaflet/leaflet.css';
$markerClusterCssHref = 'vendor/leaflet-markercluster/MarkerCluster.css';
$markerClusterDefaultCssHref = 'vendor/leaflet-markercluster/MarkerCluster.Default.css';
$leafletJsSrc = 'vendor/leaflet/leaflet.js';
$markerClusterJsSrc = 'vendor/leaflet-markercluster/leaflet.markercluster.js';

if (file_exists(__DIR__ . '/css/style.css')) {
    $styleHref .= '?v=' . filemtime(__DIR__ . '/css/style.css');
}

if (file_exists(__DIR__ . '/vendor/leaflet/leaflet.css')) {
    $leafletCssHref .= '?v=' . filemtime(__DIR__ . '/vendor/leaflet/leaflet.css');
}

if (file_exists(__DIR__ . '/vendor/leaflet-markercluster/MarkerCluster.css')) {
    $markerClusterCssHref .= '?v=' . filemtime(__DIR__ . '/vendor/leaflet-markercluster/MarkerCluster.css');
}

if (file_exists(__DIR__ . '/vendor/leaflet-markercluster/MarkerCluster.Default.css')) {
    $markerClusterDefaultCssHref .= '?v=' . filemtime(__DIR__ . '/vendor/leaflet-markercluster/MarkerCluster.Default.css');
}

if (file_exists(__DIR__ . '/vendor/leaflet/leaflet.js')) {
    $leafletJsSrc .= '?v=' . filemtime(__DIR__ . '/vendor/leaflet/leaflet.js');
}

if (file_exists(__DIR__ . '/vendor/leaflet-markercluster/leaflet.markercluster.js')) {
    $markerClusterJsSrc .= '?v=' . filemtime(__DIR__ . '/vendor/leaflet-markercluster/leaflet.markercluster.js');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TripZo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($leafletCssHref, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($markerClusterCssHref, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($markerClusterDefaultCssHref, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($styleHref, ENT_QUOTES, 'UTF-8'); ?>">
    <script src="<?php echo htmlspecialchars($leafletJsSrc, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
    <script src="<?php echo htmlspecialchars($markerClusterJsSrc, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-tripzo sticky-top py-3">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-3" href="index.php" aria-label="TripZo Home">
            <span class="tripzo-brand-mark">
                <i class="bi bi-compass-fill"></i>
            </span>
            <span class="d-flex flex-column">
                <span class="tripzo-brand-name">TripZo</span>
                <small class="tripzo-brand-tagline">Curated journeys across Addalaichenai</small>
            </span>
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2 tripzo-nav-links">
                <li class="nav-item"><a class="nav-link<?php echo isActiveNav('index.php', $currentPage); ?>" href="index.php"><i class="bi bi-house-door me-2"></i>Home</a></li>
                <li class="nav-item"><a class="nav-link<?php echo isActiveNav('places.php', $currentPage); ?>" href="places.php"><i class="bi bi-stars me-2"></i>Places</a></li>
                <li class="nav-item"><a class="nav-link<?php echo isActiveNav('map.php', $currentPage); ?>" href="map.php"><i class="bi bi-map me-2"></i>Map</a></li>
                <li class="nav-item"><a class="nav-link<?php echo isActiveNav('planner.php', $currentPage); ?>" href="planner.php"><i class="bi bi-calendar2-check me-2"></i>Planner</a></li>
                <?php if ($isUserLoggedIn) { ?>
                    <li class="nav-item"><a class="btn btn-tripzo-secondary ms-lg-2" href="my-trips.php"><i class="bi bi-journal-bookmark me-2"></i>My Trips</a></li>
                    <li class="nav-item"><span class="navbar-text tripzo-user-chip ms-lg-2"><i class="bi bi-person-circle me-2"></i><?php echo htmlspecialchars($userDisplayName); ?></span></li>
                    <li class="nav-item"><a class="btn btn-outline-light ms-lg-2" href="user-logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                <?php } elseif ($isAdminLoggedIn) { ?>
                    <li class="nav-item"><a class="btn btn-tripzo-secondary ms-lg-2" href="admin/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Admin Panel</a></li>
                    <li class="nav-item"><span class="navbar-text tripzo-user-chip ms-lg-2"><i class="bi bi-shield-lock me-2"></i><?php echo htmlspecialchars($adminDisplayName); ?></span></li>
                    <li class="nav-item"><a class="btn btn-outline-light ms-lg-2" href="admin/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                <?php } else { ?>
                    <li class="nav-item"><a class="btn btn-tripzo-login ms-lg-3" href="user-login.php"><i class="bi bi-person-badge me-2"></i>Login</a></li>
                <?php } ?>
            </ul>
        </div>
    </div>
</nav>
