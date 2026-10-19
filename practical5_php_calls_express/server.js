
const express = require("express");

const app = express();
const PORT = 3000;

// Sample product data
const products = [
    { id: 1, name: "T-Shirt", price: 499 },
    { id: 2, name: "Mobile Phone", price: 12999 },
    { id: 3, name: "Laptop", price: 45999 }
];

// REST API endpoint
app.get("/api/products", (req, res) => {
    res.json({
        message: "Products fetched successfully",
        products: products
    });
});

app.listen(PORT, () => {
    console.log(`Express server running at http://localhost:${PORT}`);
});
