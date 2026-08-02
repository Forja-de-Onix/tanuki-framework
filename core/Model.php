<?php

/**
 * Tanuki Framework — Base Model with lightweight Active Record
 *
 * Available methods (all static for simple usage):
 *   Model::all()
 *   Model::find($id)
 *   Model::where($column, $value)
 *   Model::where($column, $operator, $value)
 *   Model::first()
 *   Model::count()
 *   Model::paginate($page, $perPage)
 *   Model::create($data)         → returns the last inserted ID
 *   Model::update($id, $data)    → bool
 *   Model::delete($id)           → bool
 */
class Model
{
    /** Table name in the DB (required in each subclass) */
    protected static string $table = '';

    /**
     * If true, create() auto-fills created_at and
     * update() auto-fills updated_at with the current date.
     */
    protected static bool $timestamps = true;

    // ─── Identifier safety ──────────────────────────────────────────────────────

    /**
     * Validates that a column name is a simple identifier
     * (letters, numbers, underscore) to prevent SQL injection
     * via column names.
     *
     * @throws \InvalidArgumentException
     */
    private static function assertValidColumn(string $column): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
            throw new \InvalidArgumentException("Invalid column name: '$column'");
        }
    }

    /**
     * Quotes an identifier (table or column name) using the correct
     * escape character for the active DB driver.
     */
    private static function quoteIdent(string $ident): string
    {
        $driver = env('DB_DRIVER', 'mysql');
        return in_array($driver, ['pgsql', 'sqlite'], true) ? "\"$ident\"" : "`$ident`";
    }

    // ─── Read ─────────────────────────────────────────────────────────────────

    /** Returns all records from the table. */
    public static function all(string $orderBy = 'id', string $direction = 'ASC'): array
    {
        static::assertValidColumn($orderBy);
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $table   = static::quoteIdent(static::$table);
        $orderCol = static::quoteIdent($orderBy);

        $pdo  = Database::connect();
        $stmt = $pdo->query("SELECT * FROM $table ORDER BY $orderCol $direction");
        return $stmt->fetchAll();
    }

    /**
     * Finds a record by its primary key (id).
     * Returns the record array, or null if it doesn't exist.
     */
    public static function find(int $id): ?array
    {
        $table = static::quoteIdent(static::$table);
        $pdo   = Database::connect();
        $stmt  = $pdo->prepare("SELECT * FROM $table WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Filters records by a simple condition.
     *
     * Usage:
     *   User::where('email', 'john@example.com')
     *   User::where('age', '>', 18)
     *
     * @return array  Array of matching records
     */
    public static function where(string $column, mixed $operatorOrValue, mixed $value = null): array
    {
        static::assertValidColumn($column);

        if ($value === null) {
            $value    = $operatorOrValue;
            $operator = '=';
        } else {
            $operator = $operatorOrValue;
        }

        // Validate the operator to prevent injection
        $allowed = ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE'];
        $operator = in_array(strtoupper($operator), $allowed, true) ? $operator : '=';

        $table = static::quoteIdent(static::$table);
        $col   = static::quoteIdent($column);

        $pdo  = Database::connect();
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE $col $operator :value");
        $stmt->execute(['value' => $value]);
        return $stmt->fetchAll();
    }

    /**
     * Returns the first record in the table (ordered by id ASC).
     * Useful combined with where: can be called manually after where for a single result.
     */
    public static function first(): ?array
    {
        $table = static::quoteIdent(static::$table);
        $pdo   = Database::connect();
        $stmt  = $pdo->query("SELECT * FROM $table ORDER BY id ASC LIMIT 1");
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /** Counts the total number of records in the table. */
    public static function count(): int
    {
        $table = static::quoteIdent(static::$table);
        $pdo   = Database::connect();
        $stmt  = $pdo->query("SELECT COUNT(*) FROM $table");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Simple pagination.
     *
     * @param int $page     Current page (starts at 1)
     * @param int $perPage  Records per page
     * @return array{
     *   data:    array,
     *   total:   int,
     *   pages:   int,
     *   current: int,
     *   perPage: int
     * }
     */
    public static function paginate(int $page = 1, int $perPage = 15): array
    {
        $page    = max(1, $page);
        $offset  = ($page - 1) * $perPage;
        $total   = static::count();
        $pages   = (int) ceil($total / $perPage);

        $table = static::quoteIdent(static::$table);
        $pdo   = Database::connect();
        $stmt  = $pdo->prepare(
            "SELECT * FROM $table ORDER BY id DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data'    => $stmt->fetchAll(),
            'total'   => $total,
            'pages'   => $pages,
            'current' => $page,
            'perPage' => $perPage,
        ];
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    /**
     * Inserts a new record.
     *
     * @param  array     $data  Data to insert (column => value)
     * @return int|false        Inserted record ID, or false on error
     */
    public static function create(array $data): int|false
    {
        if (static::$timestamps) {
            $now = date('Y-m-d H:i:s');
            $data['created_at'] = $data['created_at'] ?? $now;
            $data['updated_at'] = $data['updated_at'] ?? $now;
        }

        foreach (array_keys($data) as $key) {
            static::assertValidColumn($key);
        }

        $table        = static::quoteIdent(static::$table);
        $columns      = implode(', ', array_map(fn($k) => static::quoteIdent($k), array_keys($data)));
        $placeholders = ':' . implode(', :', array_keys($data));
        $pdo          = Database::connect();
        $stmt         = $pdo->prepare("INSERT INTO $table ($columns) VALUES ($placeholders)");

        if ($stmt->execute($data)) {
            return (int) $pdo->lastInsertId();
        }
        return false;
    }

    /**
     * Updates an existing record by its id.
     *
     * @param  int   $id    Record ID
     * @param  array $data  Fields to update
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        if (static::$timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        foreach (array_keys($data) as $key) {
            static::assertValidColumn($key);
        }

        $table  = static::quoteIdent(static::$table);
        $fields = implode(', ', array_map(fn($k) => static::quoteIdent($k) . " = :$k", array_keys($data)));
        $data['id'] = $id;

        $pdo  = Database::connect();
        $stmt = $pdo->prepare("UPDATE $table SET $fields WHERE id = :id");
        return $stmt->execute($data);
    }

    /**
     * Deletes a record by its id.
     *
     * @param  int  $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        $table = static::quoteIdent(static::$table);
        $pdo   = Database::connect();
        $stmt  = $pdo->prepare("DELETE FROM $table WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}