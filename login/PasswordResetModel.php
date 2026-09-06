<?php

require_once __DIR__ . "/../core/Model.php";
require_once __DIR__ . "/UserModel.php";

class PasswordResetModel extends Model
{
    protected static string $table = 'password_resets';
    protected static bool $timestamps = false;

    /**
     * Finds a still-valid (non-expired) reset record by its hashed token.
     */
    public static function findValidByToken(string $hashedToken): ?array
    {
        $table = static::quoteIdent(static::$table);
        $pdo   = Database::connect();
        $stmt  = $pdo->prepare("SELECT * FROM $table WHERE token = :token AND expires_at > :now LIMIT 1");
        $stmt->execute(['token' => $hashedToken, 'now' => date('Y-m-d H:i:s')]);
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    public static function deleteByEmail(string $email): void
    {
        $table = static::quoteIdent(static::$table);
        $pdo   = Database::connect();
        $stmt  = $pdo->prepare("DELETE FROM $table WHERE email = :email");
        $stmt->execute(['email' => $email]);
    }
}