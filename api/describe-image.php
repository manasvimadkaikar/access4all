<?php
// api/describe-image.php
// Takes { image: "data:image/jpeg;base64,...." } and returns { description: "..." }
//
// Uses the Anthropic API (Claude can read images directly) to describe
// what's in front of a blind user. Needs an API key — see api/config.sample.php.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$configFile = __DIR__ . '/config.php';

$input = json_decode(file_get_contents('php://input'), true);
$image = isset($input['image']) ? $input['image'] : null;

if (!$image) {
    echo json_encode(['description' => 'No image received.']);
    exit;
}

// If no config.php exists yet, tell the user how to set it up instead of failing silently.
if (!file_exists($configFile)) {
    echo json_encode([
        'description' => 'No API key set up yet. Copy api/config.sample.php to api/config.php and add your Anthropic API key to enable real image descriptions.'
    ]);
    exit;
}

require_once $configFile;

if (!defined('ANTHROPIC_API_KEY') || ANTHROPIC_API_KEY === 'your-api-key-here' || ANTHROPIC_API_KEY === '') {
    echo json_encode([
        'description' => 'API key is missing or still set to the placeholder. Open api/config.php and paste in your real Anthropic API key.'
    ]);
    exit;
}

// Split "data:image/jpeg;base64,XXXXX" into media type + raw base64 data.
if (!preg_match('#^data:(image/\w+);base64,(.+)$#', $image, $matches)) {
    echo json_encode(['description' => 'Could not read the captured image format.']);
    exit;
}
$mediaType = $matches[1];
$base64Data = $matches[2];

$payload = json_encode([
    'model' => 'claude-sonnet-5',
    'max_tokens' => 300,
    'messages' => [[
        'role' => 'user',
        'content' => [
            [
                'type' => 'image',
                'source' => [
                    'type' => 'base64',
                    'media_type' => $mediaType,
                    'data' => $base64Data
                ]
            ],
            [
                'type' => 'text',
                'text' => 'You are helping a blind person understand what is directly in front of them. In 1-2 short spoken sentences, describe the scene: point out people, obstacles, doors, stairs, or hazards first if present, then briefly mention anything else notable. Speak plainly, as if narrating out loud, with no headers or bullet points.'
            ]
        ]
    ]]
]);

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-api-key: ' . ANTHROPIC_API_KEY,
    'anthropic-version: 2023-06-01'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$rawResponse = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($rawResponse === false) {
    echo json_encode(['description' => 'Could not reach the vision API: ' . $curlError]);
    exit;
}

$response = json_decode($rawResponse, true);

if ($httpCode !== 200) {
    $errMsg = isset($response['error']['message']) ? $response['error']['message'] : 'Unknown error (HTTP ' . $httpCode . ').';
    echo json_encode(['description' => 'Vision API error: ' . $errMsg]);
    exit;
}

$description = isset($response['content'][0]['text']) ? $response['content'][0]['text'] : 'Could not describe the image.';

echo json_encode(['description' => $description]);
