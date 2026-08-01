<?php

/**
 * Tanuki Framework — MongoDB connection
 *
 * Reads connection details from environment variables (.env) through
 * the env() function. Implements a Singleton pattern to reuse the
 * same client throughout the request lifecycle.
 *
 * Requires:
 *   - The native `mongodb` PHP extension
 *   - The `mongodb/mongodb` Composer package (composer require mongodb/mongodb)
 *
 * Required environment variables (see .env-example):
 *   MONGO_URI, MONGO_DATABASE
 *
 * Usage example (no shared interface with Database/Redis — use the native API directly):
 *   Mongo::connect()->selectCollection('posts')->find(['status' => 'published']);
 *   Mongo::connect()->selectCollection('posts')->insertOne(['title' => 'Hello']);
 */
class Mongo
{
    private static ?\MongoDB\Database $db = null;

    /**
     * Returns the MongoDB database instance (creates the connection on first call).
     *
     * @throws \MongoDB\Driver\Exception\Exception  Only in debug mode (APP_DEBUG=true)
     */
    public static function connect(): \MongoDB\Database
    {
        if (self::$db !== null) {
            return self::$db;
        }

        $uri      = env('MONGO_URI', 'mongodb://127.0.0.1:27017');
        $database = env('MONGO_DATABASE', '');

        try {
            $client   = new \MongoDB\Client($uri);
            self::$db = $client->selectDatabase($database);
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            error_log('[Tanuki:Mongo] Connection error: ' . $e->getMessage());

            if (env('APP_DEBUG', 'false') === 'true') {
                throw $e;
            }

            http_response_code(503);
            view('errors/503');
            exit;
        }

        return self::$db;
    }

    /**
     * Resets the connection (useful in tests).
     */
    public static function reset(): void
    {
        self::$db = null;
    }
}