
<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

    if ($action === 'remove' && $productId) {
        unset($_SESSION['cart'][$productId]);
        $message = 'Product removed from cart.';
    } elseif ($action === 'update' && $productId) {
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

        if ($quantity === false || $quantity === null || $quantity < 1 || $quantity > 99) {
            $error = 'Quantity must be between 1 and 99.';
        } elseif (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] = $quantity;
            $message = 'Cart updated.';
        }
    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
        $message = 'Your cart has been cleared.';
    }
}

$cartItems = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    $ids = array_map('intval', array_keys($_SESSION['cart']));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $stmt = $conn->prepare(
        "SELECT id, name, price, image
         FROM products
         WHERE id IN ($placeholders)"
    );

    $stmt->bind_param($types, ...$ids);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($product = $result->fetch_assoc()) {
        $id = (int) $product['id'];
        $quantity = (int) $_SESSION['cart'][$id];
        $product['quantity'] = $quantity;
        $product['subtotal'] = (float) $product['price'] * $quantity;

        $total += $product['subtotal'];
        $cartItems[] = $product;
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5fa;
            padding: 25px;
        }

        h1 {
            color: #5c4ac7;
        }

        .cart-item {
            background: white;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
        }

        img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            vertical-align: middle;
            margin-right: 15px;
        }

        input {
            padding: 7px;
            width: 65px;
        }

        button {
            padding: 8px 12px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            background: #5c4ac7;
            color: white;
            cursor: pointer;
        }

        .message {
            color: green;
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

<a href="index.php">&larr; Continue shopping</a>
<h1>Your Shopping Cart</h1>

<?php if ($message !== ''): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if (empty($cartItems)): ?>
    <p>Your cart is empty.</p>
<?php else: ?>

    <?php foreach ($cartItems as $item): ?>
        <div class="cart-item">
            <?php if (!empty($item['image'])): ?>
                <img
                    src="uploads/products/<?= rawurlencode($item['image']) ?>"
                    alt="<?= htmlspecialchars($item['name']) ?>"
                >
            <?php endif; ?>

            <h3><?= htmlspecialchars($item['name']) ?></h3>

            <p>Price: ₹<?= number_format((float) $item['price'], 2) ?></p>

            <p>
                Subtotal:
                ₹<?= number_format($item['subtotal'], 2) ?>
            </p>

            <form method="POST" action="cart.php">
                <input type="hidden" name="action" value="update">
                <input
                    type="hidden"
                    name="product_id"
                    value="<?= (int) $item['id'] ?>"
                >

                <label>
                    Quantity:
                    <input
                        type="number"
                        name="quantity"
                        min="1"
                        max="99"
                        value="<?= (int) $item['quantity'] ?>"
                        required
                    >
                </label>

                <button type="submit">Update</button>
            </form>

            <form method="POST" action="cart.php">
                <input type="hidden" name="action" value="remove">
                <input
                    type="hidden"
                    name="product_id"
                    value="<?= (int) $item['id'] ?>"
                >
                <button type="submit">Remove</button>
            </form>
        </div>
    <?php endforeach; ?>

    <h2>Total: ₹<?= number_format($total, 2) ?></h2>

    <form method="POST" action="cart.php">
        <input type="hidden" name="action" value="clear">
        <button type="submit">Clear Cart</button>
    </form>

    <p><a href="checkout.php">Proceed to Checkout</a></p>

<?php endif; ?>

</body>
</html>
