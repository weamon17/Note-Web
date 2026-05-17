<?php
declare(strict_types=1);

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

class Mailer
{
    private PHPMailer $mail;
    private ?string   $error = null;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        $this->configure();
    }

    private function configure(): void
    {
        $m = $this->mail;

        $m->isSMTP();
        $m->Host       = getenv('MAIL_HOST')       ?: 'smtp.mailtrap.io';
        $m->SMTPAuth   = true;
        $m->Username   = getenv('MAIL_USERNAME')   ?: '';
        $m->Password   = getenv('MAIL_PASSWORD')   ?: '';
        $m->Port       = (int)(getenv('MAIL_PORT') ?: 587);
        $m->CharSet    = 'UTF-8';

        $encryption = getenv('MAIL_ENCRYPTION') ?: 'tls';
        $m->SMTPSecure = match(strtolower($encryption)) {
            'ssl'   => PHPMailer::ENCRYPTION_SMTPS,
            default => PHPMailer::ENCRYPTION_STARTTLS,
        };

        $m->setFrom(
            getenv('MAIL_FROM_ADDRESS') ?: 'noreply@noteflow.local',
            getenv('MAIL_FROM_NAME')    ?: APP_NAME
        );

        if (APP_DEBUG) {
            $m->SMTPDebug = 0; // set to 2 when debugging SMTP
        }
    }

    // ─── Dev-mode detection ──────────────────────────────────────────────────

    private function isConfigured(): bool
    {
        $host     = getenv('MAIL_HOST')     ?: '';
        $username = getenv('MAIL_USERNAME') ?: '';
        $password = getenv('MAIL_PASSWORD') ?: '';
        return $host !== '' && $username !== '' && $password !== '';
    }

    // ─── Send ────────────────────────────────────────────────────────────────

    public function send(string $to, string $toName, string $subject, string $htmlBody, string $plainBody = ''): bool
    {
        $this->error = null;
        $plain = $plainBody ?: strip_tags($htmlBody);

        // Dev fallback: log email content when SMTP is not configured
        if (!$this->isConfigured()) {
            error_log(sprintf(
                "[NoteFlow Mailer] DEV FALLBACK — To: %s <%s> | Subject: %s\n%s",
                $toName,
                $to,
                $subject,
                $plain
            ));
            return true;
        }

        try {
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();
            $this->mail->addAddress($to, $toName);
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body    = $htmlBody;
            $this->mail->AltBody = $plain;
            $this->mail->send();
            return true;
        } catch (MailException $e) {
            $this->error = $this->mail->ErrorInfo;
            error_log('[NoteFlow Mailer] SMTP error: ' . $this->error);
            return false;
        }
    }

    public function getError(): ?string { return $this->error; }

    // ─── Preset emails ───────────────────────────────────────────────────────

    public function sendVerification(string $to, string $name, string $token): bool
    {
        $link = BASE_URL . '/verify-email?token=' . urlencode($token);
        $html = $this->template('Email Verification', "
            <p>Hi <strong>{$name}</strong>,</p>
            <p>Click the button below to verify your email address.</p>
            <p style='margin:24px 0'>
              <a href='{$link}' style='background:#7C3AED;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none'>
                Verify Email
              </a>
            </p>
            <p>Or copy this link: <a href='{$link}'>{$link}</a></p>
            <p>This link expires in 24 hours.</p>
        ");
        return $this->send($to, $name, APP_NAME . ' – Verify your email', $html);
    }

    public function sendPasswordReset(string $to, string $name, string $otp): bool
    {
        $html = $this->template('Password Reset', "
            <p>Hi <strong>{$name}</strong>,</p>
            <p>Your password reset OTP is:</p>
            <p style='font-size:32px;font-weight:bold;letter-spacing:8px;margin:24px 0;color:#4f46e5'>{$otp}</p>
            <p>This OTP expires in 15 minutes. If you did not request this, ignore this email.</p>
        ");
        return $this->send($to, $name, APP_NAME . ' – Password Reset OTP', $html);
    }

    public function sendShareNotification(string $to, string $name, string $senderName, string $noteTitle, string $permission): bool
    {
        $link = BASE_URL . '/shared';
        $perm = $permission === 'edit' ? 'view & edit' : 'view';
        $html = $this->template('Note Shared With You', "
            <p>Hi <strong>{$name}</strong>,</p>
            <p><strong>{$senderName}</strong> shared a note with you.</p>
            <p>Note: <em>{$noteTitle}</em> (permission: <strong>{$perm}</strong>)</p>
            <p style='margin:24px 0'>
              <a href='{$link}' style='background:#7C3AED;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none'>
                View Shared Notes
              </a>
            </p>
        ");
        return $this->send($to, $name, APP_NAME . ' – ' . $senderName . ' shared a note with you', $html);
    }

    // ─── HTML email template ─────────────────────────────────────────────────

    private function template(string $title, string $body): string
    {
        $appName = APP_NAME;
        return <<<HTML
        <!DOCTYPE html>
        <html><head><meta charset="UTF-8"><title>{$title}</title></head>
        <body style="font-family:'Plus Jakarta Sans',sans-serif;max-width:560px;margin:0 auto;padding:24px;color:#1e293b">
          <div style="border-bottom:3px solid #7C3AED;padding-bottom:12px;margin-bottom:24px">
            <span style="font-size:22px;font-weight:bold;color:#7C3AED">{$appName}</span>
          </div>
          {$body}
          <hr style="margin:32px 0;border:none;border-top:1px solid #e2e8f0">
          <p style="font-size:12px;color:#94a3b8">You received this email from {$appName}.</p>
        </body></html>
        HTML;
    }
}
