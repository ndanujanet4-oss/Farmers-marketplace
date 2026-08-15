<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$errors  = [];

// Pull the full produce catalog, grouped by category for the dropdown
$stmt = $pdo->query('SELECT produce_id, produce_name, category FROM produce ORDER BY category, produce_name');
$all_produce = $stmt->fetchAll();

$produce_by_category = [];
foreach ($all_produce as $item) {
    $produce_by_category[$item['category']][] = $item;
}

$old = ['produce_id' => '', 'price' => '', 'unit' => 'kg', 'quantity_available' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $produce_id = (int) ($_POST['produce_id'] ?? 0);
    $price      = $_POST['price'] ?? '';
    $unit       = trim($_POST['unit'] ?? '');
    $quantity   = $_POST['quantity_available'] ?? '';

    $old = compact('produce_id', 'price', 'unit', 'quantity');
    $old['quantity_available'] = $quantity; // keep key name consistent with initial $old

    // --- Validation ---
    if ($produce_id <= 0) {
        $errors[] = 'Please select a produce item.';
    } else {
        // confirm it's a real produce_id (defends against a tampered form value)
        $check = $pdo->prepare('SELECT produce_id FROM produce WHERE produce_id = ?');
        $check->execute([$produce_id]);
        if (!$check->fetch()) {
            $errors[] = 'Selected produce item is invalid.';
        }
    }

    if (!is_numeric($price) || $price <= 0) {
        $errors[] = 'Price must be a number greater than 0.';
    }

    if ($unit === '') {
        $errors[] = 'Please choose a unit.';
    }

    if (!is_numeric($quantity) || $quantity <= 0) {
        $errors[] = 'Quantity available must be a number greater than 0.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'INSERT INTO listings (produce_id, farmer_id, price, unit, quantity_available)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$produce_id, $user_id, $price, $unit, $quantity]);

        $_SESSION['flash'] = 'Listing created successfully.';
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Listing - Farmers Marketplace</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="topbar">
            <h1>Add Produce Listing</h1>
            <a href="dashboard.php">&larr; Back to Dashboard</a>
        </div>
        <p style="font-size:14px;color:#555;">Choose an item from the catalog and set your own price and quantity.</p>

        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="add_listing.php">
            <label for="produce_id">Produce</label>
            <select id="produce_id" name="produce_id" required>
                <option value="">-- Select produce --</option>
                <?php foreach ($produce_by_category as $category => $items): ?>
                    <optgroup label="<?= htmlspecialchars($category) ?>">
                        <?php foreach ($items as $item): ?>
                            <option value="<?= (int)$item['produce_id'] ?>"
                                <?= ((int)$old['produce_id'] === (int)$item['produce_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($item['produce_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>

            <label for="price">Price (KES)</label>
            <input type="text" id="price" name="price" value="<?= htmlspecialchars($old['price']) ?>" placeholder="e.g. 80.00" required>

            <label for="unit">Unit</label>
            <select id="unit" name="unit" required>
                <?php
                $units = ['kg' => 'Kilogram (kg)', 'bag' => 'Bag', 'crate' => 'Crate', 'piece' => 'Piece', 'litre' => 'Litre', 'tonne' => 'Tonne'];
                foreach ($units as $value => $label):
                ?>
                    <option value="<?= $value ?>" <?= ($old['unit'] === $value) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="quantity_available">Quantity Available</label>
            <input type="text" id="quantity_available" name="quantity_available" value="<?= htmlspecialchars($old['quantity_available']) ?>" placeholder="e.g. 200" required>

            <button type="submit">Create Listing</button>
        </form>
    </div>
</body>
</html>