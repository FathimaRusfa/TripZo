<?php
include 'db.php';
include 'navbar.php';

$focusPlaceId = isset($_GET['focus']) ? (int) $_GET['focus'] : 0;

$result = $conn->query("SELECT attractions.attraction_id, attractions.name, attractions.address, attractions.area_name, attractions.district, attractions.province, attractions.latitude, attractions.longitude, attractions.short_description, attractions.description, attractions.image, attractions.opening_hours, attractions.contact_info, attractions.distance_km, attractions.best_time_to_visit, attractions.entry_fee, attractions.google_maps_url, categories.name AS category_name
                        FROM attractions
                        LEFT JOIN categories ON attractions.category_id = categories.category_id
                        ORDER BY attractions.name ASC");

$mapPlaces = [];
$missingCoordinates = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $latitude = isset($row['latitude']) ? trim((string) $row['latitude']) : '';
        $longitude = isset($row['longitude']) ? trim((string) $row['longitude']) : '';

        if ($latitude === '' || $longitude === '' || !is_numeric($latitude) || !is_numeric($longitude)) {
            $missingCoordinates[] = $row['name'];
            continue;
        }

        $mapPlaces[] = [
            'id' => (int) $row['attraction_id'],
            'name' => $row['name'],
            'address' => $row['address'],
            'area_name' => $row['area_name'],
            'district' => $row['district'],
            'province' => $row['province'],
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
            'short_description' => $row['short_description'],
            'description' => $row['description'],
            'image' => $row['image'],
            'category_name' => $row['category_name'] ?? 'Attraction',
            'opening_hours' => $row['opening_hours'],
            'contact_info' => $row['contact_info'],
            'distance_km' => $row['distance_km'],
            'best_time_to_visit' => $row['best_time_to_visit'],
            'entry_fee' => $row['entry_fee'],
            'google_maps_url' => $row['google_maps_url']
        ];
    }
}
?>

<div class="container mt-5">
    <div class="page-header text-center mb-4" data-aos="fade-up">
        <h2>Attraction Map</h2>
        <p>Explore all mapped attractions in Addalaichenai, preview key details, and open the full attraction page directly from the map.</p>
    </div>

    <?php if (!empty($missingCoordinates)) { ?>
        <div class="alert alert-warning mb-4" data-aos="fade-up">
            These attractions still need coordinates: <?php echo htmlspecialchars(implode(', ', $missingCoordinates)); ?>.
        </div>
    <?php } ?>

    <?php if (!empty($mapPlaces)) { ?>
        <div class="map-showcase" data-aos="fade-up">
            <div class="map-showcase-toolbar">
                <div class="map-export-pill">
                    <i class="bi bi-geo-alt-fill"></i>
                    <span><?php echo count($mapPlaces); ?> places mapped</span>
                    <span class="map-pro-badge">Live</span>
                </div>
                <button type="button" class="map-layer-button" id="map-reset-view" aria-label="Reset map view">
                    <i class="bi bi-bullseye"></i>
                </button>
            </div>

            <div id="tripzo-map" class="tripzo-map-full"></div>
            <div id="map-debug-status" class="map-debug-status" hidden>Loading map...</div>

            <div class="map-floating-controls">
                <div class="map-counter" id="map-counter"></div>
                <div class="map-floating-actions">
                    <button type="button" class="map-nav-button" id="map-prev" aria-label="Previous attraction">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button type="button" class="map-nav-button" id="map-next" aria-label="Next attraction">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                    <button type="button" class="map-zoom-chip" id="map-focus" aria-label="Focus selected attraction">
                        <i class="bi bi-crosshair"></i>
                    </button>
                </div>
            </div>

            <div class="map-bottom-sheet">
                <div class="map-sheet-tabs">
                    <button type="button" class="map-tab active" data-map-tab="about">About</button>
                    <button type="button" class="map-tab" data-map-tab="photos">Photos</button>
                    <button type="button" class="map-tab" data-map-tab="visit">Visit Info</button>
                </div>
                <div id="map-sheet-content"></div>
            </div>
        </div>
    <?php } else { ?>
        <div class="content-box text-center py-5">
            <h5 class="mb-2">No map data available</h5>
            <p class="mb-0">Add latitude and longitude values to attractions from the admin panel to show them here.</p>
        </div>
    <?php } ?>
</div>

