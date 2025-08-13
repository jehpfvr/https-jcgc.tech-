<?php
require_once __DIR__ . '/config_email.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviar_email(string $destinatario, string $assunto, string $corpo): bool {
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            require_once __DIR__ . '/vendor/autoload.php';
        } else {
            echo "Biblioteca PHPMailer não encontrada.\n";
            return false;
        }
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->Port = SMTP_PORT;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail->setFrom(SMTP_USER);
        $mail->addAddress($destinatario);
        $mail->Subject = $assunto;
        $mail->Body = $corpo;

        return $mail->send();
    } catch (Exception $e) {
        echo "Erro ao enviar email: " . $e->getMessage() . "\n";
        return false;
    }
}
?>
