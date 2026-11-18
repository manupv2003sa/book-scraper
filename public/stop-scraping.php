<?php

header('Content-Type: application/json');

$stopFile = dirname(__DIR__) . '/storage/stop-request.txt';

if (file_put_contents($stopFile, 'stop', LOCK_EX) === false) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to create stop request'
    ]);

    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Stop request sent'
]);

exit;