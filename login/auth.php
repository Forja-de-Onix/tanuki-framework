<?php

/**
 * Tanuki Framework — tanuki_login extension
 *
 * Session-based auth helpers. Loaded via a require_once in App.php
 * (or index.php) once the extension is installed.
 */

/** Returns true if a user is currently logged in. */
function auth_check(): bool
{
    session_ensure();
    return !empty($_SESSION['auth_user_id']);
}

/** Returns the current logged-in user's record, or null. */
function auth_user(): ?array
{
    session_ensure();
    if (empty($_SESSION['auth_user_id'])) {
        return null;
    }
    return UserModel::find((int) $_SESSION['auth_user_id']);
}

/** Logs a user in by storing their id in the session. */
function auth_login(array $user): void
{
    session_ensure();
    session_regenerate_id(true); // prevent session fixation
    $_SESSION['auth_user_id'] = $user['id'];
}

/** Logs the current user out. */
function auth_logout(): void
{
    session_ensure();
    unset($_SESSION['auth_user_id']);
    session_regenerate_id(true);
}

/**
 * Guards a controller action: redirects to /login if no user is
 * authenticated, and stops execution. Call it as the first line
 * of any protected controller method.
 *
 *   public function dashboard(): void
 *   {
 *       auth_require();
 *       // ... rest of the method, only reached if logged in ...
 *   }
 */
function auth_require(): void
{
    if (!auth_check()) {
        session_ensure();
        $_SESSION['auth_redirect_to'] = current_uri();
        redirect('/login');
    }
}