<?php
// api/describe-image.php
// Takes { image: "data:image/jpeg;base64,...." } and returns { description: "..." }
//
// This is currently a PLACEHOLDER so the full capture -> send -> speak flow
// works end-to-end for your demo. Real object/scene description needs a
// vision AI model, which needs an API key you'd add here.
//
// TO UPGRADE TO REAL AI VISION:
// Uncomment and fill in the example below with your own Anthropic API key.
// Never commit a real API key to GitHub — read it from an environment
// variable or a local untracked config file instead (see .gitignore).

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$input = json_decode(file_get_contents('php://input'), true);
$image = isset($input['image']) ? $input['image'] : null;

if (!$image) {
    echo json_encode(['description' => 'No image received.']);
    exit;
}

// --- Placeholder response (works with no API key) ---
echo json_encode([
    'description' => 'Photo received. Connect a vision AI API here to describe what the camera sees out loud.'
]);
exit;

/*
Example of a real AI-powered version using the Anthropic API (Claude can
read images directly):

$apiKey = getenv('ANTHROPIC_API_KEY');

// Strip the "data:image/jpeg;base64," prefix, keep only the base64 data.
$base64Data = preg_replace('#^data:image/\w+;base64,#', '', $image);

$payload = json_encode([
    'model' => 'claude-sonnet-4-6',
    'max_tokens' => 300,
    'messages' => [[
        'role' => 'user',
        'content' => [
            [
                'type' => 'image',
                'source' => [
                    'type' => 'base64',
                    'media_type' => 'image/jpeg',
                    'data' => $base64Data
                ]
            ],
            [
                'type' => 'text',
                'text' => 'Describe what is directly in front of a blind user in one or two short spoken sentences, focusing on obstacles, people, doors, and stairs.'
            ]
        ]
    ]]
]);

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-api-key: ' . $apiKey,
    'anthropic-version: 2023-06-01'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = json_decode(curl_exec($ch), true);
curl_close($ch);

echo json_encode(['description' => $response['content'][0]['text'] ?? 'Could not describe the image.']);
*/
