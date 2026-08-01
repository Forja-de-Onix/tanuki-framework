<?php

/**
 * Modelo Todo
 *
 * Tabla: todo
 * Campos: id, title, description, completed, created_at, updated_at
 *
 * SQL para crear la tabla:
 * ─────────────────────────────────────────────
 * CREATE TABLE todo (
 *   id          INT AUTO_INCREMENT PRIMARY KEY,
 *   title       VARCHAR(255) NOT NULL,
 *   description TEXT,
 *   completed   TINYINT(1) DEFAULT 0,
 *   created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *   updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 * );
 * ─────────────────────────────────────────────
 */
class Todo extends Model
{
    protected static string $table = 'todo';
    protected static bool $timestamps = true;

    /** Devuelve todos los todos ordenados: pendientes primero, luego por fecha */
    public static function allOrdered(): array
    {
        $pdo  = Database::connect();
        $table = static::$table;
        $stmt = $pdo->query(
            "SELECT * FROM `$table` ORDER BY `completed` ASC, `created_at` DESC"
        );
        return $stmt->fetchAll();
    }

    /** Todos los todos pendientes */
    public static function pending(): array
    {
        return static::where('completed', 0);
    }

    /** Todos los todos completados */
    public static function completed(): array
    {
        return static::where('completed', 1);
    }
}
