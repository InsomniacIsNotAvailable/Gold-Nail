<?php

use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;

if (!function_exists('send_appointment_accepted_email')) {
    function send_appointment_accepted_email(array $appt, array $opts = []): array
    {
        // Try multiple vendor locations to avoid path conflicts between repos
        $autoloadCandidates = [
            __DIR__ . '/../../vendor/autoload.php',   // backend/vendor
            __DIR__ . '/../../../vendor/autoload.php' // project-root/vendor
        ];
        $autoload = null;
        foreach ($autoloadCandidates as $cand) {
            if (is_file($cand)) { $autoload = $cand; break; }
        }
        if (!$autoload) {
            throw new RuntimeException('Composer autoload not found for Brevo SDK. Tried: ' . implode(', ', $autoloadCandidates));
        }
        require_once $autoload;

        // Try config in backend/config first, then root/config
        $cfgCandidates = [
            dirname(__DIR__)      . '/config/brevo_email_config.php', // backend/config
            dirname(__DIR__, 2)   . '/config/brevo_email_config.php', // project-root/config
        ];
        $cfgPath = null;
        foreach ($cfgCandidates as $cand) {
            if (is_file($cand)) { $cfgPath = $cand; break; }
        }
        if (!$cfgPath) {  
            throw new RuntimeException('Brevo config file missing. Tried: ' . implode(', ', $cfgCandidates));
        }
        $brevoConfig = require $cfgPath;

        $apiKey = $brevoConfig['api_key'] ?? '';
        if ($apiKey === '') {
            throw new RuntimeException('BREVO_API_KEY missing (brevoenv)');
        }

        // Recipient and subject
        $to = (string)($appt['email'] ?? '');
        if ($to === '') {
            throw new RuntimeException('Recipient email missing in appointment');
        }

        // Ensure helpers and config-derived values exist
        $esc = $esc ?? static function (?string $v): string {
            return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };
        $brand         = isset($brand) ? $brand : trim((string)($brevoConfig['sender_name'] ?? 'Appointment'));
        $storeAddress  = isset($storeAddress) ? $storeAddress : trim((string)($brevoConfig['store_address'] ?? ''));
        $storeLandline = isset($storeLandline) ? $storeLandline : trim((string)($brevoConfig['store_landline'] ?? ''));
        $logoUrl       = isset($logoUrl) ? $logoUrl : trim((string)($brevoConfig['logo_url'] ?? ''));
        $logoPath      = isset($logoPath) ? $logoPath : trim((string)($brevoConfig['logo_path'] ?? ''));

        // Map link (optional)
        $mapsLink = trim((string)(
            $brevoConfig['google_map_url']
            ?? $brevoConfig['maps_link']
            ?? getenv('BREVO_GOOGLE_MAP_URL')
            ?? ''
        ));

        // Appt details + subject
        $dtAppt = (string)($appt['appointment_datetime'] ?? '');
        $dtApptFmt = $dtAppt !== '' ? date('l, F j, Y g:i A', strtotime($dtAppt)) : '—';
        $subject = sprintf('%s – Appointment Accepted #%s (%s)', $brand, (string)($appt['id'] ?? ''), $dtApptFmt);

        // Colors (env-driven; bgcolor uses near-black to reduce inversion)
        $hex = preg_replace('/[^0-9a-f]/i', '', ltrim(getenv('RECEIPT_HEADER_BG') ?: '#000000', '#'));
        if (strlen($hex) === 3) { $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]; }
        if (strlen($hex) !== 6) { $hex = '000000'; }
        $headerBgCss  = '#' . strtolower($hex);
        $headerBgAttr = ($headerBgCss === '#000000') ? '#000001' : $headerBgCss;
        $headerFg     = '#ffffff';

        // Build store header contents (logo or brand, then address/landline)
        $logoImgHtml = '';
        if ($logoUrl !== '') {
            $logoImgHtml = '<img src="' . $esc($logoUrl) . '" alt="' . $esc($brand) . '" style="height:42px;max-width:200px;object-fit:contain;display:block;margin:0 auto 8px;" />';
        } elseif ($logoPath !== '' && is_readable($logoPath)) {
            $bin = @file_get_contents($logoPath);
            if ($bin !== false) {
                $b64  = base64_encode($bin);
                $ext  = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
                $mime = ($ext === 'png') ? 'image/png' : (($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png');
                $logoImgHtml = '<img src="data:' . $mime . ';base64,' . $b64 . '" alt="' . $esc($brand) . '" style="height:42px;max-width:200px;object-fit:contain;display:block;margin:0 auto 8px;" />';
            }
        }

        $headerParts = [];
        if ($logoImgHtml !== '') {
            $headerParts[] = $logoImgHtml;
        } elseif ($brand !== '') {
            $headerParts[] = '<div style="font-weight:700;font-size:16px">' . $esc($brand) . '</div>';
        }
        if ($storeAddress !== '')  { $headerParts[] = '<div>' . $esc($storeAddress) . '</div>'; }
        if ($storeLandline !== '') { $headerParts[] = '<div>Landline: ' . $esc($storeLandline) . '</div>'; }
        $storeHeaderHtml = implode('', $headerParts);

        $nameHtml   = $esc($appt['name'] ?? '');
        $idHtml     = $esc((string)($appt['id'] ?? ''));
        $footerHtml = $esc($storeAddress) . ($storeLandline ? ' • Landline: ' . $esc($storeLandline) : '');

        // Build the HTML with a centered, black header
        $html = <<<HTML
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
  </head>
  <body style="margin:0;padding:0;background:#f5f6fa;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f6fa;">
      <tr>
        <td align="center" style="padding:24px 12px;">
          <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="width:640px;max-width:100%;background:#ffffff;border:1px solid #e6e8ef;border-radius:8px;overflow:hidden;">
            <tr>
              <td bgcolor="{$headerBgAttr}" style="background-color: {$headerBgCss}; color: {$headerFg}; padding:16px 20px; text-align:center; font-family:Segoe UI, Roboto, Helvetica, Arial, sans-serif;">
                <div style="font-size:14px;line-height:1.3;display:inline-block;text-align:center;">
                  {$storeHeaderHtml}
                </div>
              </td>
            </tr>
            <tr>
              <td style="background:#ffffff;padding:18px 20px;color:#111827;font-family:Segoe UI, Roboto, Helvetica, Arial, sans-serif;">
                <p style="margin:0 0 12px 0;">Hi {$nameHtml},</p>
                <p style="margin:0 0 10px 0;">Good news—your appointment has been accepted.</p>
                <p style="margin:0 0 10px 0;">Appointment ID: <strong>{$idHtml}</strong></p>
                <p style="margin:0 0 10px 0;">Date/Time: <strong>{$esc($dtApptFmt)}</strong></p>
                <p style="margin:16px 0 0 0;color:#374151;font-size:14px;">If you need to make changes, reply to this email.</p>
              </td>
            </tr>
            <tr>
              <td style="background:#f9fafb;color:#6b7280;padding:12px 20px;font-size:12px;font-family:Segoe UI, Roboto, Helvetica, Arial, sans-serif;">
                {$footerHtml}
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
HTML;

        // Optional: write a copy for quick visual check
        @file_put_contents(__DIR__ . '/last_accept.html', $html);

        $text = "Appointment Accepted\n"
              . ($brand ? ("From: " . $brand . "\n") : "")
              . "Address: " . $storeAddress . "\n"
              . "Landline: " . $storeLandline . "\n"
              . "Time: " . $dtApptFmt . "\n"
              . ($mapsLink ? ("Map: " . $mapsLink . "\n") : "")
              . "\nIf you need to make changes, reply to this email.\n";

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
        $email->setTags(['appointment-accepted']);

        $res = $api->sendTransacEmail($email);
        return ['ok' => true, 'messageId' => $res->getMessageId(), 'subject' => $subject];
    }
}
