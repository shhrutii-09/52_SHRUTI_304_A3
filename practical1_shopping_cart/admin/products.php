
<?php
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

$message = "";
$error = "";
$uploadDir = __DIR__ . "/../uploads/products/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Resize and save an uploaded image
function saveProductImage($file, $uploadDir)
{
    if (
        !isset($file["error"]) ||
        $file["error"] !== UPLOAD_ERR_OK
    ) {
        throw new RuntimeException("Please select a valid image.");
    }

    // Maximum upload size: 5 MB
    if ($file["size"] > 5 * 1024 * 1024) {
        throw new RuntimeException("Image must be smaller than 5 MB.");
    }

    $imageInfo = getimagesize($file["tmp_name"]);

    if ($imageInfo === false) {
        throw new RuntimeException("The uploaded file is not a valid image.");
    }

    $mime = $imageInfo["mime"];

    $allowedTypes = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];

    if (!in_array($mime, $allowedTypes, true)) {
        throw new RuntimeException("Only JPG, PNG and WebP images are allowed.");
    }

    if (!extension_loaded("gd")) {
        throw new RuntimeException(
            "The GD extension is not enabled. Enable GD in php.ini to resize images."
        );
    }

    switch ($mime) {
        case "image/jpeg":
            $source = imagecreatefromjpeg($file["tmp_name"]);
            break;

        case "image/png":
            $source = imagecreatefrompng($file["tmp_name"]);
            break;

        case "image/webp":
            if (!function_exists("imagecreatefromwebp")) {
                throw new RuntimeException("WebP support is unavailable in GD.");
            }
            $source = imagecreatefromwebp($file["tmp_name"]);
            break;

        default:
            $source = false;
    }

    if (!$source) {
        throw new RuntimeException("Unable to process the uploaded image.");
    }

    $width = imagesx($source);
    $height = imagesy($source);

    // Keep original proportions; maximum dimensions are 800 x 800
    $scale = min(1, 800 / $width, 800 / $height);
    $newWidth = max(1, (int) round($width * $scale));
    $newHeight = max(1, (int) round($height * $scale));

    $resized = imagecreatetruecolor($newWidth, $newHeight);

    // White background for transparent PNG/WebP images
    $white = imagecolorallocate($resized, 255, 255, 255);
    imagefill($resized, 0, 0, $white);

    imagecopyresampled(
        $resized,
        $source,
        0,
        0,
        0,
        0,
        $newWidth,
        $newHeight,
        $width,
        $height
    );

    // Generate a random filename; save every image as JPEG
    $filename = bin2hex(random_bytes(16)) . ".jpg";
    $destination = $uploadDir . $filename;

    $saved = imagejpeg($resized, $destination, 85);

    imagedestroy($source);
    imagedestroy($resized);

    if (!$saved) {
        throw new RuntimeException("Unable to save the resized image.");
    }

    return $filename;
}

// Delete an image belonging to this application
function deleteProductImage($filename, $uploadDir)
{
    if (
        $filename !== "" &&
        basename($filename) === $filename &&
        is_file($uploadDir . $filename)
    ) {
        unlink($uploadDir . $filename);
    }
}

