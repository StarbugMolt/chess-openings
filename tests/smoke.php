<?php

declare(strict_types=1);

require __DIR__.'/../backend/vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;

$base = rtrim($argv[1] ?? 'http://127.0.0.1', '/');
$client = HttpClient::create(['timeout' => 10, 'max_redirects' => 0]);
$health = $client->request('GET', $base.'/api/health');
if (in_array('--expect-db-unavailable', $argv, true)) {
    if (503 !== $health->getStatusCode() || ['status' => 'unavailable'] !== $health->toArray(false)) {
        throw new RuntimeException('Database outage must produce a generic 503 response.');
    }
    foreach (['/api/openings/catalogue-status', '/api/openings'] as $path) {
        $response = $client->request('GET', $base.$path);
        if (503 !== $response->getStatusCode() || ['error' => 'Request could not be completed.'] !== $response->toArray(false)) {
            throw new RuntimeException('Catalogue outage must produce a generic JSON 503.');
        }
    }
    echo "Database outage checks passed: health and catalogue APIs return generic 503 responses.\n";
    exit(0);
}
if (200 !== $health->getStatusCode() || ['status' => 'ok'] !== $health->toArray()) {
    throw new RuntimeException('Database readiness failed.');
}
foreach (['/', '/openings', '/a-client-side-route'] as $path) {
    $response = $client->request('GET', $base.$path);
    if (200 !== $response->getStatusCode() || !str_contains($response->getContent(), 'id="app"')) {
        throw new RuntimeException('Frontend/SPA route missing: '.$path);
    }
}
$catalogue = $client->request('GET', $base.'/api/openings/catalogue-status');
$payload = $catalogue->toArray();
$etag = $catalogue->getHeaders()['etag'][0] ?? '';
if (200 !== $catalogue->getStatusCode() || !is_int($payload['catalogueRevision'] ?? null) || '' === $etag) {
    throw new RuntimeException('Catalogue status/revision/ETag missing.');
}
if (304 !== $client->request('GET', $base.'/api/openings/catalogue-status', ['headers' => ['If-None-Match' => $etag]])->getStatusCode()) {
    throw new RuntimeException('Catalogue conditional GET failed.');
}
$page = $client->request('GET', $base.'/api/openings?limit=2')->toArray();
if (($page['catalogueRevision'] ?? null) !== $payload['catalogueRevision'] || count($page['items'] ?? []) > 2) {
    throw new RuntimeException('Catalogue pagination/revision mismatch.');
}
foreach (['/.env', '/composer.json', '/api/missing', '/assets/missing.js'] as $path) {
    if (!in_array($client->request('GET', $base.$path)->getStatusCode(), [403, 404], true)) {
        throw new RuntimeException('Private/missing route was exposed: '.$path);
    }
}
if (405 !== $client->request('POST', $base.'/api/health')->getStatusCode()) {
    throw new RuntimeException('Health must be read-only.');
}
$home = $client->request('GET', $base.'/');
$headers = $home->getHeaders();
if (!in_array('no-cache', $headers['cache-control'] ?? [], true)
    || ($headers['cross-origin-opener-policy'][0] ?? '') !== 'same-origin'
    || !str_contains($headers['permissions-policy'][0] ?? '', 'camera=()')) {
    throw new RuntimeException('HTML cache or browser security headers missing.');
}
$html = $home->getContent();
preg_match_all('/(?:src|href)="(\/assets\/[^\"]+)"/', $html, $matches);
if (count($matches[1]) < 2) {
    throw new RuntimeException('Built JS/CSS missing.');
}
foreach ($matches[1] as $asset) {
    if (200 !== $client->request('GET', $base.$asset)->getStatusCode()
        || !str_contains($client->request('GET', $base.$asset)->getHeaders()['cache-control'][0] ?? '', 'immutable')) {
        throw new RuntimeException('Built asset unavailable: '.$asset);
    }
}
echo "Smoke checks passed: database, catalogue/ETags, SPA, assets, JSON API errors, private paths and HTTP methods.\n";
