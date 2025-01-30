<?php
// Load database configuration from Lab5.ini
$config = parse_ini_file("Project.ini", true);

if (!$config || !isset($config['database'])) {
    die("Configuration file missing or incorrect.");
}

// Extract the database connection details
$host = $config['database']['host'];
$dbname = $config['database']['dbname'];
$username = $config['database']['user'];
$password = $config['database']['password'];

// Set PDO options
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    // Create PDO instance with data from the configuration file
    $dsn = "mysql:host=$host;dbname=$dbname";
    $pdo = new PDO($dsn, $username, $password, $options);
    // Uncomment the line below temporarily to confirm connection success
    // echo "Database connection successful.";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
