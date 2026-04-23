<?php
include 'db.php';
include 'navbar.php';

$homeSampleMapPlaces = array_slice(require __DIR__ . '/sample-map-places.php', 0, 4);
$heroSlides = [
    [
        'image' => 'images/addalaichenai_beach.jpg',
        'title' => 'Addalaichenai Beach',
        'caption' => 'Calm coastal views and open skies for a refreshing start to the journey.'
    ],
    [
        'image' => 'images/oluvil_harbour.jpg',
        'title' => 'Oluvil Harbour',
        'caption' => 'A scenic waterfront atmosphere that adds movement and character to the region.'
    ],
    [
        'image' => 'images/pottuvil_lagoon.jpg',
        'title' => 'Pottuvil Lagoon',
        'caption' => 'Soft natural landscapes that bring a more serene and polished tourism feel.'
    ],
    [
        'image' => 'images/buddhangala_monastery.jpg',
        'title' => 'Buddhangala Monastery',
        'caption' => 'Historic surroundings that reflect the cultural depth behind local travel experiences.'
    ]
];

$featuredSql = "SELECT attractions.attraction_id, attractions.name, attractions.image, attractions.short_description, categories.name AS category_name
                FROM attractions
                LEFT JOIN categories ON attractions.category_id = categories.category_id
                ORDER BY attractions.attraction_id DESC
                LIMIT 3";
$featuredResult = $conn->query($featuredSql);
?>

<section class="hero hero-cover" data-aos="fade-up">
    <div id="heroSlideshow" class="carousel slide carousel-fade hero-background-carousel" data-bs-ride="carousel" data-bs-interval="3600">
        <div class="carousel-indicators hero-carousel-indicators">
            <?php foreach ($heroSlides as $index => $slide) { ?>
                <button type="button"
                        data-bs-target="#heroSlideshow"
                        data-bs-slide-to="<?php echo $index; ?>"
                        class="<?php echo $index === 0 ? 'active' : ''; ?>"
                        <?php echo $index === 0 ? 'aria-current="true"' : ''; ?>
                        aria-label="Slide <?php echo $index + 1; ?>"></button>
            <?php } ?>
        </div>
        <div class="carousel-inner hero-carousel-inner">
            <?php foreach ($heroSlides as $index => $slide) { ?>
                <div class="carousel-item<?php echo $index === 0 ? ' active' : ''; ?>">
                    <img src="<?php echo htmlspecialchars($slide['image'], ENT_QUOTES, 'UTF-8'); ?>"
                         class="d-block w-100 hero-carousel-image"
                         alt="<?php echo htmlspecialchars($slide['title'], ENT_QUOTES, 'UTF-8'); ?>"
                         loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>">
                    <div class="hero-carousel-layer"></div>
                </div>
            <?php } ?>
        </div>
    </div>
    <div class="container hero-content">
        <div class="hero-story hero-story-cover">
            <span class="badge-soft hero-badge">Smart Tourist Guide</span>
            <h1 class="mt-3">Discover Addalaichenai through a more immersive first impression.</h1>
            <p class="lead">Browse attractions, open the live map, and plan a polished one-day experience from a homepage that feels closer to a real tourism brand.</p>
            <div class="hero-actions">
                <a href="places.php" class="btn btn-primary btn-lg me-2"><i class="bi bi-compass me-2"></i>Explore Attractions</a>
                <a href="planner.php" class="btn btn-success btn-lg me-2"><i class="bi bi-signpost-split me-2"></i>Plan a Trip</a>
                <a href="map.php" class="btn btn-outline-light btn-lg"><i class="bi bi-map me-2"></i>Open Live Map</a>
            </div>
        </div>
    </div>
</section>

<div class="container" data-aos="fade-up" data-aos-delay="60">
    <div class="content-box content-box-soft text-center">
        <h2 class="mb-3">Welcome to TripZo</h2>
        <p>
            TripZo is a tourist guide platform designed to help visitors discover attractions,
            explore detailed place information, and plan memorable trips around Addalaichenai.
        </p>
    </div>
</div>

