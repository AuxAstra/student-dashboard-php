<?php
// config.php - DB config + class loader
// Include this ONE file at the top of any page that needs
// database access. It loads all classes automatically.

// DATABASE CONFIGURATION
// Change host/dbname/username/password to match your setup
$config = [
    'host'     => 'localhost',
    'dbname'   => 'students_db',
    'username' => 'root',
    'password' => '',         
];

// AUTOLOAD ALL CLASSES
// Loads every .php file inside the /classes folder
foreach (glob(__DIR__ . '/classes/*.php') as $file) {
    require_once $file;
}

// CREATE THE DATABASE CONNECTION (single shared instance)
$database = new Database($config);
$conn     = $database->getConnection();