
<?php
require_once __DIR__ . "/includes/db.php";

// Fetch all categories
$categories = $conn->query(
    "SELECT id, name FROM categories ORDER BY name"
);

// Fetch a few products for the homepage
$products = $conn->query(
    "SELECT p.id, p.category_id, p.name, p.price, p.image,
       c.name AS category_name
FROM products p
INNER JOIN categories c ON p.category_id = c.id
ORDER BY p.id DESC
LIMIT 8"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Shopping Store</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f7f6fc;
            color: #29243b;
        }

        header {
            background: #5c4ac7;
            color: white;
            padding: 18px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        header h2 {
            margin: 0;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
        }

        .hero {
            padding: 45px 6%;
            background: #e9e5ff;
            text-align: center;
        }

        .hero h1 {
            margin-top: 0;
            color: #4636a3;
        }

        .container {
            padding: 25px 6%;
        }

        .categories, .products {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 18px;
        }

        .category, .product {
            background: white;
            border-radius: 10px;
            padding: 18px;
            text-align: center;
            box-shadow: 0 2px 8px #0000000d;
        }

        .category a {
            color: #5c4ac7;
            font-weight: bold;
            text-decoration: none;
        }

        .product img {
            width: 100%;
            height: 180px;
            object-fit: contain;
        }

        .price {
            color: #4636a3;
            font-weight: bold;
        }

        .view {
            display: inline-block;
            margin-top: 8px;
            padding: 9px 14px;
            background: #5c4ac7;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        footer {
            text-align: center;
            padding: 20px;
            background: #29243b;
            color: white;
        }
    </style>
</head>
<body>

<header>
    <h2>My Shopping Store</h2>

    <nav>
        <a href="index.php">Home</a>
        <a href="register.php">Register</a>
        <a href="login.php">Login</a>
        <a href="cart.php">Cart</a>
    </nav>
</header>

<section class="hero">
    <h1>Welcome to My Shopping Store</h1>
    <p>Explore products and find what you need.</p>
</section>

<main class="container">
    <h2>Shop by Category</h2>

    <div class="categories">
        <?php while ($category = $categories->fetch_assoc()): ?>
            <div class="category">
                <h3>
                    <?= htmlspecialchars($category["name"]) ?>
                </h3>

                <a href="category.php?id=<?= (int) $category["id"] ?>">
                    View Products →
                </a>
            </div>
        <?php endwhile; ?>
    </div>

    <h2>Latest Products</h2>

    <div class="products">
        <?php if ($products->num_rows > 0): ?>
            <?php while ($product = $products->fetch_assoc()): ?>
                <div class="product">
                    <?php if (!empty($product["image"])): ?>
                        <img
                            src="uploads/products/<?= rawurlencode(basename($product["image"])) ?>"
                            alt="<?= htmlspecialchars($product["name"]) ?>"
                        >
                    <?php else: ?>
                        <div>No image available</div>
                    <?php endif; ?>

                    <h3><?= htmlspecialchars($product["name"]) ?></h3>

                    <p>
                        Category:
                        <?= htmlspecialchars($product["category_name"]) ?>
                    </p>

                   
<p class="price">
    ₹<?= number_format((float) $product["price"], 2) ?>
</p>

<a
    class="view"
    href="category.php?id=<?= (int) $product["category_id"] ?>"
>Browse Category</a>

<form method="POST" action="add_to_cart.php" style="margin-top: 12px;">
    <input
        type="hidden"
        name="product_id"
        value="<?= (int) $product["id"] ?>"
    >

    <label>
        Quantity:
        <input
            type="number"
            name="quantity"
            value="1"
            min="1"
            max="99"
            required
            style="width: 60px; padding: 6px;"
        >
    </label>

    <button
        type="submit"
        style="margin-top: 10px; padding: 9px 14px;
               background: #5c4ac7; color: white;
               border: none; border-radius: 5px; cursor: pointer;"
    >Add to Cart</button>
</form>

                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No products have been added yet.</p>
        <?php endif; ?>
    </div>
</main>

<footer>
    <p>My Shopping Store &copy; <?= date("Y") ?></p>
</footer>

</body>
</html>

<?php
$conn->close();
?>