<div class="container section-space home-feature-grid">
    <div class="row g-4">
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="60">
            <div class="content-box content-box-soft h-100 text-center">
                <div class="feature-icon"><i class="bi bi-geo-alt"></i></div>
                <h5 class="mb-2">Discover Places</h5>
                <p class="mb-3">Browse cultural landmarks, beaches, and local attractions in one place.</p>
                <a href="places.php" class="btn btn-outline-primary">Browse Attractions</a>
            </div>
        </div>
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="120">
            <div class="content-box content-box-soft h-100 text-center">
                <div class="feature-icon"><i class="bi bi-calendar2-check"></i></div>
                <h5 class="mb-2">Use Smart Planner</h5>
                <p class="mb-3">Build a practical one-day travel route based on your available time.</p>
                <a href="planner.php" class="btn btn-outline-success">Open Planner</a>
            </div>
        </div>
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="180">
            <div class="content-box content-box-soft h-100 text-center">
                <div class="feature-icon"><i class="bi bi-map"></i></div>
                <h5 class="mb-2">Explore on Map</h5>
                <p class="mb-3">Preview locations visually before visiting and understand route context.</p>
                <a href="map.php" class="btn btn-outline-dark">View Map</a>
            </div>
        </div>
    </div>
</div>

<div class="container section-space" data-aos="fade-up">
    <div class="row align-items-stretch g-4">
        <div class="col-lg-5 d-flex flex-column justify-content-center">
            <h2 class="mb-3">Map preview</h2>
            <p class="text-muted mb-3">
                Explore a built-in sample map with placeholder pins and short descriptions. It uses OpenStreetMap tiles only — no Google Maps API.
                The full map page includes a larger view and a details panel for each stop.
            </p>
            <a href="map.php" class="btn btn-primary align-self-start"><i class="bi bi-map me-2"></i>Open full map</a>
        </div>
        <div class="col-lg-7">
            <div class="home-sample-map-wrap content-box content-box-soft p-2 p-md-3">
                <div id="home-sample-map" class="tripzo-map tripzo-map-home-sample" role="img" aria-label="Sample map of the region"></div>
                <p class="small text-muted mb-0 mt-2 px-1">Demo markers for presentation; replace with live data from your admin panel.</p>
            </div>
        </div>
    </div>
</div>

<div class="container section-space home-featured-section">
    <div class="page-header text-center">
        <h2>Featured Attractions</h2>
        <p>Start with popular places travelers usually visit first.</p>
    </div>
    <div class="row">
        <?php if ($featuredResult && $featuredResult->num_rows > 0) { ?>
            <?php while ($place = $featuredResult->fetch_assoc()) { ?>
                <div class="col-md-4 mb-4" data-aos="zoom-in-up">
                    <div class="card h-100">
                        <img src="images/<?php echo htmlspecialchars($place['image']); ?>"
                             class="card-img-top"
                             alt="<?php echo htmlspecialchars($place['name']); ?>"
                             loading="lazy"
                             onerror="this.onerror=null;this.src='https://placehold.co/800x500?text=No+Image';">
                        <div class="card-body d-flex flex-column">
                            <span class="badge-soft mb-2"><?php echo htmlspecialchars($place['category_name'] ?? 'Featured'); ?></span>
                            <h5 class="card-title"><?php echo htmlspecialchars($place['name']); ?></h5>
                            <p class="card-text line-clamp-3"><?php echo htmlspecialchars($place['short_description']); ?></p>
                            <div class="mt-auto">
                                <a href="place-details.php?id=<?php echo (int) $place['attraction_id']; ?>" class="btn btn-primary w-100">View Details &rarr;</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="col-12">
                <div class="content-box text-center py-5">
                    <h5 class="mb-2">No featured attractions yet</h5>
                    <p class="mb-0">Add attractions from the admin panel to show them here.</p>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var mapEl = document.getElementById('home-sample-map');
    if (typeof L === 'undefined' || !mapEl) {
        return;
    }

    var places = <?php echo json_encode($homeSampleMapPlaces, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    if (!places.length) {
        return;
    }

    var map = L.map(mapEl, { zoomControl: true, scrollWheelZoom: false });
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap &copy; CARTO',
        maxZoom: 19
    }).addTo(map);

    var bounds = L.latLngBounds([]);
    function escHtml(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    places.forEach(function (place, index) {
        var latLng = [place.latitude, place.longitude];
        bounds.extend(latLng);
        var popupHtml = '<strong>' + escHtml(place.name) + '</strong><p class="mb-0 small">' +
            escHtml(place.short_description) + '</p>' +
            '<a class="btn btn-sm btn-primary mt-2" href="map.php">View on map page</a>';
        L.marker(latLng).addTo(map).bindPopup(popupHtml);
    });

    if (bounds.isValid()) {
        map.fitBounds(bounds, { padding: [20, 20] });
    }

    setTimeout(function () {
        map.invalidateSize();
        if (bounds.isValid()) {
            map.fitBounds(bounds, { padding: [20, 20] });
        }
    }, 250);
});
</script>

<?php include 'footer.php'; ?>
