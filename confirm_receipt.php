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

// Verify this order item belongs to an order placed by this user
// and that it's currently out for delivery.
$stmt = $pdo->prepare(
    'SELECT oi.order_item_id
     FROM order_items oi
     JOIN orders o ON oi.order_id = o.order_id
     WHERE oi.order_item_id = ? AND o.buyer_id = ? AND oi.status = "out_for_delivery"'
);
$stmt->execute([$order_item_id, $user_id]);

if ($stmt->fetch()) {
    $update = $pdo->prepare(
        "UPDATE order_items
         SET status = 'delivered', received_at = NOW()
         WHERE order_item_id = ?"
    );
    $update->execute([$order_item_id]);
    $_SESSION['flash'] = 'Receipt confirmed - thank you!';
} else {
    $_SESSION['flash'] = 'Could not confirm that item - it may not be yours or is not out for delivery yet.';
}

header('Location: dashboard.php');
exit;