<?php if (!empty($mapPlaces)) { ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function initTripzoMap() {
        var debugStatus = document.getElementById('map-debug-status');

        function showStatus(message, type) {
            if (!debugStatus) {
                return;
            }
            debugStatus.hidden = false;
            debugStatus.textContent = message;
            debugStatus.className = 'map-debug-status';
            if (type) {
                debugStatus.classList.add(type);
            }
        }

        function clearStatus() {
            if (!debugStatus) {
                return;
            }
            debugStatus.hidden = true;
            debugStatus.textContent = '';
            debugStatus.className = 'map-debug-status';
        }

        if (typeof L === 'undefined') {
            var mapEl = document.getElementById('tripzo-map');
            if (mapEl) {
                mapEl.innerHTML = '<div class="alert alert-warning m-3 mb-0" role="alert">Map library failed to load. Refresh the page and try again.</div>';
            }
            showStatus('Map library failed to load.', 'is-error');
            return;
        }

        var places = <?php echo json_encode($mapPlaces, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
        var focusPlaceId = <?php echo json_encode($focusPlaceId); ?>;
        var currentIndex = 0;
        var activeTab = 'about';
        var markers = [];
        var bounds = L.latLngBounds([]);
        var map = L.map('tripzo-map', { zoomControl: false });
        var counter = document.getElementById('map-counter');
        var sheetContent = document.getElementById('map-sheet-content');
        var defaultView = null;
        var tilesLoaded = false;
        var fallbackEnabled = false;

        L.control.zoom({ position: 'bottomright' }).addTo(map);

        var primaryTiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19
        });

        var fallbackTiles = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri',
            maxZoom: 19
        });

        primaryTiles.on('load', function () {
            tilesLoaded = true;
            clearStatus();
        });

        primaryTiles.on('tileerror', function () {
            if (!fallbackEnabled) {
                fallbackEnabled = true;
                map.removeLayer(primaryTiles);
                fallbackTiles.addTo(map);
                showStatus('Primary tiles failed. Trying fallback map tiles...', 'is-warning');
            }
        });

        fallbackTiles.on('load', function () {
            tilesLoaded = true;
            clearStatus();
        });

        fallbackTiles.on('tileerror', function () {
            showStatus('Map tiles failed to load. This network is blocking the tile server.', 'is-error');
        });

        primaryTiles.addTo(map);

        var clusterGroup = typeof L.markerClusterGroup === 'function'
            ? L.markerClusterGroup({
                showCoverageOnHover: false,
                spiderfyOnMaxZoom: true,
                maxClusterRadius: 40
            })
            : L.layerGroup();

        places.forEach(function (place, index) {
            var icon = L.divIcon({
                className: 'tripzo-divicon',
                html: '<div class="tripzo-marker' + (index === currentIndex ? ' active' : '') + '"><span>' + (index + 1) + '</span></div>',
                iconSize: [38, 50],
                iconAnchor: [19, 50]
            });

            var marker = L.marker([place.latitude, place.longitude], { icon: icon });
            marker.on('click', function () {
                selectPlace(index, true);
            });

            markers.push(marker);
            clusterGroup.addLayer(marker);
            bounds.extend([place.latitude, place.longitude]);
        });

        map.addLayer(clusterGroup);

        if (bounds.isValid()) {
            map.fitBounds(bounds, { padding: [32, 32] });
            defaultView = bounds;
        }

        function refitMap() {
            map.invalidateSize();
            if (defaultView) {
                map.fitBounds(defaultView, { padding: [32, 32] });
            }
        }

        map.whenReady(refitMap);
        setTimeout(refitMap, 200);
        setTimeout(refitMap, 650);
        window.addEventListener('load', refitMap);
        setTimeout(function () {
            if (!tilesLoaded && (!debugStatus || debugStatus.hidden)) {
                showStatus('Map is still loading. The tile server may be slow or blocked on this network.', 'is-warning');
            }
        }, 3500);

        document.getElementById('map-prev').addEventListener('click', function () {
            selectPlace((currentIndex - 1 + places.length) % places.length, true);
        });

        document.getElementById('map-next').addEventListener('click', function () {
            selectPlace((currentIndex + 1) % places.length, true);
        });

        document.getElementById('map-focus').addEventListener('click', function () {
            focusCurrentPlace();
        });

        document.getElementById('map-reset-view').addEventListener('click', function () {
            if (defaultView) {
                map.fitBounds(defaultView, { padding: [32, 32] });
            }
        });

        document.querySelectorAll('.map-tab').forEach(function (button) {
            button.addEventListener('click', function () {
                document.querySelectorAll('.map-tab').forEach(function (tab) {
                    tab.classList.remove('active');
                });
                this.classList.add('active');
                activeTab = this.getAttribute('data-map-tab');
                renderSheet();
            });
        });

        sheetContent.addEventListener('click', function (event) {
            var card = event.target.closest('[data-place-link]');
            if (!card) {
                return;
            }
            window.location.href = card.getAttribute('data-place-link');
        });

        if (focusPlaceId) {
            var focusIndex = places.findIndex(function (place) {
                return Number(place.id) === Number(focusPlaceId);
            });
            if (focusIndex >= 0) {
                currentIndex = focusIndex;
            }
        }

        selectPlace(currentIndex, Boolean(focusPlaceId));

        function selectPlace(index, shouldFly) {
            currentIndex = index;
            var place = places[currentIndex];

            markers.forEach(function (marker, markerIndex) {
                var element = marker.getElement();
                if (!element) {
                    return;
                }

                var bubble = element.querySelector('.tripzo-marker');
                if (bubble) {
                    bubble.classList.toggle('active', markerIndex === currentIndex);
                }
            });

            counter.textContent = (currentIndex + 1) + ' of ' + places.length;
            renderSheet();

            if (shouldFly) {
                focusCurrentPlace();
            }
        }

        function focusCurrentPlace() {
            var place = places[currentIndex];
            if (typeof clusterGroup.zoomToShowLayer === 'function') {
                clusterGroup.zoomToShowLayer(markers[currentIndex], function () {
                    map.flyTo([place.latitude, place.longitude], 14, { duration: 0.8 });
                });
            } else {
                map.flyTo([place.latitude, place.longitude], 14, { duration: 0.8 });
            }
        }

        function formatDistanceKm(value) {
            if (value === null || value === undefined || value === '') {
                return 'N/A';
            }
            return String(value) + ' km';
        }

        function renderSheet() {
            var place = places[currentIndex];
            var detailsUrl = 'place-details.php?id=' + place.id;
            var imgSrc = place.image
                ? ('images/' + encodeURIComponent(place.image))
                : ('https://placehold.co/420x260/e9ecef/495057?text=' + encodeURIComponent(place.name));
            var imageHtml = '<img src="' + imgSrc + '" alt="' + escapeHtml(place.name) + '" onerror="this.onerror=null;this.src=\'https://placehold.co/420x260?text=Preview\';">';

            var aboutHtml =
                '<div class="map-sheet-grid map-sheet-clickable" data-place-link="' + detailsUrl + '">' +
                    '<div class="map-sheet-copy">' +
                        '<div class="map-sheet-heading">' +
                            '<span class="map-sheet-marker">' + (currentIndex + 1) + '</span>' +
                            '<div>' +
                                '<h3>' + escapeHtml(place.name) + '</h3>' +
                                '<span class="map-sheet-category">' + escapeHtml(place.category_name) + '</span>' +
                            '</div>' +
                        '</div>' +
                        '<p>' + escapeHtml(place.description || place.short_description || 'Open the full details page to see more information about this attraction.') + '</p>' +
                        '<div class="map-sheet-meta">' +
                            '<div><strong>Address</strong><br><span>' + escapeHtml(place.address || 'Not available') + '</span></div>' +
                            '<div><strong>Distance</strong><br><span>' + escapeHtml(formatDistanceKm(place.distance_km)) + '</span></div>' +
                            '<div><strong>Area</strong><br><span>' + escapeHtml([place.area_name, place.district, place.province].filter(Boolean).join(', ') || 'Not available') + '</span></div>' +
                            '<div><strong>Best Time</strong><br><span>' + escapeHtml(place.best_time_to_visit || 'Not available') + '</span></div>' +
                        '</div>' +
                        '<p class="map-tap-hint">Tap this card to open full attraction details.</p>' +
                        '<a class="btn btn-primary" href="' + detailsUrl + '">Open Full Details</a>' +
                    '</div>' +
                    '<div class="map-sheet-media">' + imageHtml + '</div>' +
                '</div>';

            var photosHtml =
                '<div class="map-sheet-grid map-sheet-clickable" data-place-link="' + detailsUrl + '">' +
                    '<div class="map-sheet-copy">' +
                        '<h3>' + escapeHtml(place.name) + '</h3>' +
                        '<p>Photo preview for this attraction. Use the full details page for a richer view and more place information.</p>' +
                        '<p class="map-tap-hint">Tap this card to open full attraction details.</p>' +
                        '<a class="btn btn-primary" href="' + detailsUrl + '">Open Full Details</a>' +
                    '</div>' +
                    '<div class="map-sheet-media">' + imageHtml + '</div>' +
                '</div>';

            var visitHtml =
                '<div class="map-sheet-placeholder map-sheet-clickable" data-place-link="' + detailsUrl + '">' +
                    '<h3>' + escapeHtml(place.name) + '</h3>' +
                    '<div class="map-sheet-meta mb-3">' +
                        '<div><strong>Opening Hours</strong><br><span>' + escapeHtml(place.opening_hours || 'Not available') + '</span></div>' +
                        '<div><strong>Contact</strong><br><span>' + escapeHtml(place.contact_info || 'Not available') + '</span></div>' +
                        '<div><strong>Entry Fee</strong><br><span>' + escapeHtml(place.entry_fee || 'Not available') + '</span></div>' +
                        '<div><strong>Area</strong><br><span>' + escapeHtml([place.area_name, place.district].filter(Boolean).join(', ') || 'Not available') + '</span></div>' +
                    '</div>' +
                    '<p class="map-tap-hint">Tap this card to open full attraction details.</p>' +
                    '<a class="btn btn-outline-primary me-2" href="' + detailsUrl + '">View Attraction Page</a>' +
                    (place.google_maps_url ? '<a class="btn btn-primary" target="_blank" rel="noopener" href="' + escapeHtml(place.google_maps_url) + '">Directions</a>' : '') +
                '</div>';

            if (activeTab === 'about') {
                sheetContent.innerHTML = aboutHtml;
            } else if (activeTab === 'photos') {
                sheetContent.innerHTML = photosHtml;
            } else {
                sheetContent.innerHTML = visitHtml;
            }
        }

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    }

    if (typeof L !== 'undefined') {
        initTripzoMap();
    } else {
        window.addEventListener('load', function () {
            initTripzoMap();
        });
    }
});
</script>
<?php } ?>

<?php include 'footer.php'; ?>
