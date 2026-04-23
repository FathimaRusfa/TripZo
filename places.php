<?php
include 'db.php';
include 'navbar.php';

$search = trim($_GET['q'] ?? '');
$selectedCategory = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 6;
$offset = ($page - 1) * $perPage;

$categories = $conn->query("SELECT category_id, name FROM categories ORDER BY name ASC");

$whereClause = '';
$bindParams = [];
$bindTypes = '';
$likeSearch = '%' . $search . '%';

if ($search !== '' && $selectedCategory > 0) {
    $whereClause = " WHERE (attractions.name LIKE ? OR attractions.short_description LIKE ?) AND attractions.category_id = ?";
    $bindTypes = 'ssi';
    $bindParams = [$likeSearch, $likeSearch, $selectedCategory];
} elseif ($search !== '') {
    $whereClause = " WHERE attractions.name LIKE ? OR attractions.short_description LIKE ?";
    $bindTypes = 'ss';
    $bindParams = [$likeSearch, $likeSearch];
} elseif ($selectedCategory > 0) {
    $whereClause = " WHERE attractions.category_id = ?";
    $bindTypes = 'i';
    $bindParams = [$selectedCategory];
}

$countSql = "SELECT COUNT(*) AS total
             FROM attractions
             LEFT JOIN categories ON attractions.category_id = categories.category_id" . $whereClause;
$countStmt = $conn->prepare($countSql);
if ($bindTypes !== '') {
    $countStmt->bind_param($bindTypes, ...$bindParams);
}
$countStmt->execute();
$totalResult = $countStmt->get_result()->fetch_assoc();
$totalAttractions = (int) ($totalResult['total'] ?? 0);
$totalPages = max(1, (int) ceil($totalAttractions / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$dataSql = "SELECT attractions.*, categories.name AS category_name
            FROM attractions
            LEFT JOIN categories ON attractions.category_id = categories.category_id"
            . $whereClause .
            " ORDER BY attractions.name ASC LIMIT ? OFFSET ?";
$dataStmt = $conn->prepare($dataSql);

if ($bindTypes === 'ssi') {
    $dataStmt->bind_param('ssiii', $bindParams[0], $bindParams[1], $bindParams[2], $perPage, $offset);
} elseif ($bindTypes === 'ss') {
    $dataStmt->bind_param('ssii', $bindParams[0], $bindParams[1], $perPage, $offset);
} elseif ($bindTypes === 'i') {
    $dataStmt->bind_param('iii', $bindParams[0], $perPage, $offset);
} else {
    $dataStmt->bind_param('ii', $perPage, $offset);
}

$dataStmt->execute();
$result = $dataStmt->get_result();
?>

<div class="container mt-5">
    <div class="page-header text-center">
        <h2>Tourist Attractions</h2>
        <p>Find the best destinations in Addalaichenai by category, distance, and travel interest.</p>
    </div>

    <div class="content-box content-box-soft mb-4" data-aos="fade-up">
        <form method="GET" action="places.php" class="row g-3 align-items-end">
            <div class="col-lg-6">
                <label for="q" class="form-label mb-1">Search attractions</label>
                <input type="text" id="q" name="q" class="form-control" placeholder="e.g. beach, temple, park" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-lg-4">
                <label for="category" class="form-label mb-1">Category</label>
                <select id="category" name="category" class="form-select">
                    <option value="0">All categories</option>
                    <?php while ($category = $categories->fetch_assoc()) { ?>
                        <option value="<?php echo (int) $category['category_id']; ?>" <?php echo $selectedCategory === (int) $category['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-lg-2 d-grid d-lg-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Apply</button>
                <a href="places.php" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <span class="muted-chip">Quick filters</span>
            <span class="muted-chip">Family-friendly</span>
            <span class="muted-chip">Scenic views</span>
            <span class="muted-chip">Short distance</span>
        </div>
    </div>

    <?php if ($totalAttractions > 0) { ?>
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <p class="mb-0 text-muted">Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $perPage, $totalAttractions); ?> of <?php echo $totalAttractions; ?> attractions</p>
        </div>
        <div class="row">
            <?php while ($row = $result->fetch_assoc()) { ?>
                <div class="col-md-6 col-lg-4 mb-4" data-aos="zoom-in-up">
                    <div class="card card-subtle h-100">
                        <img src="images/<?php echo htmlspecialchars($row['image']); ?>"
                             class="card-img-top"
                             alt="<?php echo htmlspecialchars($row['name']); ?>"
                             loading="lazy"
                             onerror="this.onerror=null;this.src='https://placehold.co/800x500?text=No+Image';">
                        <div class="card-body d-flex flex-column">
                            <span class="badge-soft mb-2"><?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?></span>
                            <h5 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h5>
                            <p class="card-text line-clamp-3"><?php echo htmlspecialchars($row['short_description']); ?></p>
                            <p class="mb-3"><span class="badge bg-light text-dark border">Distance: <?php echo htmlspecialchars($row['distance_km']); ?> km</span></p>
                            <div class="mt-auto">
                                <a href="place-details.php?id=<?php echo (int) $row['attraction_id']; ?>" class="btn btn-primary w-100">View Details &rarr;</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>

        <?php if ($totalPages > 1) {
            $queryBase = [];
            if ($search !== '') {
                $queryBase['q'] = $search;
            }
            if ($selectedCategory > 0) {
                $queryBase['category'] = $selectedCategory;
            }
        ?>
            <nav aria-label="Attractions pagination" class="mt-2">
                <ul class="pagination justify-content-center">
                    <?php
                    $prevDisabled = $page <= 1 ? ' disabled' : '';
                    $prevQuery = http_build_query(array_merge($queryBase, ['page' => max(1, $page - 1)]));
                    ?>
                    <li class="page-item<?php echo $prevDisabled; ?>">
                        <a class="page-link" href="places.php?<?php echo htmlspecialchars($prevQuery); ?>">Previous</a>
                    </li>

                    <?php for ($i = 1; $i <= $totalPages; $i++) {
                        $pageQuery = http_build_query(array_merge($queryBase, ['page' => $i]));
                        $activeClass = $i === $page ? ' active' : '';
                    ?>
                        <li class="page-item<?php echo $activeClass; ?>">
                            <a class="page-link" href="places.php?<?php echo htmlspecialchars($pageQuery); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php } ?>

                    <?php
                    $nextDisabled = $page >= $totalPages ? ' disabled' : '';
                    $nextQuery = http_build_query(array_merge($queryBase, ['page' => min($totalPages, $page + 1)]));
                    ?>
                    <li class="page-item<?php echo $nextDisabled; ?>">
                        <a class="page-link" href="places.php?<?php echo htmlspecialchars($nextQuery); ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php } ?>
    <?php } else { ?>
        <div class="content-box text-center py-5">
            <h5 class="mb-2">No attractions found</h5>
            <p class="mb-3">Try changing the search term or selecting a different category.</p>
            <a href="places.php" class="btn btn-outline-primary">Clear Filters</a>
        </div>
    <?php } ?>
</div>

<?php include 'footer.php'; ?>
