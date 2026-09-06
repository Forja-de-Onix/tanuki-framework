<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ .'/../core/HasOwnViews.php';

class AuthController extends Controller
{
    use HasOwnViews;

    private const TOKEN_TTL_MINUTES = 60;

    // ── GET /login ───────────────────────────────────────────────────────────

    public function showLogin(): void
    {
        if (auth_check()) {
            $this->redirect('/');
        }
        $this->view('auth/login', [
            'title' => t('auth.login_title') . ' — ' . env('APP_NAME', 'Tanuki App'),
        ]);
    }

    // ── POST /login ──────────────────────────────────────────────────────────

    public function login(): void
    {
        if (!csrf_verify($this->request->post('_token'))) {
            $this->flash('error', t('auth.session_expired'));
            $this->redirect('/login');
        }

        $email    = trim((string) $this->request->post('email'));
        $password = (string) $this->request->post('password');

        $user = UserModel::findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            keep_old(['email' => $email]);
            $this->flash('error', t('auth.invalid_credentials'));
            $this->redirect('/login');
        }

        auth_login($user);

        session_ensure();
        $redirectTo = $_SESSION['auth_redirect_to'] ?? '/';
        unset($_SESSION['auth_redirect_to']);

        $this->redirect($redirectTo);
    }

    // ── POST /logout ─────────────────────────────────────────────────────────

    public function logout(): void
    {
        if (!csrf_verify($this->request->post('_token'))) {
            $this->redirect('/');
        }
        auth_logout();
        $this->redirect('/');
    }

    // ── GET /register ────────────────────────────────────────────────────────

    public function showRegister(): void
    {
        if (auth_check()) {
            $this->redirect('/');
        }
        $this->view('auth/register', [
            'title' => t('auth.register_title') . ' — ' . env('APP_NAME', 'Tanuki App'),
        ]);
    }

    // ── POST /register ───────────────────────────────────────────────────────

    public function register(): void
    {
        if (!csrf_verify($this->request->post('_token'))) {
            $this->flash('error', t('auth.session_expired'));
            $this->redirect('/register');
        }

        $name     = trim((string) $this->request->post('name'));
        $email    = trim((string) $this->request->post('email'));
        $password = (string) $this->request->post('password');

        if (empty($name) || empty($email) || strlen($password) < 8) {
            keep_old(['name' => $name, 'email' => $email]);
            $this->flash('error', t('auth.register_validation_failed'));
            $this->redirect('/register');
        }

        if (UserModel::findByEmail($email) !== null) {
            keep_old(['name' => $name, 'email' => $email]);
            $this->flash('error', t('auth.email_taken'));
            $this->redirect('/register');
        }

        $userId = UserModel::create([
            'name'     => $name,
            'email'    => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        if (!$userId) {
            $this->flash('error', t('auth.register_failed'));
            $this->redirect('/register');
        }

        $user = UserModel::find($userId);
        auth_login($user);
        $this->flash('success', t('auth.welcome'));
        $this->redirect('/');
    }

    // ── GET /forgot-password ─────────────────────────────────────────────────

    public function showForgot(): void
    {
        $this->view('auth/forgot', [
            'title' => t('auth.forgot_title') . ' — ' . env('APP_NAME', 'Tanuki App'),
        ]);
    }

    // ── POST /forgot-password ────────────────────────────────────────────────

    public function sendResetLink(): void
    {
        if (!csrf_verify($this->request->post('_token'))) {
            $this->flash('error', t('auth.session_expired'));
            $this->redirect('/forgot-password');
        }

        $email = trim((string) $this->request->post('email'));
        $user  = UserModel::findByEmail($email);

        // Always show the same message, whether or not the email exists —
        // this prevents leaking which emails are registered.
        $this->flash('success', t('auth.reset_link_sent'));

        if ($user === null) {
            $this->redirect('/forgot-password');
        }

        $plainToken  = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $plainToken);

        PasswordResetModel::deleteByEmail($email);
        PasswordResetModel::create([
            'email'      => $email,
            'token'      => $hashedToken,
            'expires_at' => date('Y-m-d H:i:s', time() + self::TOKEN_TTL_MINUTES * 60),
        ]);

        $resetUrl = url('/reset-password/' . $plainToken);
        $html = '<p>' . e(t('auth.reset_email_body')) . '</p>'
              . '<p><a href="' . e($resetUrl) . '">' . e($resetUrl) . '</a></p>';

        Mail::send($email, t('auth.reset_email_subject'), $html);

        $this->redirect('/forgot-password');
    }

    // ── GET /reset-password/{token} ──────────────────────────────────────────

    public function showReset(string $token): void
    {
        $hashedToken = hash('sha256', $token);
        $record = PasswordResetModel::findValidByToken($hashedToken);

        if ($record === null) {
            $this->flash('error', t('auth.reset_link_invalid'));
            $this->redirect('/forgot-password');
        }

        $this->view('auth/reset', [
            'title' => t('auth.reset_title') . ' — ' . env('APP_NAME', 'Tanuki App'),
            'token' => $token,
        ]);
    }

    // ── POST /reset-password/{token} ─────────────────────────────────────────

    public function resetPassword(string $token): void
    {
        if (!csrf_verify($this->request->post('_token'))) {
            $this->flash('error', t('auth.session_expired'));
            $this->redirect("/reset-password/$token");
        }

        $hashedToken = hash('sha256', $token);
        $record = PasswordResetModel::findValidByToken($hashedToken);

        if ($record === null) {
            $this->flash('error', t('auth.reset_link_invalid'));
            $this->redirect('/forgot-password');
        }

        $password = (string) $this->request->post('password');

        if (strlen($password) < 8) {
            $this->flash('error', t('auth.password_too_short'));
            $this->redirect("/reset-password/$token");
        }

        $user = UserModel::findByEmail($record['email']);
        if ($user !== null) {
            UserModel::update($user['id'], [
                'password' => password_hash($password, PASSWORD_DEFAULT),
            ]);
        }

        PasswordResetModel::deleteByEmail($record['email']);

        $this->flash('success', t('auth.password_reset_success'));
        $this->redirect('/login');
    }
}