// Add, update or delete products
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "save") {
        $id = (int) ($_POST["id"] ?? 0);
        $categoryId = (int) ($_POST["category_id"] ?? 0);
        $name = trim($_POST["name"] ?? "");
        $priceInput = trim($_POST["price"] ?? "");
        $description = trim($_POST["description"] ?? "");

        if (
            $name === "" ||
            $categoryId <= 0 ||
            $priceInput === "" ||
            !is_numeric($priceInput) ||
            (float) $priceInput < 0
        ) {
            $error = "Enter a valid name, category and non-negative price.";
        } else {
            $price = (float) $priceInput;

            // Verify that the selected category exists
            $check = $conn->prepare(
                "SELECT id FROM categories WHERE id = ?"
            );
            $check->bind_param("i", $categoryId);
            $check->execute();
            $categoryExists = $check->get_result()->num_rows > 0;
            $check->close();

            if (!$categoryExists) {
                $error = "Please select a valid category.";
            } else {
                $oldImage = "";

                if ($id > 0) {
                    $check = $conn->prepare(
                        "SELECT image FROM products WHERE id = ?"
                    );
                    $check->bind_param("i", $id);
                    $check->execute();
                    $existing = $check->get_result()->fetch_assoc();
                    $check->close();

                    if (!$existing) {
                        $error = "Product not found.";
                    } else {
                        $oldImage = $existing["image"] ?? "";
                    }
                }

                $newImage = "";
                $imageUploaded = false;

                if (
                    $error === "" &&
                    isset($_FILES["image"]) &&
                    $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
                ) {
                    try {
                        $newImage = saveProductImage($_FILES["image"], $uploadDir);
                        $imageUploaded = true;
                    } catch (RuntimeException $e) {
                        $error = $e->getMessage();
                    }
                }

                if ($error === "") {
                    if ($id > 0) {
                        if ($imageUploaded) {
                            $stmt = $conn->prepare(
                                "UPDATE products
                                 SET category_id = ?, name = ?, price = ?,
                                     description = ?, image = ?
                                 WHERE id = ?"
                            );
                            $stmt->bind_param(
                                "isdssi",
                                $categoryId,
                                $name,
                                $price,
                                $description,
                                $newImage,
                                $id
                            );
                        } else {
                            $stmt = $conn->prepare(
                                "UPDATE products
                                 SET category_id = ?, name = ?, price = ?,
                                     description = ?
                                 WHERE id = ?"
                            );
                            $stmt->bind_param(
                                "isdsi",
                                $categoryId,
                                $name,
                                $price,
                                $description,
                                $id
                            );
                        }
                    } else {
                        $stmt = $conn->prepare(
                            "INSERT INTO products
                             (category_id, name, price, description, image)
                             VALUES (?, ?, ?, ?, ?)"
                        );
                        $stmt->bind_param(
                            "isdss",
                            $categoryId,
                            $name,
                            $price,
                            $description,
                            $newImage
                        );
                    }

                    if ($stmt->execute()) {
                        $message = $id > 0
                            ? "Product updated successfully."
                            : "Product added successfully.";

                        if ($id > 0 && $imageUploaded) {
                            deleteProductImage($oldImage, $uploadDir);
                        }
                    } else {
                        if ($imageUploaded) {
                            deleteProductImage($newImage, $uploadDir);
                        }

                        $error = "Unable to save product: " . $stmt->error;
                    }

                    $stmt->close();
                }
            }
        }
    }

    if ($action === "delete") {
        $id = (int) ($_POST["id"] ?? 0);

        $stmt = $conn->prepare(
            "SELECT image FROM products WHERE id = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($product) {
            $stmt = $conn->prepare(
                "DELETE FROM products WHERE id = ?"
            );
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                deleteProductImage($product["image"] ?? "", $uploadDir);
                $message = "Product deleted successfully.";
            } else {
                $error = "Unable to delete product.";
            }

            $stmt->close();
        } else {
            $error = "Product not found.";
        }
    }
}

// Fetch categories for the form
$categories = $conn->query(
    "SELECT id, name FROM categories ORDER BY name"
);

// Load product for editing
$editProduct = null;
$editId = (int) ($_GET["edit"] ?? 0);

