<?php
/** Public endpoint. Configuration, SMTP credentials, library and state stay outside public_html. */
declare(strict_types=1);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function reply(int $status, string $message, bool $received = false): void {
    http_response_code($status);
    echo json_encode(['received' => $received, 'message' => $message], JSON_UNESCAPED_SLASHES);
    exit;
}
function outsidePublic(string $path, string $public): bool {
    return $path !== $public && strpos($path, $public . DIRECTORY_SEPARATOR) !== 0;
}
function limitRequests(string $directory, string $public): bool {
    if (!is_dir($directory) && !mkdir($directory, 0700, true)) {
        throw new RuntimeException('State directory unavailable');
    }
    $directory = realpath($directory);
    if (!$directory || !outsidePublic($directory, $public)) {
        throw new RuntimeException('State must be private');
    }
    // One lock covers the private HMAC key and bounded, expiring rate counters.
    $file = fopen($directory . '/limits.json', 'c+b');
    if (!$file || !flock($file, LOCK_EX)) {
        throw new RuntimeException('State lock unavailable');
    }
    try {
        $raw = stream_get_contents($file);
        $state = $raw === '' ? ['key' => bin2hex(random_bytes(32)), 'ips' => [], 'total' => []]
                            : json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
        if (!is_array($state) || !isset($state['key'], $state['ips'], $state['total']) || strlen($state['key']) !== 64) {
            throw new RuntimeException('Invalid state');
        }
        $now = time();
        foreach ($state['ips'] as $key => $entry) {
            if (($entry['start'] ?? 0) <= $now - 900) unset($state['ips'][$key]);
        }
        if (($state['total']['start'] ?? 0) <= $now - 900) $state['total'] = ['start' => $now, 'count' => 0];
        $address = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!filter_var($address, FILTER_VALIDATE_IP)) throw new RuntimeException('No trusted client address');
        // Never trust user-supplied X-Forwarded-For headers.
        $key = hash_hmac('sha256', $address, $state['key']);
        $entry = $state['ips'][$key] ?? ['start' => $now, 'count' => 0];
        if ($entry['count'] >= 5 || $state['total']['count'] >= 50) return false;
        $entry['count']++;
        $state['ips'][$key] = $entry;
        $state['total']['count']++;
        $json = json_encode($state, JSON_THROW_ON_ERROR);
        rewind($file);
        if (!ftruncate($file, 0) || fwrite($file, $json) !== strlen($json) || !fflush($file)) {
            throw new RuntimeException('State write unavailable');
        }
        return true;
    } finally {
        flock($file, LOCK_UN);
        fclose($file);
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    reply(405, 'Please submit using the inquiry form.');
}
if (!(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && strtolower($_SERVER['HTTPS']) !== 'off')
    && (int)($_SERVER['SERVER_PORT'] ?? 0) !== 443) {
    reply(403, 'A secure HTTPS connection is required.');
}
if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') {
    reply(415, 'Please submit using the inquiry form.');
}
try {
    umask(0077);
    $public = realpath($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__));
    $configPath = getenv('KOVA_INQUIRY_CONFIG') ?: dirname(__DIR__, 2) . '/kova-private/inquiry-config.php';
    $configFile = realpath($configPath);
    if (!$public || !$configFile || !outsidePublic($configFile, $public)) {
        reply(503, 'Online inquiries are not configured. Please email info@kovasourcing.com.');
    }
    $config = require $configFile;
    if (!is_array($config) || ($config['enabled'] ?? false) !== true) {
        reply(503, 'Online inquiries are not enabled. Please email info@kovasourcing.com.');
    }
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (!isset($config['allowed_origin']) || $origin !== $config['allowed_origin']
        || parse_url($origin, PHP_URL_SCHEME) !== 'https') {
        reply(403, 'Please use the inquiry form on the KOVA website.');
    }
    $smtp = $config['smtp'] ?? [];
    if (!is_array($smtp) || !is_string($smtp['host'] ?? null)
        || !preg_match('/\A[a-z0-9](?:[a-z0-9.-]{0,251}[a-z0-9])?\z/i', $smtp['host'])
        || !is_int($smtp['port'] ?? null) || $smtp['port'] < 1 || $smtp['port'] > 65535
        || !in_array($smtp['security'] ?? '', ['smtps', 'starttls'], true)
        || ($smtp['username'] ?? '') !== 'info@kovasourcing.com'
        || !is_string($smtp['password'] ?? null) || $smtp['password'] === '') {
        reply(503, 'Online inquiries are not configured. Please email info@kovasourcing.com.');
    }
    $stream = fopen('php://input', 'rb');
    $raw = stream_get_contents($stream, 32769);
    fclose($stream);
    if (strlen($raw) > 32768) reply(413, 'Your inquiry is too long. Please shorten the project details.');
    try {
        $input = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
    } catch (JsonException $error) {
        reply(400, 'The inquiry could not be read. Please try again.');
    }
    if (!is_array($input) || array_is_list($input)) reply(400, 'Please review the inquiry form.');
    $limits = ['name' => 120, 'company' => 160, 'email' => 254, 'country' => 100,
               'category' => 40, 'support' => 60, 'quantity' => 120, 'details' => 6000,
               'website' => 200, 'source_category' => 160];
    if (array_diff(array_keys($input), array_keys($limits))) reply(422, 'Please review the inquiry form.');
    $fields = [];
    foreach ($limits as $key => $max) {
        $value = $input[$key] ?? '';
        if (!is_string($value) || mb_strlen($value, 'UTF-8') > $max) {
            reply(422, 'Please check the length and format of your inquiry fields.');
        }
        // Header and single-line fields cannot contain control characters, even at their edges.
        $pattern = $key === 'details' ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u' : '/[\x00-\x1F\x7F]/u';
        if (preg_match($pattern, $value)) reply(422, 'Please remove invalid characters from your inquiry.');
        $fields[$key] = trim($value);
    }
    foreach (['name', 'email', 'country', 'category', 'details'] as $key) {
        if ($fields[$key] === '') reply(422, 'Please complete the required inquiry fields.');
    }
    if (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) reply(422, 'Please enter a valid email address.');
    if (!in_array($fields['category'], ['Cat Products', 'Dog Products', 'Both', 'Other / Not Sure'], true)
        || !in_array($fields['support'], ['', 'Supplier Research', 'OEM/ODM Customization', 'Sample Coordination',
                     'Production Follow-up', 'Order / Shipment Coordination', 'Not Sure Yet'], true)) {
        reply(422, 'Please choose a listed product category and support type.');
    }
    if ($fields['website'] !== '') reply(400, 'Unable to send this inquiry. Please email info@kovasourcing.com.');
    $private = dirname($configFile);
    if (!limitRequests($private . '/state', $public)) {
        header('Retry-After: 900');
        reply(429, 'Too many attempts. Please try later or email info@kovasourcing.com.');
    }
    require_once $private . '/phpmailer/src/Exception.php';
    require_once $private . '/phpmailer/src/PHPMailer.php';
    require_once $private . '/phpmailer/src/SMTP.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->SMTPAuth = true;
    $mail->Host = $smtp['host'];
    $mail->Port = $smtp['port'];
    $mail->Username = $smtp['username'];
    $mail->Password = $smtp['password'];
    $mail->SMTPSecure = $smtp['security'] === 'smtps'
        ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    // Preserve peer certificate and hostname verification; never enable SMTP debug publicly.
    $mail->SMTPOptions = ['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false]];
    $mail->Timeout = 10;
    $mail->Timelimit = 15;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom('info@kovasourcing.com', 'KOVA Sourcing');
    $mail->Sender = 'info@kovasourcing.com';
    $mail->addAddress('info@kovasourcing.com');
    $mail->addReplyTo($fields['email'], $fields['name']);
    $mail->Subject = 'KOVA sourcing inquiry — ' . $fields['category'];
    $labels = ['name' => 'Name', 'company' => 'Company / Brand', 'email' => 'Email', 'country' => 'Country / Region',
               'category' => 'Product Category', 'support' => 'Type of Support', 'quantity' => 'Estimated Order Quantity',
               'source_category' => 'Selected sourcing category', 'details' => 'Project Details'];
    $lines = ['New KOVA sourcing inquiry'];
    foreach ($labels as $key => $label) $lines[] = $label . ': ' . ($fields[$key] ?: 'Not specified');
    $mail->Body = implode("\n\n", $lines);
    // PHPMailer returns success only after the authenticated SMTP server accepts the message.
    if (!$mail->send()) throw new RuntimeException('SMTP rejected message');
    reply(200, 'Your inquiry was accepted for sending to KOVA.', true);
} catch (Throwable $error) {
    // Do not log bodies, email addresses, SMTP responses, secrets or filesystem paths.
    error_log('KOVA inquiry could not be confirmed; review private configuration or SMTP service status.');
    reply(503, 'We could not confirm your inquiry was sent. Please email info@kovasourcing.com.');
}
