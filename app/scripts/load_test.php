<?php

/**
 * Burst load generator for the public submission endpoint.
 *
 * Demonstrates the "large, sudden bursts of submissions" requirement by
 * firing a configurable number of concurrent POSTs at /submit and
 * reporting throughput, latency, and how many succeeded vs. were
 * rate-limited vs. errored.
 *
 * Usage:
 *   php scripts/load_test.php <account_api_key> <form_slug> [total] [concurrency]
 *
 * Example:
 *   php scripts/load_test.php abc123XYZ newsletter-signup-x7z9k1 2000 100
 *
 * Requires guzzlehttp/guzzle (composer require guzzlehttp/guzzle if not
 * already pulled in as a transitive dependency).
 */

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;

[, $apiKey, $slug, $total, $concurrency] = array_pad($argv, 5, null);

if (!$apiKey || !$slug) {
    fwrite(STDERR, "Usage: php scripts/load_test.php <account_api_key> <form_slug> [total] [concurrency]\n");
    exit(1);
}

$total = (int) ($total ?? 500);
$concurrency = (int) ($concurrency ?? 50);
$baseUrl = getenv('APP_URL') ?: 'http://localhost:8000';

$client = new Client(['base_uri' => $baseUrl, 'timeout' => 10, 'http_errors' => false]);

$results = ['2xx' => 0, '429' => 0, '4xx' => 0, '5xx' => 0, 'error' => 0];
$latencies = [];

$requests = function () use ($total, $apiKey, $slug) {
    for ($i = 0; $i < $total; $i++) {
        yield new Request(
            'POST',
            "/api/public/{$apiKey}/forms/{$slug}/submit",
            ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
            json_encode(['data' => ['email' => "load-test-{$i}@example.com"]])
        );
    }
};

$start = microtime(true);

$pool = new Pool($client, $requests(), [
    'concurrency' => $concurrency,
    'fulfilled' => function ($response) use (&$results) {
        $code = $response->getStatusCode();
        if ($code === 429) {
            $results['429']++;
        } elseif ($code < 300) {
            $results['2xx']++;
        } elseif ($code < 500) {
            $results['4xx']++;
        } else {
            $results['5xx']++;
        }
    },
    'rejected' => function () use (&$results) {
        $results['error']++;
    },
]);

$pool->promise()->wait();

$elapsed = microtime(true) - $start;

printf(
    "Sent %d requests at concurrency %d in %.2fs (%.1f req/s)\n",
    $total,
    $concurrency,
    $elapsed,
    $total / max($elapsed, 0.001)
);
printf(
    "2xx: %d | 429 (rate-limited): %d | other 4xx: %d | 5xx: %d | transport errors: %d\n",
    $results['2xx'],
    $results['429'],
    $results['4xx'],
    $results['5xx'],
    $results['error']
);

if ($results['5xx'] > 0 || $results['error'] > 0) {
    fwrite(STDERR, "Non-zero server/transport errors under burst — inspect logs before treating this run as passing.\n");
    exit(1);
}
