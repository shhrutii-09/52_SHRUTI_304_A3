
<?php
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

$message = "";
$error = "";

// Add or update category
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    $name = trim($_POST["name"] ?? "");

    if ($action === "save") {
        if ($name === "") {
            $error = "Category name is required.";
        } else {
            $id = (int) ($_POST["id"] ?? 0);

            if ($id > 0) {
                $stmt = $conn->prepare(
                    "UPDATE categories SET name = ? WHERE id = ?"
                );
                $stmt->bind_param("si", $name, $id);
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO categories (name) VALUES (?)"
                );
                $stmt->bind_param("s", $name);
            }

            if ($stmt->execute()) {
                $message = $id > 0
                    ? "Category updated successfully."
                    : "Category added successfully.";
            } else {
                $error = $stmt->errno === 1062
                    ? "This category already exists."
                    : "Unable to save category.";
            }

            $stmt->close();
        }
    }

    // Delete category
    if ($action === "delete") {
        $id = (int) ($_POST["id"] ?? 0);

        if ($id > 0) {
            $stmt = $conn->prepare(
                "DELETE FROM categories WHERE id = ?"
            );
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $message = $stmt->affected_rows > 0
                    ? "Category deleted successfully."
                    : "Category not found.";
            } else {
                $error = "Cannot delete this category. It may contain products.";
            }

            $stmt->close();
        }
    }
}

// Load category details for editing
$editCategory = null;
$editId = (int) ($_GET["edit"] ?? 0);

if ($editId > 0) {
    $stmt = $conn->prepare(
        "SELECT id, name FROM categories WHERE id = ?"
    );
    $stmt->bind_param("i", $editId);
    $stmt->execute();

    $editCategory = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Fetch all categories
$result = $conn->query(
    "SELECT id, name FROM categories ORDER BY id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f4fb;
            margin: 0;
            padding: 25px;
        }

        /* .container {
            max-width: 850px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        } */

        /* h2, h3 {
            color: #bfb6f4;
        } */
/* 
        input {
            padding: 10px;
            width: 65%;
            max-width: 350px;
        }

        button, .button {
            padding: 10px 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .save {
            background: #5c4ac7;
            color: white;
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
        } */

        table {
            width: 100%;
            /* border-collapse: collapse; */
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background: #eeeafd;
        } 
    </style>
</head>
<body>

<div class="container">
    <h2>Category Management</h2>

    <p>
        <a href="dashboard.php">Admin Dashboard</a> |
        <a href="logout.php">Logout</a>
    </p>

    <?php if ($message !== ""): ?>
        <p class="message">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <p class="error">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <h3>
        <?= $editCategory ? "Edit Category" : "Add New Category" ?>
    </h3>

    <form method="POST">
        <input type="hidden" name="action" value="save">

        <input
            type="hidden"
            name="id"
            value="<?= (int) ($editCategory["id"] ?? 0) ?>"
        >

        <input
            type="text"
            name="name"
            placeholder="Enter category name"
            maxlength="100"
            value="<?= htmlspecialchars($editCategory["name"] ?? "") ?>"
            required
        >

        <button type="submit" class="save">
            <?= $editCategory ? "Update Category" : "Add Category" ?>
        </button>

        <?php if ($editCategory): ?>
            <a href="categories.php">Cancel</a>
        <?php endif; ?>
    </form>

    <h3>All Categories</h3>

    <table>
        <tr>
            <th>ID</th>
            <th>Category Name</th>
            <th>Actions</th>
        </tr>

        <?php while ($category = $result->fetch_assoc()): ?>
            <tr>
                <td><?= (int) $category["id"] ?></td>

                <td>
                    <?= htmlspecialchars($category["name"]) ?>
                </td>

                <td>
                    <a
                        class="button edit"
                        href="categories.php?edit=<?= (int) $category["id"] ?>"
                    >Edit</a>

                    <form
                        method="POST"
                        style="display:inline"
                        onsubmit="return confirm('Delete this category?');"
                    >
                        <input type="hidden" name="action" value="delete">
                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int) $category["id"] ?>"
                        >

                        <button type="submit" class="delete">
                            Delete
                        </button>
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
