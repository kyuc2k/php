<?php

class Mailer
{
    /**
     * Send an email using PHPMailer.
     *
     * @param string $to      Recipient email
     * @param string $name    Recipient name (optional)
     * @param string $subject Email subject
     * @param string $body    Email body (HTML or plain text)
     * @param bool   $isHtml  Whether body is HTML
     * @return bool
     */
    public static function send(string $to, string $name, string $subject, string $body, bool $isHtml = false): bool
    {
        require_once BASE_PATH . '/PHPMailer/src/PHPMailer.php';
        require_once BASE_PATH . '/PHPMailer/src/SMTP.php';
        require_once BASE_PATH . '/PHPMailer/src/Exception.php';

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer();
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('GMAIL_USERNAME');
            $mail->Password   = getenv('GMAIL_APP_PASSWORD');
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(getenv('GMAIL_USERNAME'), 'PDF Manager');
            $mail->addAddress($to, $name);

            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            if ($isHtml) {
                $mail->AltBody = strip_tags($body);
            }

            return $mail->send();
        } catch (\Exception $e) {
            return false;
        }
    }
}
