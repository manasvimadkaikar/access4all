<?php
// api/simplify.php
// Takes { text: "...", level: 0|1|2 } and returns { simplified: "..." }
//
// This is a rule-based demo (a small word-substitution list) so the
// full frontend -> backend -> frontend flow works out of the box on XAMPP,
// with no API key required.
//
// TO UPGRADE TO REAL AI SIMPLIFICATION:
// Replace the body of this file with a cURL call to an LLM API (for example
// the Anthropic API) and pass $text in the prompt, asking it to rewrite the
// text simply. See the commented example near the bottom of this file.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$input = json_decode(file_get_contents('php://input'), true);
$text = isset($input['text']) ? $input['text'] : '';
$level = isset($input['level']) ? intval($input['level']) : 1;

if (trim($text) === '') {
    echo json_encode(['simplified' => '']);
    exit;
}

$replacements = [
    'individuals'        => 'people',
    'are required to'    => 'must',
    'adhere to'           => 'follow',
    'prescribed'          => 'set',
    'guidelines'          => 'rules',
    'prior to'            => 'before',
    'commencing'          => 'starting',
    'the examination'     => 'the test',
    'characterized by'    => 'known for',
    'utilize'             => 'use',
    'facilitate'          => 'help',
    'demonstrate'         => 'show',
    'subsequently'        => 'later',
    'approximately'       => 'about',
];

foreach ($replacements as $complex => $simple) {
    $text = preg_replace('/' . preg_quote($complex, '/') . '/i', $simple, $text);
}

if ($level >= 1) {
    // Break long comma-joined clauses into separate short sentences.
    $text = preg_replace('/,\s+/', ".\n", $text);
}

echo json_encode(['simplified' => $text]);

/*
Example of a real AI-powered version using the Anthropic API:

$apiKey = getenv('ANTHROPIC_API_KEY');
$payload = json_encode([
    'model' => 'claude-sonnet-4-6',
    'max_tokens' => 300,
    'messages' => [[
        'role' => 'user',
        'content' => "Rewrite this in very simple, short sentences for a reader with dyslexia:\n\n" . $text
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

echo json_encode(['simplified' => $response['content'][0]['text'] ?? $text]);
*/
