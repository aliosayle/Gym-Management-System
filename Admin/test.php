<?php
// Test file to debug issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP is working!<br>";
echo "PHP version: " . phpversion() . "<br>";

// Test database connection
echo "<h3>Testing Database Connection:</h3>";
try {
    include 'layouts/config.php';
    echo "Database connection successful!<br>";
    
    // Test if layouts files exist
    echo "<h3>Testing Layout Files:</h3>";
    $layouts = ['head-main.php', 'head.php', 'head-style.php', 'body.php', 'menu.php', 'footer.php', 'vendor-scripts.php'];
    foreach ($layouts as $layout) {
        $path = 'layouts/' . $layout;
        echo "File $path: " . (file_exists($path) ? "Exists" : "MISSING") . "<br>";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
}
?> 