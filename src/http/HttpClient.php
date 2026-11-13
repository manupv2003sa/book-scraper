<?php

namespace App\Http;

use RuntimeException;

class HttpClient
{
    public function get(string $url): HttpResponse
    {
        $curl = curl_init($url);

        if ($curl === false) {
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
                throw new RuntimeException(
                    "Failed to configure cURL options."
                );
            }

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

            return new HttpResponse($statusCode, [], $response);

        } finally {
            curl_close($curl);
        }
    }
}
