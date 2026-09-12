<?php
// api/simplify.php
// Takes { text: "...", level: 0|1|2 } and returns { simplified: "..." }
//
// Uses the Anthropic API to genuinely rewrite the text in simple language,
// suited for a reader with dyslexia. Needs an API key — see api/config.sample.php
// (the same config.php you already set up for the Blind mode image description
// also powers this).

// Temporary debugging: surface real PHP errors instead of a blank page.
ini_set('display_errors', '0'); // keep off for the browser (we return JSON manually below)
error_reporting(E_ALL);
set_time_limit(60); // make sure PHP's own timeout can't cut off curl early

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Catch any fatal error and still return valid JSON instead of a blank page.
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(200);
        echo json_encode(['simplified' => '', 'debug_error' => $error['message'] . ' in ' . $error['file'] . ' line ' . $error['line']]);
    }
});

$configFile = __DIR__ . '/config.php';

$input = json_decode(file_get_contents('php://input'), true);
$text = isset($input['text']) ? trim($input['text']) : '';
$level = isset($input['level']) ? intval($input['level']) : 1;

if ($text === '') {
    echo json_encode(['simplified' => '']);
    exit;
}

// Fallback word list, used only if no API key is set up yet.
function fallbackSimplify($text, $level) {
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
        $text = preg_replace('/,\s+/', ".\n", $text);
    }
    return $text;
}

if (!file_exists($configFile)) {
    echo json_encode(['simplified' => fallbackSimplify($text, $level)]);
    exit;
}

require_once $configFile;

if (!defined('ANTHROPIC_API_KEY') || ANTHROPIC_API_KEY === 'your-api-key-here' || ANTHROPIC_API_KEY === '') {
    echo json_encode(['simplified' => fallbackSimplify($text, $level)]);
    exit;
}

$levelInstruction = [
    0 => 'Simplify it slightly: keep most of the original wording, but replace the hardest words with easier ones.',
    1 => 'Simplify it clearly: use short sentences, everyday words, and one idea per sentence.',
    2 => 'Simplify it as much as possible: very short sentences (5-8 words each), the simplest everyday words, one idea per line.'
];
$instruction = isset($levelInstruction[$level]) ? $levelInstruction[$level] : $levelInstruction[1];

$prompt = "Rewrite the following text for a reader with dyslexia. $instruction "
        . "Keep the original meaning and all key facts. Do not add a title, headers, quotation marks, or any commentary — output only the rewritten text.\n\n"
        . "Text:\n" . $text;

$payload = json_encode([
    'model' => 'claude-sonnet-5',
    'max_tokens' => 500,
    'messages' => [[
        'role' => 'user',
        'content' => $prompt
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
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$rawResponse = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($rawResponse === false) {
    // Network failure — fall back rather than showing a dead end.
    echo json_encode(['simplified' => fallbackSimplify($text, $level)]);
    exit;
}

$response = json_decode($rawResponse, true);

if ($httpCode !== 200) {
    $errMsg = isset($response['error']['message']) ? $response['error']['message'] : ('HTTP ' . $httpCode);
    echo json_encode(['simplified' => fallbackSimplify($text, $level) . "\n\n[AI simplification unavailable: $errMsg]"]);
    exit;
}

$simplified = isset($response['content'][0]['text']) ? trim($response['content'][0]['text']) : fallbackSimplify($text, $level);

echo json_encode(['simplified' => $simplified]);
