
<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/db.php';

$categoryId = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);

if (!$categoryId || $categoryId < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid category ID.']);
    exit;
}

$page = ($page && $page > 0) ? $page : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

$countStmt = $conn->prepare(
    "SELECT COUNT(*) AS total FROM products WHERE category_id = ?"
);
$countStmt->bind_param("i", $categoryId);
$countStmt->execute();

$totalProducts = (int) $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = (int) ceil($totalProducts / $limit);

if ($totalPages > 0 && $page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

$stmt = $conn->prepare(
    "SELECT id, name, price, image
     FROM products
     WHERE category_id = ?
     ORDER BY id DESC
     LIMIT ? OFFSET ?"
);

$stmt->bind_param("iii", $categoryId, $limit, $offset);
$stmt->execute();

$result = $stmt->get_result();
$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

$stmt->close();

echo json_encode([
    'products' => $products,
    'currentPage' => $page,
    'totalPages' => $totalPages,
    'totalProducts' => $totalProducts
]);
