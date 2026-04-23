<?php
include __DIR__ . '/admin-common.php';

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM attractions WHERE attraction_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
}

header('Location: manage-places.php');
exit;
