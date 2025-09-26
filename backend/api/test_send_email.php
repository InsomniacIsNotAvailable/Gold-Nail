<?php
// Make sure errors never break JSON responses
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Convert PHP warnings/notices to exceptions so we can JSON them
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});
set_exception_handler(function ($e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
});

// CORS + JSON headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-Test-Secret');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// Composer autoload guard
$autoload = __DIR__ . '/../../vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Composer autoload not found', 'expected' => $autoload]);
    exit;
}
require $autoload;

use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;

// Load config
$cfgPath = dirname(__DIR__) . '/config/brevo_email_config.php';
if (!is_file($cfgPath)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Config file missing', 'path' => $cfgPath]);
    exit;
}
$brevoConfig = require $cfgPath;

// Diagnostics mode (GET ?diag=1)
if (($_GET['diag'] ?? '') === '1') {
    echo json_encode([
        'ok' => true,
        'autoload' => true,
        'sdk_classes' => [
            'Configuration' => class_exists(\Brevo\Client\Configuration::class),
            'TransactionalEmailsApi' => class_exists(\Brevo\Client\Api\TransactionalEmailsApi::class),
            'SendSmtpEmail' => class_exists(\Brevo\Client\Model\SendSmtpEmail::class),
        ],
        'config' => [
            'has_api_key' => !empty($brevoConfig['api_key']),
            'sender_email' => $brevoConfig['sender_email'] ?? null,
            'sender_name' => $brevoConfig['sender_name'] ?? null,
        ],
        'api_key_prefix' => substr((string)($brevoConfig['api_key'] ?? ''), 0, 12),
    ]);
    exit;
}

// Enforce POST from here
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Use POST']);
    exit;
}

// Optional simple auth
$secretExpected = getenv('TEST_EMAIL_SECRET');
if ($secretExpected) {
    $provided = $_SERVER['HTTP_X_TEST_SECRET'] ?? '';
    if (!hash_equals($secretExpected, $provided)) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Unauthorized (bad test secret)']);
        exit;
    }
}

if (empty($brevoConfig['api_key'])) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Missing BREVO_API_KEY (brevoenv)']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

$to = filter_var($data['to'] ?? '', FILTER_VALIDATE_EMAIL);
if (!$to) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Field "to" must be a valid email']);
    exit;
}
$subject = trim((string)($data['subject'] ?? 'Test Email'));
$html    = (string)($data['html'] ?? '<p>This is a Brevo test email.</p>');
$text    = (string)($data['text'] ?? strip_tags($html));

try {
    $conf = Configuration::getDefaultConfiguration()->setApiKey('api-key', $brevoConfig['api_key']);
    $api  = new TransactionalEmailsApi(null, $conf);

    $email = new SendSmtpEmail();
    $email->setSender(['email' => $brevoConfig['sender_email'], 'name' => $brevoConfig['sender_name']]);
    $email->setTo([['email' => $to]]);
    $email->setSubject($subject);
    $email->setHtmlContent($html);
    $email->setTextContent($text);
    if (!empty($brevoConfig['reply_to'])) {
        $email->setReplyTo(['email' => $brevoConfig['reply_to']]);
    }
    $email->setTags(['test-endpoint']);

    $res = $api->sendTransacEmail($email);
    echo json_encode(['ok' => true, 'messageId' => $res->getMessageId(), 'to' => $to, 'subject' => $subject]);
} catch (\Brevo\Client\ApiException $e) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => $e->getMessage(), 'code' => $e->getCode(), 'body' => $e->getResponseBody()]);
}