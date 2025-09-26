<?php

use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;

if (!function_exists('send_appointment_cancelled_email')) {
    function send_appointment_cancelled_email(array $appt, array $opts = []): array
    {
        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new RuntimeException('Composer autoload not found for Brevo SDK');
        }
        require_once $autoload;

        $cfgPath = dirname(__DIR__) . '/config/brevo_email_config.php';
        if (!is_file($cfgPath)) {
            throw new RuntimeException('Brevo config file missing: ' . $cfgPath);
        }
        $brevoConfig = require $cfgPath;

        $apiKey = $brevoConfig['api_key'] ?? '';
        if ($apiKey === '') {
            throw new RuntimeException('BREVO_API_KEY missing (brevoenv)');
        }

        $brand         = trim((string)($opts['brand'] ?? ($brevoConfig['sender_name'] ?? '')));
        if ($brand === '') $brand = 'Appointment';
        $storeAddress  = trim((string)($brevoConfig['store_address'] ?? ''));
        $storeLandline = trim((string)($brevoConfig['store_landline'] ?? ''));

        $to = filter_var((string)($appt['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        if (!$to) {
            throw new InvalidArgumentException('Valid recipient email required');
        }

        $esc = static function (?string $v): string {
            return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };

        $dtAppt = (string)($appt['appointment_datetime'] ?? '');
        $dtApptFmt = $dtAppt !== '' ? date('l, F j, Y g:i A', strtotime($dtAppt)) : '—';

        $subject = sprintf('%s – Appointment Cancelled #%s', $brand, (string)($appt['id'] ?? ''));

        // Colors
        $headerBg = getenv('RECEIPT_HEADER_BG') ?: '#000000';
        $headerFg = '#ffffff';

        // Pre-escape content used in HTML
        $brandHtml        = $esc($brand);
        $storeAddressHtml = $esc($storeAddress);
        $storeLandHtml    = $esc($storeLandline);
        $nameHtml         = $esc($appt['name'] ?? '');
        $idHtml           = $esc((string)($appt['id'] ?? ''));
        $dtApptFmtHtml    = $esc($dtApptFmt);

        $html = <<<HTML
<!doctype html>
<html>
  <body style="margin:0;padding:0;background:#f5f6fa;">
    <div style="max-width:640px;margin:24px auto;background:#ffffff;border:1px solid #e6e8ef;border-radius:8px;overflow:hidden;font-family:Segoe UI, Roboto, Helvetica, Arial, sans-serif;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td style="background-color: {$headerBg}; color: {$headerFg}; padding:16px 20px; text-align:center">
            <div style="font-size:14px;line-height:1.3;display:inline-block;text-align:center;">
              <div style="font-weight:700;font-size:16px">{$brandHtml}</div>
              <div>{$storeAddressHtml}</div>
              <div>Landline: {$storeLandHtml}</div>
            </div>
          </td>
        </tr>
      </table>
      <div style="padding:18px 20px;color:#111827;">
        <p style="margin:0 0 12px 0;">Hi {$nameHtml},</p>
        <p style="margin:0 0 10px 0;">Your appointment has been cancelled.</p>
        <p style="margin:0 0 10px 0;">Appointment ID: <strong>{$idHtml}</strong></p>
        <p style="margin:0 0 10px 0;">Original time: <strong>{$dtApptFmtHtml}</strong></p>
        <p style="margin:16px 0 0 0;color:#374151;font-size:14px;">If this was a mistake, reply to this email.</p>
      </div>
      <div style="background:#f9fafb;color:#6b7280;padding:12px 20px;font-size:12px;">
        {$storeAddressHtml} • Landline: {$storeLandHtml}
      </div>
    </div>
  </body>
</html>
HTML;

        $text = "Appointment Cancelled\n"
              . ($brand ? ("From: " . $brand . "\n") : "")
              . "Address: " . $storeAddress . "\n"
              . "Landline: " . $storeLandline . "\n"
              . "Appointment ID: " . (string)($appt['id'] ?? '') . "\n"
              . "Original time: " . $dtApptFmt . "\n";

        $conf = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
        $api = new TransactionalEmailsApi(null, $conf);

        $email = new SendSmtpEmail();
        $email->setSender([
            'email' => $brevoConfig['sender_email'] ?? 'no-reply@example.com',
            'name'  => $brevoConfig['sender_name'] ?? 'Appointment',
        ]);
        $email->setTo([['email' => $to, 'name' => (string)($appt['name'] ?? '')]]);
        $email->setSubject($subject);
        $email->setHtmlContent($html);
        $email->setTextContent($text);
        if (!empty($brevoConfig['reply_to'])) {
            $email->setReplyTo(['email' => $brevoConfig['reply_to']]);
        }
        $email->setTags(['appointment-cancelled']);

        $res = $api->sendTransacEmail($email);
        return ['ok' => true, 'messageId' => $res->getMessageId(), 'subject' => $subject];
    }
}
