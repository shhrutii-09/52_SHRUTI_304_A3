
<?php
// Express REST API URL
$url = "http://localhost:3000/api/products";

// Initialize cURL
$curl = curl_init($url);

// Set cURL options
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

// Execute API request
$response = curl_exec($curl);

// Check for errors
if ($response === false) {
    die("API Error: " . htmlspecialchars(curl_error($curl)));
}

// Get HTTP status code
$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

// Close cURL
curl_close($curl);

// Convert JSON response into a PHP array
$data = json_decode($response, true);
?>

<!DOCTYPE html>
<html>
<head>
    <title>PHP Calling Express API</title>
</head>
<body>

<h2>Products from Express REST API</h2>

<?php
if ($statusCode == 200 && isset($data["products"])) {
    echo "<p>" . htmlspecialchars($data["message"]) . "</p>";

    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Product Name</th><th>Price</th></tr>";

    foreach ($data["products"] as $product) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars((string)$product["id"]) . "</td>";
        echo "<td>" . htmlspecialchars($product["name"]) . "</td>";
        echo "<td>₹" . htmlspecialchars((string)$product["price"]) . "</td>";
        echo "</tr>";
    }

    echo "</table>";
} else {
    echo "<p>Failed to fetch products. HTTP Status: "
        . htmlspecialchars((string)$statusCode) . "</p>";
}
?>

</body>
</html>
