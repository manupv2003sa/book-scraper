<?php
namespace App\http;
use RuntimeException;

class HttpClient
{
    public function get(string $url): string
    {
        $curl = curl_init($url);

        if ($curl === false) {
            throw new RuntimeException("Failed to initialize cURL.");
        }

        try {
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_USERAGENT => 'BookScraper/1.0'
            ]);

            $response = curl_exec($curl);

            if ($response === false) {
                throw new RuntimeException(
                    "cURL error: " . curl_error($curl)
                );
            }

            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($statusCode < 200 || $statusCode >= 300) {
                throw new RuntimeException(
                    "HTTP request failed with status code: " . $statusCode
                );
            }

            return $response;

        } finally {
            curl_close($curl);
        }
    }
}
