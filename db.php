<?php
$host = "127.0.0.1";
$user = "root";
$pass = "";
$dbname = "tripzo";
$port = 3306;

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!function_exists('ensureTripzoSchema')) {
    function ensureTripzoSchema(mysqli $conn)
    {
        static $ensured = false;

        if ($ensured) {
            return;
        }

        $desiredColumns = [
            'area_name' => "ALTER TABLE attractions ADD COLUMN area_name VARCHAR(120) DEFAULT NULL AFTER address",
            'district' => "ALTER TABLE attractions ADD COLUMN district VARCHAR(120) DEFAULT NULL AFTER area_name",
            'province' => "ALTER TABLE attractions ADD COLUMN province VARCHAR(120) DEFAULT NULL AFTER district",
            'postal_code' => "ALTER TABLE attractions ADD COLUMN postal_code VARCHAR(20) DEFAULT NULL AFTER province",
            'google_maps_url' => "ALTER TABLE attractions ADD COLUMN google_maps_url VARCHAR(255) DEFAULT NULL AFTER longitude",
            'nearby_landmarks' => "ALTER TABLE attractions ADD COLUMN nearby_landmarks TEXT DEFAULT NULL AFTER contact_info",
            'transport_options' => "ALTER TABLE attractions ADD COLUMN transport_options TEXT DEFAULT NULL AFTER nearby_landmarks",
            'best_time_to_visit' => "ALTER TABLE attractions ADD COLUMN best_time_to_visit VARCHAR(120) DEFAULT NULL AFTER transport_options",
            'entry_fee' => "ALTER TABLE attractions ADD COLUMN entry_fee VARCHAR(120) DEFAULT NULL AFTER best_time_to_visit",
            'accessibility_info' => "ALTER TABLE attractions ADD COLUMN accessibility_info TEXT DEFAULT NULL AFTER entry_fee"
        ];

        $existingColumns = [];
        $result = $conn->query("SHOW COLUMNS FROM attractions");

        if ($result) {
            while ($column = $result->fetch_assoc()) {
                $existingColumns[] = $column['Field'];
            }
        }

        foreach ($desiredColumns as $columnName => $sql) {
            if (!in_array($columnName, $existingColumns, true)) {
                $conn->query($sql);
            }
        }

        $conn->query(
            "CREATE TABLE IF NOT EXISTS attraction_images (
                image_id INT(11) NOT NULL AUTO_INCREMENT,
                attraction_id INT(11) NOT NULL,
                image_path VARCHAR(255) NOT NULL,
                sort_order INT(11) DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (image_id),
                KEY attraction_id (attraction_id),
                CONSTRAINT attraction_images_ibfk_1 FOREIGN KEY (attraction_id) REFERENCES attractions (attraction_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        $ensured = true;
    }
}

ensureTripzoSchema($conn);
?>
