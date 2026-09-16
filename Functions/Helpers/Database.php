<?php
declare(strict_types=1);

require_once __DIR__ . '/DatabaseConfig.php';

function maple_load_database_connection()
{
    global $conn;

    require_once __DIR__ . '/../../Includes/DataBase.php';
}

function maple_db(): mysqli
{
    maple_load_database_connection();

    global $conn;

    if (!$conn instanceof mysqli) {
        throw new RuntimeException('Database connection unavailable.');
    }

    return $conn;
}

function maple_pdo()
{
    static $pdo = null;

    if (!class_exists('PDO')) {
        throw new RuntimeException('PDO is not available.');
    }

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = maple_database_config();
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['database'],
        $config['charset']
    );

    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function maple_table_has_column(string $table, string $column): bool
{
    $allowedTables = [
        'User' => 'User',
        'Roles' => 'Roles',
    ];

    if (!isset($allowedTables[$table])) {
        return false;
    }

    try {
        $db = maple_db();
        $tableName = $allowedTables[$table];
        $statement = $db->prepare('
            SELECT COUNT(*) AS column_count
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
                AND LOWER(TABLE_NAME) = LOWER(?)
                AND COLUMN_NAME = ?
        ');

        if (!$statement) {
            return false;
        }

        $statement->bind_param('ss', $tableName, $column);
        $statement->execute();
        $result = $statement->get_result();
        $row = $result instanceof mysqli_result ? $result->fetch_assoc() : null;
        $hasColumn = $row !== null && (int) $row['column_count'] > 0;
        $statement->close();

        return $hasColumn;
    } catch (Throwable $exception) {
        return false;
    }
}
