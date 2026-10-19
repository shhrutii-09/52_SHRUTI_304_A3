
<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

if (!$productId || $productId < 1) {
    header('Location: index.php?error=invalid_product');
    exit;
}

if ($quantity === false || $quantity === null || $quantity < 1 || $quantity > 99) {
    $quantity = 1;
}

$stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();

$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header('Location: index.php?error=product_not_found');
    exit;
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$currentQuantity = (int) ($_SESSION['cart'][$productId] ?? 0);

if ($currentQuantity + $quantity > 99) {
    $_SESSION['cart'][$productId] = 99;
} else {
    $_SESSION['cart'][$productId] = $currentQuantity + $quantity;
}

header('Location: cart.php?added=1');
exit;
