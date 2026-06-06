<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class HeaderAnalyzerService
{
    private array $privateRanges = [
        '/^127\./',
        '/^10\./',
        '/^192\.168\./',
        '/^172\.(1[6-9]|2[0-9]|3[0-1])\./',
        '/^169\.254\./',
        '/^::1$/',
        '/^fc00:/i',
        '/^fe80:/i',
    ];

    public function analyze(string $url): array
    {
        $this->validateUrl($url);

        $response = Http::timeout(5)
            ->withoutRedirecting()
            ->head($url);

        $headers = $response->headers();

        return [
            'meta' => $this->extractMeta($response),
            'headers' => $headers,
            'security' => $this->analyzeSecurityHeaders($headers),
        ];
    }

    private function validateUrl(string $url): void
    {
        $parsed = parse_url($url);

        if (!in_array($parsed['scheme'] ?? '', ['http', 'https'])) {
            throw new \InvalidArgumentException('Solo se permiten URLs HTTP/HTTPS.');
        }

        $host = $parsed['host'] ?? '';
        $ip = gethostbyname($host);

        foreach ($this->privateRanges as $pattern) {
            if (preg_match($pattern, $ip)) {
                throw new \InvalidArgumentException('La URL apunta a una dirección privada.');
            }
        }
    }

    private function extractMeta($response): array
    {
        return [
            'status' => $response->status(),
            'redirect' => $response->header('Location'),
        ];
    }

    private function analyzeSecurityHeaders(array $headers): array
    {
        $checks = [
        'HSTS'            => 'Strict-Transport-Security',
        'CSP'             => 'Content-Security-Policy',
        'X-Frame-Options' => 'X-Frame-Options',
        'X-Content-Type'  => 'X-Content-Type-Options',
        'Referrer-Policy' => 'Referrer-Policy',
        'Permissions'     => 'Permissions-Policy',
    ];

        $result = [];
        foreach ($checks as $label => $headerKey) {
            $found = collect($headers)->first(fn($v, $k) => 
            strtolower($k) === strtolower($headerKey)
        );
            $result[$label] = [
            'present' => !is_null($found),
            'value'   => is_array($found) ? $found[0] : $found,
        ];
        }

        return $result;
    }
}