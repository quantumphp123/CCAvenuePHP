<?php

namespace App\Core;

class Redirect
{
    /**
     * Redirect to the specified URL with optional query parameters.
     *
     * @param string $url The URL to redirect to
     * @param array $queryParams (optional) Associative array of query parameters to append to the URL
     * @param int $statusCode HTTP status code (default is 302)
     */
    public static function to(string $url, array $queryParams = [], int $statusCode = 302): void
    {
        // If query parameters are provided, append them to the URL
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        // Perform the redirect
        header("Location: $url", true, $statusCode);
        exit;
    }
}
