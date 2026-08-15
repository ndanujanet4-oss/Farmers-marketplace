<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['order_item_id'])) {
    header('Location: dashboard.php');
    exit;
}

$user_id       = $_SESSION['user_id'];
$order_item_id = (int) $_POST['order_item_id'];

// Verify this order item actually belongs to a listing owned by this user
$stmt = $pdo->prepare(
    'SELECT oi.order_item_id
     FROM order_items oi
     JOIN listings l ON oi.listing_id = l.listing_id
     WHERE oi.order_item_id = ? AND l.farmer_id = ? AND oi.status = "pending"'
);
$stmt->execute([$order_item_id, $user_id]);

if ($stmt->fetch()) {
    $update = $pdo->prepare(
        "UPDATE order_items
         SET status = 'out_for_delivery', dispatched_at = NOW()
         WHERE order_item_id = ?"
    );
    $update->execute([$order_item_id]);
    $_SESSION['flash'] = 'Marked as out for delivery.';
} else {
    $_SESSION['flash'] = 'Could not update that item - it may not belong to you or was already dispatched.';
}

header('Location: dashboard.php');
exit;