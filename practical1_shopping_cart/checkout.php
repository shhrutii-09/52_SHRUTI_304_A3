
<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$error = '';
$items = [];
$total = 0;

$cart = $_SESSION['cart'];
$ids = [];

foreach ($cart as $id => $quantity) {
    $id = filter_var($id, FILTER_VALIDATE_INT);
    $quantity = filter_var($quantity, FILTER_VALIDATE_INT);

    if ($id && $id > 0 && $quantity && $quantity > 0 && $quantity <= 99) {
        $ids[$id] = $quantity;
    }
}

if (empty($ids)) {
    $_SESSION['cart'] = [];
    header('Location: cart.php');
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));
$productIds = array_keys($ids);

$stmt = $conn->prepare(
    "SELECT id, name, price FROM products WHERE id IN ($placeholders)"
);
$stmt->bind_param($types, ...$productIds);
$stmt->execute();

$result = $stmt->get_result();

while ($product = $result->fetch_assoc()) {
    $productId = (int) $product['id'];
    $quantity = $ids[$productId];
    $price = (float) $product['price'];

    $items[] = [
        'id' => $productId,
        'name' => $product['name'],
        'quantity' => $quantity,
        'price' => $price,
        'subtotal' => $price * $quantity
    ];

    $total += $price * $quantity;
}

$stmt->close();

if (empty($items)) {
    $_SESSION['cart'] = [];
    header('Location: cart.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();

    try {
        $userId = (int) $_SESSION['user_id'];

        $orderStmt = $conn->prepare(
            "INSERT INTO orders (user_id, total_amount, status)
             VALUES (?, ?, 'Placed')"
        );
        $orderStmt->bind_param("id", $userId, $total);
        $orderStmt->execute();

        $orderId = $conn->insert_id;
        $orderStmt->close();

        $itemStmt = $conn->prepare(
            "INSERT INTO order_items
             (order_id, product_id, quantity, price)
             VALUES (?, ?, ?, ?)"
        );

        foreach ($items as $item) {
            $productId = $item['id'];
            $quantity = $item['quantity'];
            $price = $item['price'];

            $itemStmt->bind_param(
                "iiid",
                $orderId,
                $productId,
                $quantity,
                $price
            );
            $itemStmt->execute();
        }

        $itemStmt->close();
        $conn->commit();

        $_SESSION['cart'] = [];

        header('Location: checkout.php?success=' . $orderId);
        exit;
    } catch (Throwable $e) {
        $conn->rollback();
        $error = 'Unable to place your order. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5fa;
            padding: 25px;
        }

        .container {
            max-width: 650px;
            margin: 20px auto;
            padding: 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px #ddd;
        }

        h1, h2 {
            color: #5c4ac7;
        }

        .item {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .total {
            font-size: 20px;
            font-weight: bold;
        }

        button {
            background: #5c4ac7;
            color: white;
            padding: 12px 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .success {
            color: #16743b;
        }

        .error {
            color: #b00020;
        }

        a {
            color: #5c4ac7;
        }
    </style>
</head>
<body>

<div class="container">
    <a href="cart.php">&larr; Back to cart</a>

    <?php if (isset($_GET['success']) && ctype_digit($_GET['success'])): ?>
        <h1 class="success">Order placed successfully!</h1>
        <p>Your order number is #<?= (int) $_GET['success'] ?>.</p>
        <a href="index.php">Continue shopping</a>

    <?php else: ?>
        <h1>Checkout</h1>

        <?php if ($error !== ''): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php foreach ($items as $item): ?>
            <div class="item">
                <strong><?= htmlspecialchars($item['name']) ?></strong>
                <p>
                    Quantity: <?= $item['quantity'] ?> |
                    ₹<?= number_format($item['price'], 2) ?> each
                </p>
                <p>Subtotal: ₹<?= number_format($item['subtotal'], 2) ?></p>
            </div>
        <?php endforeach; ?>

        <p class="total">Total: ₹<?= number_format($total, 2) ?></p>

        <form method="POST" action="checkout.php">
            <button type="submit">Place Order</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
