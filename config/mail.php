<?php

/**
 * Tanuki Framework — Mail system
 *
 * Thin wrapper around PHPMailer, following the same lazy Singleton
 * pattern as Database/Redis/Mongo. Nothing connects to the SMTP
 * server until send() is actually called.
 *
 * Required environment variables (see .env-example):
 *   MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD,
 *   MAIL_ENCRYPTION, MAIL_FROM_ADDRESS, MAIL_FROM_NAME
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class Mail
{
    private static ?PHPMailer $mailer = null;

    private static function client(): PHPMailer
    {
        if (self::$mailer !== null) {
            return self::$mailer;
        }

        $mailer = new PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host       = env('MAIL_HOST', 'localhost');
        $mailer->Port       = (int) env('MAIL_PORT', '587');
        $mailer->SMTPAuth   = true;
        $mailer->Username   = env('MAIL_USERNAME', '');
        $mailer->Password   = env('MAIL_PASSWORD', '');
        $mailer->SMTPSecure = env('MAIL_ENCRYPTION', 'tls'); // 'tls' or 'ssl'
        $mailer->CharSet    = 'UTF-8';
        $mailer->setFrom(
            env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
            env('MAIL_FROM_NAME', env('APP_NAME', 'Tanuki App'))
        );

        return self::$mailer = $mailer;
    }

    /**
     * Sends an HTML email.
     *
     * @return bool  true on success, false on failure (logged, never thrown to the user)
     */
    public static function send(string $to, string $subject, string $htmlBody): bool
    {
        try {
            $mailer = self::client();
            $mailer->clearAddresses();
            $mailer->addAddress($to);
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body    = $htmlBody;
            $mailer->AltBody = strip_tags($htmlBody);

            return $mailer->send();
        } catch (PHPMailerException $e) {
            error_log('[Tanuki:Mail] Send error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Resets the client (useful in tests).
     */
    public static function reset(): void
    {
        self::$mailer = null;
    }
}