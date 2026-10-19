
<?php
require_once __DIR__ . '/includes/db.php';

$categoryId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$categoryId || $categoryId < 1) {
    http_response_code(400);
    exit('Invalid category ID.');
}

$stmt = $conn->prepare("SELECT id, name FROM categories WHERE id = ?");
$stmt->bind_param("i", $categoryId);
$stmt->execute();

$category = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$category) {
    http_response_code(404);
    exit('Category not found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($category['name']) ?> - Shopping Store</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 25px;
            background: #f5f5fa;
        }

        h1 {
            color: #5c4ac7;
        }

        .back {
            color: #5c4ac7;
            text-decoration: none;
        }

        #products {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }

        .product {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 8px #ddd;
        }

        .product img {
            width: 100%;
            height: 180px;
            object-fit: contain;
        }

        .price {
            font-weight: bold;
            color: #5c4ac7;
        }

        #pagination {
            margin-top: 25px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        #pagination button {
            padding: 9px 14px;
            border: 1px solid #5c4ac7;
            border-radius: 5px;
            background: white;
            color: #5c4ac7;
            cursor: pointer;
        }

        #pagination button.active {
            background: #5c4ac7;
            color: white;
        }

        #message {
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <a class="back" href="index.php">&larr; Back to store</a>

    <h1><?= htmlspecialchars($category['name']) ?></h1>

    <div id="message">Loading products...</div>
    <div id="products"></div>
    <div id="pagination"></div>

    <script>
        const categoryId = <?= (int) $category['id'] ?>;

        async function loadProducts(page = 1) {
            const message = document.getElementById('message');
            const productsContainer = document.getElementById('products');
            const pagination = document.getElementById('pagination');

            message.textContent = 'Loading products...';
            productsContainer.replaceChildren();
            pagination.replaceChildren();

            try {
                const response = await fetch(
                    `api_products.php?category_id=${categoryId}&page=${page}`
                );

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Could not load products.');
                }

                message.textContent = data.products.length
                    ? ''
                    : 'No products found in this category.';

                data.products.forEach(product => {
                    const card = document.createElement('div');
                    card.className = 'product';

                    if (product.image) {
                        const img = document.createElement('img');
                        img.src = 'uploads/products/' + product.image;
                        img.alt = product.name;
                        card.appendChild(img);
                    }

                    const name = document.createElement('h3');
                    name.textContent = product.name;
                    card.appendChild(name);

                    const price = document.createElement('p');
                    price.className = 'price';
                    price.textContent = '₹' + Number(product.price).toFixed(2);
                    card.appendChild(price);



const form = document.createElement('form');
form.method = 'POST';
form.action = 'add_to_cart.php';

const productIdInput = document.createElement('input');
productIdInput.type = 'hidden';
productIdInput.name = 'product_id';
productIdInput.value = product.id;

const quantityInput = document.createElement('input');
quantityInput.type = 'number';
quantityInput.name = 'quantity';
quantityInput.value = '1';
quantityInput.min = '1';
quantityInput.max = '99';
quantityInput.required = true;

const addButton = document.createElement('button');
addButton.type = 'submit';
addButton.textContent = 'Add to Cart';

form.appendChild(productIdInput);
form.appendChild(quantityInput);
form.appendChild(addButton);

card.appendChild(form);



                    productsContainer.appendChild(card);
                });

                for (let i = 1; i <= data.totalPages; i++) {
                    const button = document.createElement('button');
                    button.textContent = i;

                    if (i === data.currentPage) {
                        button.classList.add('active');
                    }

                    button.addEventListener('click', () => loadProducts(i));
                    pagination.appendChild(button);
                }
            } catch (error) {
                message.textContent = error.message;
            }
        }

        loadProducts();
    </script>
</body>
</html>
