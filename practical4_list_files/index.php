
<?php
echo "<h2>Files and Folders</h2>";

$files = scandir(__DIR__);

echo "<ul>";

foreach ($files as $file) {
    if ($file != "." && $file != "..") {
        echo "<li>" . htmlspecialchars($file) . "</li>";
    }
}

echo "</ul>";
?>
