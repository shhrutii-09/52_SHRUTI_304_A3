
<?php
echo "<h2>XML to MySQL Import</h2>";

$xmlFile = __DIR__ . "/students.xml";

if (!file_exists($xmlFile)) {
    die("Error: students.xml file not found.");
}

libxml_use_internal_errors(true);
$xml = simplexml_load_file($xmlFile);

if ($xml === false) {
    echo "Error: Unable to read XML file.<br>";

    foreach (libxml_get_errors() as $error) {
        echo htmlspecialchars(trim($error->message)) . "<br>";
    }

    libxml_clear_errors();
    exit;
}

$conn = new mysqli(
    "127.0.0.1",
    "root",
    "Shruti@2005",
    "php_practical_db",
    3306
);

if ($conn->connect_error) {
    die("MySQL connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$sql = "CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    course VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL
)";

if (!$conn->query($sql)) {
    die("Table creation failed: " . $conn->error);
}

$stmt = $conn->prepare(
    "INSERT INTO students (name, age, course, email)
     VALUES (?, ?, ?, ?)"
);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$count = 0;

foreach ($xml->student as $student) {
    $name = (string) $student->name;
    $age = (int) $student->age;
    $course = (string) $student->course;
    $email = (string) $student->email;

    $stmt->bind_param("siss", $name, $age, $course, $email);

    if ($stmt->execute()) {
        echo "<p>Inserted: " . htmlspecialchars($name) . "</p>";
        $count++;
    } else {
        echo "<p>Insert failed: " .
             htmlspecialchars($stmt->error) . "</p>";
    }
}

echo "<h3>Total records inserted: $count</h3>";

$stmt->close();
$conn->close();
?>
