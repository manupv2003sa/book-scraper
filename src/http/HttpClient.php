<?php

namespace App\Http;

use App\Logging\LoggerInterface;
use RuntimeException;

class HttpClient
{
    private const MAX_ATTEMPTS = 3;
    private const RETRY_DELAY = 1;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function get(string $url): HttpResponse
    {
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {

            $curl = curl_init($url);

            if ($curl === false) {

                $this->logger->error('Failed to initialize cURL', [
                    'url' => $url
                ]);
                throw new RuntimeException("Failed to initialize cURL.");
            }




            try {
                $optionsSet = curl_setopt_array($curl, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_USERAGENT => 'BookScraper/1.0',
                    CURLOPT_DNS_CACHE_TIMEOUT => 300,
                ]);

                if ($optionsSet === false) {
                    $this->logger->error('Failed to initialize cURL', [
                        'url' => $url
                    ]);
                    throw new RuntimeException(
                        "Failed to configure cURL options."
                    );
                }

                $response = curl_exec($curl);

                if ($response === false) {
                    $errorCode = curl_errno($curl);
                    $errorMessage = curl_error($curl);

                    // Retry temporary network and timeout errors.
                    if ($attempt < self::MAX_ATTEMPTS) {
                        $this->logger->warning('Retrying HTTP request', [
                            'url' => $url,
                            'attempt' => $attempt,
                            'error' => $errorMessage
                        ]);
                        sleep(self::RETRY_DELAY);
                        continue;
                    }

                    $this->logger->error('HTTP request failed', [
                        'url' => $url,
                        'error' => $errorMessage,
                        'attempts' => $attempt
                    ]);

                    throw new RuntimeException(
                        "cURL error ($errorCode): $errorMessage"
                    );
                }

                $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                // Retry only HTTP 5xx server errors.
                $retryableStatuses = [429, 500, 502, 503, 504];

                if (in_array($statusCode, $retryableStatuses, true)) {
                    if ($attempt < self::MAX_ATTEMPTS) {

                        $this->logger->warning('Retrying HTTP request', [
                            'url' => $url,
                            'attempt' => $attempt,
                            'status' => $statusCode
                        ]);

                        sleep(self::RETRY_DELAY);
                        continue;
                    }

                    $this->logger->error('HTTP server error after maximum attempts', [
                        'url' => $url,
                        'status' => $statusCode,
                        'attempts' => $attempt
                    ]);

                    throw new RuntimeException(
                        "HTTP server error after "
                            . self::MAX_ATTEMPTS
                            . " attempts. Status code: "
                            . $statusCode
                    );
                }

                // Do not retry permanent HTTP errors.
                if ($statusCode < 200 || $statusCode >= 300) {

                    $this->logger->error('HTTP request failed', [
                        'url' => $url,
                        'status' => $statusCode
                    ]);
                    throw new RuntimeException(
                        "HTTP request failed with status code: "
                            . $statusCode
                    );
                }
                $this->logger->info(
                    'HTTP request successful',
                    [
                        'url' => $url,
                        'status' => $statusCode
                    ]
                );

                return new HttpResponse($statusCode, [], $response);
            } finally {
                curl_close($curl);
            }
        }

        throw new RuntimeException("HTTP request failed after all retry attempts.");
    }
}
