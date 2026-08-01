<?php

/**
 * Tanuki Framework — Redis connection
 *
 * Reads connection details from environment variables (.env) through
 * the env() function. Implements a Singleton pattern to reuse the
 * same connection throughout the request lifecycle.
 *
 * Requires the native `redis` PHP extension (php-redis).
 *
 * Required environment variables (see .env-example):
 *   REDIS_HOST, REDIS_PORT, REDIS_PASSWORD, REDIS_DATABASE
 *
 * Usage example (no shared interface with Database/Mongo — use the native API directly):
 *   Redis::connect()->set('key', 'value');
 *   Redis::connect()->get('key');
 *   Redis::connect()->expire('key', 3600);
 */
class Redis
{
    private static ?\Redis $client = null;

    /**
     * Returns the Redis client instance (creates the connection on first call).
     *
     * @throws \RedisException  Only in debug mode (APP_DEBUG=true)
     */
    public static function connect(): \Redis
    {
        if (self::$client !== null) {
            return self::$client;
        }

        $host     = env('REDIS_HOST', '127.0.0.1');
        $port     = (int) env('REDIS_PORT', '6379');
        $password = env('REDIS_PASSWORD', '');
        $database = (int) env('REDIS_DATABASE', '0');

        try {
            $client = new \Redis();
            $client->connect($host, $port);

            if ($password !== '') {
                $client->auth($password);
            }

            if ($database > 0) {
                $client->select($database);
            }

            self::$client = $client;
        } catch (\RedisException $e) {
            error_log('[Tanuki:Redis] Connection error: ' . $e->getMessage());

            if (env('APP_DEBUG', 'false') === 'true') {
                throw $e;
            }

            http_response_code(503);
            view('errors/503');
            exit;
        }

        return self::$client;
    }

    /**
     * Resets the connection (useful in tests).
     */
    public static function reset(): void
    {
        self::$client = null;
    }
}