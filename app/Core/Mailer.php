<?php

declare(strict_types=1);

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

/**
 * SMTP mail sender (PHPMailer). Falls back to logging when SMTP is not
 * configured so the app remains usable in local/dev environments.
 */
class Mailer
{
    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $altBody = ''): bool
    {
        $cfg = config('mail');

        // No SMTP host configured -> log the message instead of sending.
        if (empty($cfg['host'])) {
            error_log("[MAIL:disabled] To: {$toEmail} | Subject: {$subject}\n{$htmlBody}");
            return false;
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $cfg['host'];
            $mail->Port       = (int) $cfg['port'];
            $mail->SMTPAuth   = !empty($cfg['username']);
            $mail->Username   = $cfg['username'];
            $mail->Password   = $cfg['password'];
            if (!empty($cfg['encryption'])) {
                $mail->SMTPSecure = $cfg['encryption'] === 'ssl'
                    ? PHPMailer::ENCRYPTION_SMTPS
                    : PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($cfg['from_addr'], $cfg['from_name']);
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $altBody ?: strip_tags($htmlBody);

            $mail->send();
            return true;
        } catch (MailException $e) {
            error_log('Mail send failed: ' . $mail->ErrorInfo);
            return false;
        }
    }
}
