<?php
/**
 * Database connection (PDO).
 */

$host    = 'localhost';
$port    = 3307;      // default MySQL port. 
$db      = 'farmers_marketplace';
$user    = 'root';   // change to your MySQL username if different
$pass    = '';        // change to your MySQL password if you set one
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // In production, log this instead of echoing it
    die('Database connection failed: ' . $e->getMessage());
}