if ($editId > 0) {
    $stmt = $conn->prepare(
        "SELECT * FROM products WHERE id = ?"
    );
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editProduct = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Fetch product list
$products = $conn->query(
    "SELECT p.*, c.name AS category_name
     FROM products p
     INNER JOIN categories c ON p.category_id = c.id
     ORDER BY p.id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f4fb;
            margin: 0;
            padding: 25px;
        }

        .container {
            max-width: 1100px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        h2, h3 {
            color: #5c4ac7;
        }

        label {
            display: block;
            margin-top: 12px;
        }

        input, select, textarea {
            display: block;
            width: 100%;
            max-width: 450px;
            padding: 9px;
            margin-top: 5px;
            box-sizing: border-box;
        }

        button, .button {
            padding: 9px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .save {
            background: #5c4ac7;
            color: white;
            margin-top: 15px;
        }

        .edit {
            background: #eeeafd;
            color: #4636a3;
        }

        .delete {
            background: #b00020;
            color: white;
        }

        .message {
            color: green;
        }

        .error {
            color: #b00020;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background: #eeeafd;
        }

        .product-image {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Product Management</h2>

    <p>
        <a href="dashboard.php">Admin Dashboard</a> |
        <a href="categories.php">Manage Categories</a> |
        <a href="logout.php">Logout</a>
    </p>

    <?php if ($message !== ""): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <h3><?= $editProduct ? "Edit Product" : "Add Product" ?></h3>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save">
        <input
            type="hidden"
            name="id"
            value="<?= (int) ($editProduct["id"] ?? 0) ?>"
        >

        <label for="name">Product Name</label>
        <input
            id="name"
            type="text"
            name="name"
            maxlength="150"
            required
            value="<?= htmlspecialchars($editProduct["name"] ?? "") ?>"
        >

        <label for="category_id">Category</label>
        <select id="category_id" name="category_id" required>
            <option value="">Select category</option>

            <?php while ($category = $categories->fetch_assoc()): ?>
                <option
                    value="<?= (int) $category["id"] ?>"
                    <?= (int) ($editProduct["category_id"] ?? 0) === (int) $category["id"] ? "selected" : "" ?>
                >
                    <?= htmlspecialchars($category["name"]) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="price">Price (₹)</label>
        <input
            id="price"
            type="number"
            name="price"
            min="0"
            step="0.01"
            required
            value="<?= htmlspecialchars((string) ($editProduct["price"] ?? "")) ?>"
        >

        <label for="description">Description</label>
        <textarea
            id="description"
            name="description"
            rows="3"
        ><?= htmlspecialchars($editProduct["description"] ?? "") ?></textarea>

        <label for="image">Product Image (JPG, PNG or WebP; max 5 MB)</label>
        <input
            id="image"
            type="file"
            name="image"
            accept="image/jpeg,image/png,image/webp"
        >

        <?php if (!empty($editProduct["image"])): ?>
            <p>Current image:</p>
            <img
                class="product-image"
                src="../uploads/products/<?= rawurlencode(basename($editProduct["image"])) ?>"
                alt="Current product"
            >
            <p>Leave the image field empty to keep the current image.</p>
        <?php endif; ?>

        <button type="submit" class="save">
            <?= $editProduct ? "Update Product" : "Add Product" ?>
        </button>

        <?php if ($editProduct): ?>
            <a class="button" href="products.php">Cancel</a>
        <?php endif; ?>
    </form>

    <h3>All Products</h3>

    <table>
        <tr>
            <th>ID</th>
            <th>Image</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Actions</th>
        </tr>

        <?php while ($product = $products->fetch_assoc()): ?>
            <tr>
                <td><?= (int) $product["id"] ?></td>

                <td>
                    <?php if (!empty($product["image"])): ?>
                        <img
                            class="product-image"
                            src="../uploads/products/<?= rawurlencode(basename($product["image"])) ?>"
                            alt="<?= htmlspecialchars($product["name"]) ?>"
                        >
                    <?php else: ?>
                        No image
                    <?php endif; ?>
                </td>

                <td><?= htmlspecialchars($product["name"]) ?></td>

                <td><?= htmlspecialchars($product["category_name"]) ?></td>

                <td>₹<?= number_format((float) $product["price"], 2) ?></td>

                <td>
                    <a
                        class="button edit"
                        href="products.php?edit=<?= (int) $product["id"] ?>"
                    >Edit</a>

                    <form
                        method="POST"
                        style="display:inline"
                        onsubmit="return confirm('Delete this product?');"
                    >
                        <input type="hidden" name="action" value="delete">
                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int) $product["id"] ?>"
                        >
                        <button type="submit" class="delete">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>

<?php
$conn->close();
?>
