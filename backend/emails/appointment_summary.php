<?php

use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;

if (!function_exists('send_appointment_summary_email')) {
    function send_appointment_summary_email(array $appt, array $opts = []): array
    {
        // Composer autoload
        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new RuntimeException('Composer autoload not found for Brevo SDK');
        }
        require_once $autoload;

        // Load Brevo config
        $cfgPath = dirname(__DIR__) . '/config/brevo_email_config.php';
        if (!is_file($cfgPath)) {
            throw new RuntimeException('Brevo config file missing: ' . $cfgPath);
        }
    $brevoConfig = require $cfgPath;

        $apiKey = $brevoConfig['api_key'] ?? '';
        if ($apiKey === '') {
            throw new RuntimeException('BREVO_API_KEY missing (brevoenv)');
        }

      $brand = trim((string)($opts['brand'] ?? ($brevoConfig['sender_name'] ?? '')));
        if ($brand === '') $brand = 'Appointment';

    $storeAddress = trim((string)($brevoConfig['store_address'] ?? ''));
    $storeLandline = trim((string)($brevoConfig['store_landline'] ?? ''));
    $logoUrl = trim((string)($brevoConfig['logo_url'] ?? ''));
    $logoPath = trim((string)($brevoConfig['logo_path'] ?? ''));
        $to = filter_var((string)($appt['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        if (!$to) {
            throw new InvalidArgumentException('Valid recipient email required');
        }

        $esc = static function (?string $v): string {
            return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };

        $dtAppt = (string)($appt['appointment_datetime'] ?? '');
        $dtApptFmt = $dtAppt !== '' ? date('l, F j, Y g:i A', strtotime($dtAppt)) : '—';

        $created = (string)($appt['created_at'] ?? '');
        $createdFmt = $created !== '' ? date('l, F j, Y g:i A', strtotime($created)) : date('l, F j, Y g:i A');

    // Subject (keep concise); no in-body title per receipt layout
    $subject = sprintf('%s – Appointment Receipt #%s (%s)', $brand, (string)($appt['id'] ?? ''), $dtApptFmt);

        // Rows with all user inputs
        $rows = [
            'Name' => $appt['name'] ?? '',
            'Email' => $appt['email'] ?? '',
            'Phone' => $appt['phone'] ?? '',
            'Appointment Date/Time' => $dtApptFmt,
            'Purpose' => $appt['purpose'] ?? '',
            'Status' => $appt['status'] ?? '',
            'Message' => $appt['message'] ?? '',
            'Created At' => $createdFmt,
            'Appointment ID' => (string)($appt['id'] ?? ''),
        ];

        $htmlRows = '';
        foreach ($rows as $label => $value) {
            $isMsg = ($label === 'Message');
            $htmlRows .= sprintf(
                '<tr>
                    <td style="padding:8px 12px;font-weight:600;width:220px;background:#f7f7f9;border:1px solid #ececf1;color:#333">%s</td>
                    <td style="padding:8px 12px;border:1px solid #ececf1;color:#333">%s</td>
                 </tr>',
                $esc($label),
                $isMsg ? nl2br($esc($value)) : $esc($value)
            );
        }

        // Build store header pieces (logo/brand/address/landline/booked at)
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
        $bookedHtml = $createdFmt ? ('Booked at: ' . $esc($createdFmt)) : '';

        $headerParts = [];
        if ($logoImgHtml !== '') {
            $headerParts[] = $logoImgHtml;
        } elseif ($brand !== '') {
            $headerParts[] = '<div style="font-weight:700;font-size:16px">' . $esc($brand) . '</div>';
        }
        if ($storeAddress !== '')  { $headerParts[] = '<div>' . $esc($storeAddress) . '</div>'; }
        if ($storeLandline !== '') { $headerParts[] = '<div>Landline: ' . $esc($storeLandline) . '</div>'; }
        if ($bookedHtml !== '')    { $headerParts[] = '<div style="opacity:0.9">' . $bookedHtml . '</div>'; }
        $storeHeaderHtml = implode('', $headerParts);

    // Footer line
    $footerParts = [];
    if ($storeAddress !== '')  { $footerParts[] = 'Address: ' . $esc($storeAddress); }
    if ($storeLandline !== '') { $footerParts[] = 'Landline: ' . $esc($storeLandline); }
    $footerLine = implode(' • ', $footerParts);

                // Generate cancel link if config allows
                $publicBase = trim((string)($brevoConfig['public_base_url'] ?? ''));
                $linkSecret = trim((string)($brevoConfig['link_secret'] ?? ''));
                $cancelBtnHtml = '';
                $cancelUrl = '';
                if ($publicBase !== '' && $linkSecret !== '' && !empty($appt['id'])) {
                        $ts = time();
                        $sig = hash_hmac('sha256', (string)$appt['id'] . ':' . $ts, $linkSecret);
                        $cancelUrl = rtrim($publicBase, '/') . '/backend/api/appointment_cancel.php?id=' . rawurlencode((string)$appt['id']) . '&ts=' . $ts . '&sig=' . urlencode($sig);
                        $cancelBtnHtml = '<div style="margin-top:14px;"><a href="' . $esc($cancelUrl) . '" style="display:inline-block;background:#ef4444;color:#ffffff;text-decoration:none;padding:10px 14px;border-radius:6px;font-weight:600">Cancel Appointment</a></div>';
                }

                // Colors
$hex = preg_replace('/[^0-9a-f]/i', '', ltrim(getenv('RECEIPT_HEADER_BG') ?: '#000000', '#'));
if (strlen($hex) === 3) { $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]; }
if (strlen($hex) !== 6) { $hex = '000000'; }
$headerBgCss  = '#' . strtolower($hex);            // CSS background
$headerBgAttr = ($headerBgCss === '#000000') ? '#000001' : $headerBgCss; // td bgcolor (avoid inversion)
$headerFg     = '#ffffff';

// Build final HTML (table-based header; explicit white backgrounds)
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
                <p style="margin:0 0 10px 0;">Here are your appointment details:</p>
                <p style="margin:0 0 18px 0;color:#b45309;background:#fffbeb;border:1px solid #fcd34d;padding:10px 12px;border-radius:6px;">
                  Please wait until your appointment is accepted. You will receive a confirmation email when it is approved.
                </p>
                <table role="presentation" cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%;margin:0 0 10px 0;background:#ffffff;">
                  {$htmlRows}
                </table>
                {$cancelBtnHtml}
                <p style="margin:16px 0 0 0;color:#374151;font-size:14px;">If you need to make changes, reply to this email.</p>
              </td>
            </tr>
            <tr>
              <td style="background:#f9fafb;color:#6b7280;padding:12px 20px;font-size:12px;font-family:Segoe UI, Roboto, Helvetica, Arial, sans-serif;">
                {$footerLine}
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
HTML;

        // Write a copy to inspect locally
        @file_put_contents(__DIR__ . '/last_summary.html', $html);

        // Plain-text fallback
        $lines = [];
        foreach ($rows as $label => $value) { $lines[] = $label . ': ' . (string)$value; }
        $textHeader = [];
        if ($storeAddress !== '')  { $textHeader[] = 'Address: ' . $storeAddress; }
        if ($storeLandline !== '') { $textHeader[] = 'Landline: ' . $storeLandline; }
        if ($createdFmt !== '')    { $textHeader[] = 'Booked at: ' . $createdFmt; }
        $cancelText = $cancelUrl ? ("\nCancel link: " . $cancelUrl . "\n") : '';

        $text = "Appointment Receipt\n" . ($brand ? ("From: " . $brand . "\n") : "")
              . (empty($textHeader) ? "" : (implode("\n", $textHeader) . "\n"))
              . "\n" . implode("\n", $lines)
              . "\n\nNOTE: Please wait until your appointment is accepted.\n"
              . "If you need to make changes, reply to this email.\n" . $cancelText;

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
        $email->setTags(['appointment-receipt']);

        $res = $api->sendTransacEmail($email);

        return ['ok' => true, 'messageId' => $res->getMessageId(), 'subject' => $subject];
    }
}