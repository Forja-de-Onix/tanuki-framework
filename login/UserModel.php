<?php

require_once __DIR__ . "/../core/Model.php";
require_once __DIR__ . "/UserModel.php";

class UserModel extends Model
{
    protected static string $table = 'users';
    protected static bool $timestamps = true;

    public static function findByEmail(string $email): ?array
    {
        $results = static::where('email', $email);
        return $results[0] ?? null;
    }
}