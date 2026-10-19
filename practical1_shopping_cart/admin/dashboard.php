
<?php
require_once __DIR__ . "/../includes/auth.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <!-- <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f4fb;
            margin: 0;
        }

        header {
            background: #cbc4f3;
            color: white;
            padding: 20px;
        }

        main {
            padding: 25px;
        }

        a {
            display: inline-block;
            /* margin: 8px 8px 8px 0; */
            padding: 12px 16px;
            background: white;
            /* color: #5c4ac7; */
            text-decoration: none;
            border-radius: 5px;
        }

        .logout {
            background: #b00020;
            color: white;
        }
    </style> -->
</head>
<body>

<header>
    <h2>Shopping Cart Admin Panel</h2>
    Welcome, <?= htmlspecialchars($_SESSION["admin_username"]) ?>!
</header>

<main>
    <h3>Dashboard</h3>
    <p>Manage your shopping website from here.</p>

    <a href="categories.php">Manage Categories</a>   ||
    <a href="products.php">Manage Products</a>    ||
    <a href="../index.php">View Website</a>   ||
    <a href="logout.php" class="logout">Logout</a>
</main>

</body>
</html>
    