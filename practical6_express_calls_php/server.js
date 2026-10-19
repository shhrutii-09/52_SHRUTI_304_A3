
const express = require("express");

const app = express();
const PORT = 3001;

// Express calls the PHP REST API
app.get("/students", async (req, res) => {
    try {
        const response = await fetch("http://localhost:8000/api.php");

        if (!response.ok) {
            return res.status(response.status).json({
                error: "PHP API request failed"
            });
        }

        const data = await response.json();

        // Return PHP API data to the browser
        res.json({
            message: "Data received from PHP API",
            students: data.students
        });
    } catch (error) {
        res.status(500).json({
            error: "Unable to connect to PHP API",
            details: error.message
        });
    }
});

app.listen(PORT, () => {
    console.log(`Express server running at http://localhost:${PORT}`);
});
