<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: browse.php');
    exit;
}

$user_id     = $_SESSION['user_id'];
$listing_ids = $_POST['listing_id'] ?? [];
$quantities  = $_POST['quantity'] ?? [];

// Build the cart: keep only rows where a positive numeric quantity was entered
$cart = [];
foreach ($listing_ids as $i => $lid) {
    $lid = (int) $lid;
    $qty = $quantities[$i] ?? '';

    if ($lid > 0 && is_numeric($qty) && (float) $qty > 0) {
        $cart[] = ['listing_id' => $lid, 'quantity' => (float) $qty];
    }
}

if (empty($cart)) {
    $_SESSION['flash'] = 'Enter a quantity for at least one item before buying.';
    header('Location: browse.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $order_lines = [];  // validated lines ready to insert
    $total       = 0;

    foreach ($cart as $line) {
        // Lock each listing row so concurrent buyers can't oversell it
        $stmt = $pdo->prepare(
            'SELECT listing_id, farmer_id, price, quantity_available, status
             FROM listings
             WHERE listing_id = ?
             FOR UPDATE'
        );
        $stmt->execute([$line['listing_id']]);
        $listing = $stmt->fetch();

        if (!$listing) {
            throw new RuntimeException('One of the items in your cart no longer exists.');
        }
        if ((int) $listing['farmer_id'] === (int) $user_id) {
            throw new RuntimeException('You cannot buy your own listing.');
        }
        if ($listing['status'] !== 'available') {
            throw new RuntimeException('An item in your cart is no longer available.');
        }
        if ($line['quantity'] > (float) $listing['quantity_available']) {
            throw new RuntimeException('Only ' . $listing['quantity_available'] . ' available for one of your items - reduce the quantity.');
        }

        $price    = (float) $listing['price'];
        $subtotal = $price * $line['quantity'];
        $total   += $subtotal;

        $order_lines[] = [
            'listing_id' => $listing['listing_id'],
            'quantity'   => $line['quantity'],
            'price'      => $price,
            'subtotal'   => $subtotal,
            'remaining'  => (float) $listing['quantity_available'] - $line['quantity'],
        ];
    }

    // One order covering every line (possibly from several different farmers)
    $stmt = $pdo->prepare('INSERT INTO orders (buyer_id, total_amount, status) VALUES (?, ?, "pending")');
    $stmt->execute([$user_id, $total]);
    $order_id = $pdo->lastInsertId();

    foreach ($order_lines as $line) {
        $stmt = $pdo->prepare(
            'INSERT INTO order_items (order_id, listing_id, quantity, price_at_purchase, subtotal, status)
             VALUES (?, ?, ?, ?, ?, "pending")'
        );
        $stmt->execute([$order_id, $line['listing_id'], $line['quantity'], $line['price'], $line['subtotal']]);

        $new_status = $line['remaining'] <= 0 ? 'sold_out' : 'available';
        $stmt = $pdo->prepare('UPDATE listings SET quantity_available = ?, status = ? WHERE listing_id = ?');
        $stmt->execute([$line['remaining'], $new_status, $line['listing_id']]);
    }

    $pdo->commit();

    $itemWord = count($order_lines) === 1 ? 'item' : 'items';
    $_SESSION['flash'] = 'Order placed for ' . count($order_lines) . ' ' . $itemWord . '! Waiting for farmer dispatch.';
    header('Location: dashboard.php');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash'] = $e->getMessage();
    header('Location: browse.php');
    exit;
}