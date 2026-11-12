<?php
namespace App\Support;

class UrlResolver
{
    private string $baseUrl;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/') . '/';
    }

    
public function resolve(
    string $url,
    ?string $currentPageUrl = null
): string {

    // Already an absolute URL
    if (
        str_starts_with($url, 'http://') ||
        str_starts_with($url, 'https://')
    ) {
        return $url;
    }

    // Determine the base URL
    $base = $currentPageUrl ?? $this->baseUrl;

    $parts = parse_url($base);

    $domain = $parts['scheme'] . '://' . $parts['host'];

    // Root-relative URL
    if (str_starts_with($url, '/')) {
        $path = $url;
    } else {
        // Get current page path
        $basePath = $parts['path'] ?? '/';

        // Find the last slash without using dirname()
        $lastSlash = strrpos($basePath, '/');

        $directory = $lastSlash !== false
            ? substr($basePath, 0, $lastSlash + 1)
            : '/';

        $path = $directory . $url;
    }

    // Normalize ../ and ./ path segments
    $segments = explode('/', $path);
    $result = [];

    foreach ($segments as $segment) {

        if ($segment === '..') {
            array_pop($result);
        } elseif ($segment !== '' && $segment !== '.') {
            $result[] = $segment;
        }
    }

    return $domain . '/' . implode('/', $result);
}

}
