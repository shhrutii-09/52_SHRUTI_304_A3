
<?php
// Step 1: Connect to MySQL
$conn = new mysqli("localhost", "root", "Shruti@2005", "php_practical_db");

if ($conn->connect_error) {
    die("MySQL connection failed: " . $conn->connect_error);
}

// Step 2: Fetch student records
$sql = "SELECT id, name, age, course, email FROM students";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

// Step 3: Create XML document
$xml = new DOMDocument("1.0", "UTF-8");
$xml->formatOutput = true;

$root = $xml->createElement("students");
$xml->appendChild($root);

// Step 4: Convert each database record into XML
while ($row = $result->fetch_assoc()) {
    $student = $xml->createElement("student");
    $root->appendChild($student);

    foreach ($row as $key => $value) {
        $element = $xml->createElement($key);
        $element->appendChild($xml->createTextNode((string) $value));
        $student->appendChild($element);
    }
}

// Step 5: Save XML to a file
$filePath = __DIR__ . "/students_export.xml";

if ($xml->save($filePath) === false) {
    die("Error: Unable to save XML file.");
}

echo "<h2>MySQL to XML Export</h2>";
echo "<p>Student records exported successfully!</p>";
echo "<p>Total records exported: " . $result->num_rows . "</p>";
echo "<p>File saved as: students_export.xml</p>";

$conn->close();
?>
