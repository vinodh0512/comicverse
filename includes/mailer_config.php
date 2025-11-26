<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function cv_mail_config() {
    $host = getenv('CV_MAIL_HOST') ?: 'smtp.gmail.com';
    $user = getenv('CV_MAIL_USER') ?: 'acethumbstudio@gmail.com';
    $pass = getenv('CV_MAIL_PASS') ?: 'mhpn inmz igsy zcpf';
    $port = getenv('CV_MAIL_PORT') ?: 587;
    $fromName = getenv('CV_MAIL_FROM') ?: 'ComicVerse';
    $jsonPath = __DIR__ . '/../user_data/mail.json';
    if ((!$user || !$pass) && file_exists($jsonPath)) {
        $cfg = json_decode(file_get_contents($jsonPath), true) ?: [];
        $host = $cfg['host'] ?? $host;
        $user = $cfg['user'] ?? $user;
        $pass = $cfg['pass'] ?? $pass;
        $port = $cfg['port'] ?? $port;
        $fromName = $cfg['from_name'] ?? $fromName;
    }
    $pass = preg_replace('/\s+/', '', $pass);
    return [ 'host'=>$host, 'user'=>$user, 'pass'=>$pass, 'port'=>$port, 'from_name'=>$fromName ];
}

function send_mail($toEmail, $subject, $htmlBody, $plainBody = '', $fromName = null, $replyToEmail = null, $replyToName = null) {
    $cfg = cv_mail_config();
    if (!$cfg['user'] || !$cfg['pass']) return ['ok'=>false,'error'=>'Mail credentials not configured'];
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $cfg['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $cfg['user'];
        $mail->Password = $cfg['pass'];
        $mail->Port = (int)$cfg['port'];
        $mail->SMTPSecure = ($mail->Port === 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->SMTPAutoTLS = true;
        $mail->Timeout = (int)(getenv('CV_MAIL_TIMEOUT') ?: 10);
        $mail->SMTPKeepAlive = true;
        $mail->AuthType = getenv('CV_MAIL_AUTHTYPE') ?: '';
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false
            ]
        ];
        $mail->CharSet = 'UTF-8';
        if ((getenv('CV_MAIL_DEBUG') ?: '') === '1') {
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function($str, $level) {
                @file_put_contents(__DIR__ . '/../user_data/mail.log', date('c') . " [debug:$level] " . $str . "\n", FILE_APPEND);
            };
        }
        $mail->setFrom($cfg['user'], $fromName ?: $cfg['from_name']);
        if ($replyToEmail) { $mail->addReplyTo($replyToEmail, $replyToName ?: ($fromName ?: $cfg['from_name'])); }
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $plainBody ?: strip_tags($htmlBody);
        $mail->send();
        return ['ok'=>true];
    } catch (Exception $e) {
        @file_put_contents(__DIR__ . '/../user_data/mail.log', date('c') . " [error] " . ($mail->ErrorInfo ?: $e->getMessage()) . "\n", FILE_APPEND);
        return ['ok'=>false,'error'=>$mail->ErrorInfo ?: $e->getMessage()];
    }
}
?>
