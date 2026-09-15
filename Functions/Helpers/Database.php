<?php
declare(strict_types=1);

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
