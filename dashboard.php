<?php
session_start();
require_once 'db_connect.php';

// Validates that a session has been created. If not redirects to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// One-time success message set by mark_dispatched.php / confirm_receipt.php for an order is has dispatched or received
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Current user's details
$stmt = $pdo->prepare('SELECT full_name, username, email FROM users WHERE user_id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// ---- SALES: everything this user has sold as a farmer ----
$stmt = $pdo->prepare(
    'SELECT oi.order_item_id, p.produce_name, oi.quantity, l.unit, oi.price_at_purchase,
            oi.subtotal, oi.status, oi.dispatched_at, oi.received_at,
            o.order_date, u.full_name AS buyer_name
     FROM order_items oi
     JOIN listings l ON oi.listing_id = l.listing_id
     JOIN produce p  ON l.produce_id = p.produce_id
     JOIN orders o   ON oi.order_id = o.order_id
     JOIN users u    ON o.buyer_id = u.user_id
     WHERE l.farmer_id = ?
     ORDER BY o.order_date DESC'
);
$stmt->execute([$user_id]);
$sales = $stmt->fetchAll();

$total_sales_revenue = array_sum(array_column($sales, 'subtotal'));

// ---- PURCHASES: everything this user has bought as a customer ----
$stmt = $pdo->prepare(
    'SELECT oi.order_item_id, p.produce_name, oi.quantity, l.unit, oi.price_at_purchase,
            oi.subtotal, oi.status, oi.dispatched_at, oi.received_at,
            o.order_date, uf.full_name AS farmer_name
     FROM order_items oi
     JOIN listings l ON oi.listing_id = l.listing_id
     JOIN produce p  ON l.produce_id = p.produce_id
     JOIN orders o   ON oi.order_id = o.order_id
     JOIN users uf   ON l.farmer_id = uf.user_id
     WHERE o.buyer_id = ?
     ORDER BY o.order_date DESC'
);
$stmt->execute([$user_id]);
$purchases = $stmt->fetchAll();

$total_purchases_spent = array_sum(array_column($purchases, 'subtotal'));

// ---- Active listings this user currently has for sale ----
$stmt = $pdo->prepare(
    "SELECT l.listing_id, p.produce_name, l.price, l.unit, l.quantity_available, l.status
     FROM listings l
     JOIN produce p ON l.produce_id = p.produce_id
     WHERE l.farmer_id = ?
     ORDER BY l.date_listed DESC"
);
$stmt->execute([$user_id]);
$my_listings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Farmers Marketplace</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="wide-container">
        <div class="topbar">
            <h1>Welcome, <?= htmlspecialchars($user['full_name']) ?></h1>
            <div>
                <a href="browse.php" style="margin-right:16px;">Browse Produce</a>
                <a href="logout.php">Log out</a>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="success"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <div class="stat-cards">
            <div class="stat-card">
                <div class="value">KES <?= number_format($total_sales_revenue, 2) ?></div>
                <div class="label">Total Sales Revenue</div>
            </div>
            <div class="stat-card">
                <div class="value">KES <?= number_format($total_purchases_spent, 2) ?></div>
                <div class="label">Total Spent on Purchases</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= count($my_listings) ?></div>
                <div class="label">Active Listings</div>
            </div>
        </div>

        <h2>My Produce Listings</h2>
        <p><a href="add_listing.php" class="small-btn" style="display:inline-block;text-decoration:none;">+ Add Listing</a></p>
        <?php if (empty($my_listings)): ?>
            <p style="color:#777;">You haven't listed any produce yet.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Produce</th><th>Price</th><th>Unit</th><th>Qty Available</th><th>Status</th>
                </tr>
                <?php foreach ($my_listings as $l): ?>
                <tr>
                    <td><?= htmlspecialchars($l['produce_name']) ?></td>
                    <td>KES <?= number_format($l['price'], 2) ?></td>
                    <td><?= htmlspecialchars($l['unit']) ?></td>
                    <td><?= htmlspecialchars($l['quantity_available']) ?></td>
                    <td><?= htmlspecialchars($l['status']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <h2>My Sales (as Farmer)</h2>
        <?php if (empty($sales)): ?>
            <p style="color:#777;">No sales yet.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Produce</th><th>Buyer</th><th>Qty</th><th>Price</th><th>Subtotal</th><th>Date</th><th>Status</th><th>Action</th>
                </tr>
                <?php foreach ($sales as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['produce_name']) ?></td>
                    <td><?= htmlspecialchars($s['buyer_name']) ?></td>
                    <td><?= htmlspecialchars($s['quantity']) ?> <?= htmlspecialchars($s['unit']) ?></td>
                    <td>KES <?= number_format($s['price_at_purchase'], 2) ?></td>
                    <td>KES <?= number_format($s['subtotal'], 2) ?></td>
                    <td><?= htmlspecialchars($s['order_date']) ?></td>
                    <td><span class="status-badge status-<?= htmlspecialchars($s['status']) ?>"><?= ucwords(str_replace('_', ' ', $s['status'])) ?></span></td>
                    <td>
                        <?php if ($s['status'] === 'pending'): ?>
                            <form method="POST" action="mark_dispatched.php" style="margin:0;">
                                <input type="hidden" name="order_item_id" value="<?= (int)$s['order_item_id'] ?>">
                                <button type="submit" class="small-btn">Mark Out for Delivery</button>
                            </form>
                        <?php elseif ($s['status'] === 'out_for_delivery'): ?>
                            <span style="color:#777;font-size:13px;">Awaiting customer confirmation</span>
                        <?php else: ?>
                            <span style="color:#2e7d32;font-size:13px;">✓ Delivered</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <h2>My Purchases (as Customer)</h2>
        <?php if (empty($purchases)): ?>
            <p style="color:#777;">No purchases yet.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Produce</th><th>Farmer</th><th>Qty</th><th>Price</th><th>Subtotal</th><th>Date</th><th>Status</th><th>Action</th>
                </tr>
                <?php foreach ($purchases as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['produce_name']) ?></td>
                    <td><?= htmlspecialchars($p['farmer_name']) ?></td>
                    <td><?= htmlspecialchars($p['quantity']) ?> <?= htmlspecialchars($p['unit']) ?></td>
                    <td>KES <?= number_format($p['price_at_purchase'], 2) ?></td>
                    <td>KES <?= number_format($p['subtotal'], 2) ?></td>
                    <td><?= htmlspecialchars($p['order_date']) ?></td>
                    <td><span class="status-badge status-<?= htmlspecialchars($p['status']) ?>"><?= ucwords(str_replace('_', ' ', $p['status'])) ?></span></td>
                    <td>
                        <?php if ($p['status'] === 'out_for_delivery'): ?>
                            <form method="POST" action="confirm_receipt.php" style="margin:0;">
                                <input type="hidden" name="order_item_id" value="<?= (int)$p['order_item_id'] ?>">
                                <button type="submit" class="small-btn">Confirm Receipt</button>
                            </form>
                        <?php elseif ($p['status'] === 'pending'): ?>
                            <span style="color:#777;font-size:13px;">Waiting for farmer to dispatch</span>
                        <?php else: ?>
                            <span style="color:#2e7d32;font-size:13px;">✓ Received</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>