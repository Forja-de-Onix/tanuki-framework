<?php

/**
 * Tanuki Framework — Database configuration
 *
 * Reads credentials from environment variables (.env) through
 * the env() function. Implements a Singleton pattern to reuse
 * the PDO connection throughout the request lifecycle.
 *
 * Required environment variables (see .env-example):
 *   DB_DRIVER (mysql|pgsql), DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_CHARSET
 */
class Database
{
    private static ?PDO $pdo = null;

    /**
     * Returns the PDO instance (creates the connection on first call).
     *
     * @throws PDOException  Only in debug mode (APP_DEBUG=true)
     */
    public static function connect(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $driver  = env('DB_DRIVER',  'mysql');
        $host    = env('DB_HOST',    'localhost');
        $port    = env('DB_PORT',    $driver === 'pgsql' ? '5432' : '3306');
        $dbname  = env('DB_NAME',    '');
        $user    = env('DB_USER',    'root');
        $pass    = env('DB_PASS',    '');
        $charset = env('DB_CHARSET', 'utf8mb4');

        $dsn = match ($driver) {
            'pgsql' => "pgsql:host=$host;port=$port;dbname=$dbname",
            default => "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset",
        };

        try {
            self::$pdo = new PDO(
                $dsn,
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            // Log the real error server-side (never expose it to the user)
            error_log('[Tanuki:DB] Connection error: ' . $e->getMessage());

            // In debug mode we show the detail; in production, a generic message
            if (env('APP_DEBUG', 'false') === 'true') {
                throw $e;
            }

            http_response_code(503);
            view('errors/503');
            exit;
        }

        return self::$pdo;
    }

    /**
     * Resets the connection (useful in tests).
     */
    public static function reset(): void
    {
        self::$pdo = null;
    }
}