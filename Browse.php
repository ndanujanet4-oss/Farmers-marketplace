<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$flash   = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Optional category filter via ?category=Vegetable
$category_filter = $_GET['category'] ?? '';
$allowed_categories = ['Vegetable', 'Animal Product', 'Cereal'];

$sql = "SELECT l.listing_id, l.price, l.unit, l.quantity_available,
               p.produce_name, p.category,
               u.full_name AS farmer_name, u.location AS farmer_location,
               l.farmer_id
        FROM listings l
        JOIN produce p ON l.produce_id = p.produce_id
        JOIN users u   ON l.farmer_id = u.user_id
        WHERE l.status = 'available' AND l.quantity_available > 0";

$params = [];
if (in_array($category_filter, $allowed_categories, true)) {
    $sql .= " AND p.category = ?";
    $params[] = $category_filter;
}
$sql .= " ORDER BY p.category, p.produce_name, l.price ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Produce - Farmers Marketplace</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="wide-container">
        <div class="topbar">
            <h1>Browse Produce</h1>
            <a href="dashboard.php">&larr; Back to Dashboard</a>
        </div>

        <?php if ($flash): ?>
            <div class="success"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <div style="margin-bottom:20px;">
            <a href="browse.php" style="margin-right:14px;<?= $category_filter === '' ? 'font-weight:bold;' : '' ?>">All</a>
            <?php foreach ($allowed_categories as $cat): ?>
                <a href="browse.php?category=<?= urlencode($cat) ?>" style="margin-right:14px;<?= $category_filter === $cat ? 'font-weight:bold;' : '' ?>">
                    <?= htmlspecialchars($cat) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($listings)): ?>
            <p style="color:#777;">No produce available right now<?= $category_filter ? ' in ' . htmlspecialchars($category_filter) : '' ?>.</p>
        <?php else: ?>
            <p style="font-size:13px;color:#555;">Enter a quantity for each item you want, then click "Buy Selected Items" once at the bottom - everything goes into a single order.</p>
            <form method="POST" action="place_order.php">
                <table>
                    <tr>
                        <th>Produce</th><th>Farmer</th><th>Location</th><th>Price</th><th>Available</th><th>Quantity</th>
                    </tr>
                    <?php foreach ($listings as $l): ?>
                    <tr>
                        <td><?= htmlspecialchars($l['produce_name']) ?></td>
                        <td><?= htmlspecialchars($l['farmer_name']) ?></td>
                        <td><?= htmlspecialchars($l['farmer_location'] ?? '-') ?></td>
                        <td>KES <?= number_format($l['price'], 2) ?> / <?= htmlspecialchars($l['unit']) ?></td>
                        <td><?= htmlspecialchars($l['quantity_available']) ?> <?= htmlspecialchars($l['unit']) ?></td>
                        <td>
                            <?php if ((int)$l['farmer_id'] === (int)$user_id): ?>
                                <span style="color:#777;font-size:13px;">Your listing</span>
                            <?php else: ?>
                                <input type="hidden" name="listing_id[]" value="<?= (int)$l['listing_id'] ?>">
                                <input type="text" name="quantity[]" placeholder="0" style="width:60px;padding:6px;border:1px solid #ccc;border-radius:4px;">
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <button type="submit" class="small-btn" style="margin-top:16px;padding:10px 20px;font-size:14px;">Buy Selected Items</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>