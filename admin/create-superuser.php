<?php

/**
 * Tanuki Framework — tanuki_admin
 *
 * One-off CLI script to create the first admin account.
 * Run from the project root:
 *
 *   php admin/create-superuser.php
 */

if (php_sapi_name() !== 'cli') {
    exit("This script can only be run from the command line.\n");
}

$root = __DIR__ . '/..';

require_once "$root/utils.php";
require_once "$root/config/database.php";
require_once "$root/core/Model.php";
require_once "$root/models/UserModel.php";

// Load .env (same parsing logic as App::loadEnv, kept standalone here
// since this script runs outside the normal App::run() bootstrap)
$envFile = "$root/.env";
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value, " \t\"'");
    }
}

function prompt(string $label): string
{
    echo $label;
    return trim((string) fgets(STDIN));
}

function promptHidden(string $label): string
{
    echo $label;
    $isWindows = stripos(PHP_OS, 'WIN') === 0;

    if ($isWindows) {
        // No portable way to hide input on Windows without extra tools.
        return trim((string) fgets(STDIN));
    }

    system('stty -echo');
    $value = trim((string) fgets(STDIN));
    system('stty echo');
    echo "\n";
    return $value;
}

echo "🦝 Tanuki Admin — Create superuser\n\n";

$name  = prompt('Name: ');
$email = prompt('Email: ');

if (UserModel::findByEmail($email) !== null) {
    exit("\nA user with that email already exists.\n");
}

$password = promptHidden('Password (min. 8 characters): ');
if (strlen($password) < 8) {
    exit("\nPassword must be at least 8 characters.\n");
}

$confirmation = promptHidden('Confirm password: ');
if ($password !== $confirmation) {
    exit("\nPasswords don't match.\n");
}

$id = UserModel::create([
    'name'     => $name,
    'email'    => $email,
    'password' => password_hash($password, PASSWORD_DEFAULT),
    'is_admin' => 1,
]);

if ($id) {
    echo "\n✅ Superuser created (id: $id).\n";
} else {
    exit("\n❌ Could not create the user.\n");
}