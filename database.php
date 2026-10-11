<?php
/**
 * Koneksi database (PDO).
 * Default: MySQL/MariaDB (XAMPP). Untuk demo tanpa MySQL set env DB_DRIVER=sqlite.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $driver = getenv('DB_DRIVER') ?: 'mysql';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    if ($driver === 'sqlite') {
        $path = getenv('SQLITE_PATH') ?: __DIR__ . '/../database/inventory.sqlite';
        $pdo = new PDO('sqlite:' . $path, null, null, $options);
        $pdo->exec('PRAGMA foreign_keys = ON');
    } else {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $name = getenv('DB_NAME') ?: 'inventory_db';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, $options);
    }
    return $pdo;
}

/** GROUP_CONCAT berbeda sintaks antara MySQL dan SQLite. */
function sql_group_concat(string $col): string
{
    return (getenv('DB_DRIVER') ?: 'mysql') === 'sqlite'
        ? "GROUP_CONCAT($col, ', ')"
        : "GROUP_CONCAT($col SEPARATOR ', ')";
}
