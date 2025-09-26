<?php
declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Brevo\Client\Configuration;
use Brevo\Client\Api\EmailCampaignsApi;
use Brevo\Client\Model\CreateEmailCampaign;
use Brevo\Client\Model\CreateEmailCampaignSender;
use Brevo\Client\Model\CreateEmailCampaignRecipients;

// Load Brevo config (throws if key missing)
$config = require dirname(__DIR__) . '/config/brevo_email_config.php';

$apiKey = $config['api_key'];
if ($apiKey === '') {
    fwrite(STDERR, "BREVO_API_KEY missing in brevoenv\n");
    exit(1);
}

$brevoConfig = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
$api = new EmailCampaignsApi(null, $brevoConfig);

// Build campaign
$campaign = new CreateEmailCampaign();
$campaign->setName('Campaign sent via API');
$campaign->setSubject('My subject line');
$campaign->setType('classic'); // classic | trigger (trigger = transactional automation)
$campaign->setSender(new CreateEmailCampaignSender([
    'name'  => $config['sender_name'],
    'email' => $config['sender_email'],
]));

// Inline HTML content (or use setHtmlUrl('https://...'))
$campaign->setHtmlContent('<html><body><p>Congratulations! You successfully sent this example campaign via the Brevo API.</p></body></html>');

// Target recipient lists (replace with your actual list IDs)
$campaign->setRecipients(new CreateEmailCampaignRecipients([
    'listIds' => [2, 7],
]));

// Schedule one hour from now (must be future, ISO 8601)
$campaign->setScheduledAt(gmdate('Y-m-d\TH:i:sP', time() + 3600));

try {
    $result = $api->createEmailCampaign($campaign);
    echo json_encode([
        'id'        => $result->getId(),
        'createdAt' => $result->getCreatedAt(),
        'status'    => $result->getStatus(),
    ], JSON_PRETTY_PRINT) . PHP_EOL;
} catch (\Brevo\Client\ApiException $e) {
    fwrite(STDERR, "API Exception: {$e->getCode()} {$e->getMessage()}\n");
    if ($e->getResponseBody()) {
        fwrite(STDERR, $e->getResponseBody() . "\n");
    }
    exit(1);
} catch (\Throwable $t) {
    fwrite(STDERR, "Error: {$t->getMessage()}\n");
    exit(1);
}