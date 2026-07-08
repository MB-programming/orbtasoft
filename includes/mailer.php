<?php
/**
 * Minimal SMTP client over raw sockets — no Composer/PHPMailer dependency,
 * to stay consistent with the project's "vanilla stack" requirement.
 * Supports plain, STARTTLS (587) and implicit TLS/SSL (465) connections
 * with AUTH LOGIN.
 */

function smtp_read_response($socket): array
{
    $data = '';
    while (($line = fgets($socket, 515)) !== false) {
        $data .= $line;
        // A multi-line SMTP response has '-' as the 4th char except on the last line.
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }
    $code = (int) substr($data, 0, 3);
    return [$code, $data];
}

function smtp_send_command($socket, string $command, array $expectedCodes): array
{
    fwrite($socket, $command . "\r\n");
    [$code, $response] = smtp_read_response($socket);
    if (!in_array($code, $expectedCodes, true)) {
        throw new RuntimeException("SMTP command failed: {$command} — response: {$response}");
    }
    return [$code, $response];
}

/**
 * @param array{host:string,port:int,encryption:string,username:string,password:string,from_email:string,from_name:string} $config
 */
function send_smtp_mail(array $config, string $toEmail, string $subject, string $body): bool
{
    if (empty($config['host'])) {
        error_log('send_smtp_mail: no SMTP host configured, skipping send.');
        return false;
    }

    $host = $config['host'];
    $port = (int) ($config['port'] ?: 587);
    $encryption = $config['encryption'] ?? 'tls';
    $transport = $encryption === 'ssl' ? 'ssl://' : '';

    try {
        $socket = @stream_socket_client(
            $transport . $host . ':' . $port,
            $errno,
            $errstr,
            10,
            STREAM_CLIENT_CONNECT
        );
        if (!$socket) {
            throw new RuntimeException("Could not connect to SMTP server: {$errstr} ({$errno})");
        }

        smtp_read_response($socket); // 220 greeting
        $localHost = $_SERVER['SERVER_NAME'] ?? 'localhost';
        smtp_send_command($socket, "EHLO {$localHost}", [250]);

        if ($encryption === 'tls') {
            smtp_send_command($socket, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('STARTTLS negotiation failed.');
            }
            smtp_send_command($socket, "EHLO {$localHost}", [250]);
        }

        if (!empty($config['username'])) {
            smtp_send_command($socket, 'AUTH LOGIN', [334]);
            smtp_send_command($socket, base64_encode($config['username']), [334]);
            smtp_send_command($socket, base64_encode($config['password'] ?? ''), [235]);
        }

        $fromEmail = $config['from_email'] ?: $config['username'];
        smtp_send_command($socket, "MAIL FROM:<{$fromEmail}>", [250]);
        smtp_send_command($socket, "RCPT TO:<{$toEmail}>", [250, 251]);
        smtp_send_command($socket, 'DATA', [354]);

        $fromName = $config['from_name'] ?: 'Orbtasoft';
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers = implode("\r\n", [
            "From: {$fromName} <{$fromEmail}>",
            "To: <{$toEmail}>",
            "Subject: {$encodedSubject}",
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ]);
        $encodedBody = chunk_split(base64_encode($body));
        // Escape lines that start with a lone dot, per SMTP data-transparency rules.
        $payload = $headers . "\r\n\r\n" . $encodedBody;
        $payload = preg_replace('/\r\n\./', "\r\n..", $payload);

        fwrite($socket, $payload . "\r\n.\r\n");
        smtp_read_response($socket); // 250 Ok

        smtp_send_command($socket, 'QUIT', [221]);
        fclose($socket);
        return true;
    } catch (Throwable $e) {
        error_log('send_smtp_mail failed: ' . $e->getMessage());
        if (isset($socket) && is_resource($socket)) {
            fclose($socket);
        }
        return false;
    }
}

function get_settings(): array
{
    require_once __DIR__ . '/../config/database.php';
    $pdo = get_db();
    if (!$pdo) {
        return [];
    }
    $rows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    return $rows;
}

function render_email_template(string $template, array $placeholders): string
{
    $replacements = [];
    foreach ($placeholders as $key => $value) {
        $replacements['{{' . $key . '}}'] = $value;
    }
    return strtr($template, $replacements);
}

/** Sends a transactional email to an arbitrary recipient (e.g. password reset) using the site's configured SMTP settings. */
function send_user_mail(string $toEmail, string $subject, string $body): bool
{
    $settings = get_settings();
    return send_smtp_mail([
        'host' => $settings['smtp_host'] ?? '',
        'port' => (int) ($settings['smtp_port'] ?? 587),
        'encryption' => $settings['smtp_encryption'] ?? 'tls',
        'username' => $settings['smtp_username'] ?? '',
        'password' => $settings['smtp_password'] ?? '',
        'from_email' => $settings['smtp_from_email'] ?? '',
        'from_name' => $settings['smtp_from_name'] ?? '',
    ], $toEmail, $subject, $body);
}

function notify_admin(string $type, array $placeholders): bool
{
    $settings = get_settings();
    $toggleKey = $type === 'contact' ? 'notify_on_contact' : 'notify_on_newsletter';

    if (empty($settings[$toggleKey]) || $settings[$toggleKey] !== '1') {
        return false;
    }
    if (empty($settings['notify_email'])) {
        return false;
    }

    $subjectKey = $type === 'contact' ? 'contact_email_subject' : 'newsletter_email_subject';
    $bodyKey = $type === 'contact' ? 'contact_email_body' : 'newsletter_email_body';

    $subject = render_email_template($settings[$subjectKey] ?? 'New submission', $placeholders);
    $body = render_email_template($settings[$bodyKey] ?? '', $placeholders);

    return send_smtp_mail([
        'host' => $settings['smtp_host'] ?? '',
        'port' => (int) ($settings['smtp_port'] ?? 587),
        'encryption' => $settings['smtp_encryption'] ?? 'tls',
        'username' => $settings['smtp_username'] ?? '',
        'password' => $settings['smtp_password'] ?? '',
        'from_email' => $settings['smtp_from_email'] ?? '',
        'from_name' => $settings['smtp_from_name'] ?? '',
    ], $settings['notify_email'], $subject, $body);
}
