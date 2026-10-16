<?php

declare(strict_types=1);

namespace App\Service\OpeningImport;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class OpeningSourceFetcher
{
    public function __construct(private HttpClientInterface $client)
    {
    }

    public function resolveLatestSha(): string
    {
        $response = $this->client->request('GET', 'https://api.github.com/repos/lichess-org/chess-openings/commits/master', [
            'timeout' => 10, 'max_duration' => 30, 'max_redirects' => 0,
            'headers' => ['Accept' => 'application/vnd.github+json', 'User-Agent' => 'chess-openings-php'],
        ]);
        try {
            if (200 !== $response->getStatusCode()) {
                throw new \RuntimeException('Cannot resolve upstream SHA: HTTP '.$response->getStatusCode());
            }
            $body = '';
            foreach ($this->client->stream($response) as $chunk) {
                $body .= $chunk->getContent();
                if (strlen($body) > 262_144) {
                    throw new \RuntimeException('Upstream commit response exceeds size limit.');
                }
            }
            $data = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
            $sha = is_array($data) ? ($data['sha'] ?? null) : null;
            if (!is_string($sha) || !preg_match('/\A[0-9a-f]{40}\z/', $sha)) {
                throw new \RuntimeException('Upstream commit response has no immutable SHA.');
            }

            return $sha;
        } finally {
            $response->cancel();
        }
    }

    /** Downloads only one immutable upstream revision into an empty private directory. */
    public function fetch(string $sha, string $directory): void
    {
        if (!preg_match('/\A[0-9a-f]{40}\z/', $sha)) {
            throw new \InvalidArgumentException('Source SHA must be 40 lowercase hexadecimal characters');
        }
        foreach (range('a', 'e') as $letter) {
            $file = $letter.'.tsv';
            $response = $this->client->request('GET', 'https://raw.githubusercontent.com/lichess-org/chess-openings/'.$sha.'/'.$file, [
                'timeout' => 10,
                'max_duration' => 60,
                'max_redirects' => 0,
                'headers' => ['Accept' => 'text/plain', 'User-Agent' => 'chess-openings-php-importer'],
            ]);
            $stream = null;
            try {
                if (200 !== $response->getStatusCode()) {
                    throw new \RuntimeException($file.': upstream HTTP '.$response->getStatusCode());
                }
                $stream = fopen($directory.'/'.$file, 'xb');
                if (false === $stream) {
                    throw new \RuntimeException($file.': cannot create private download');
                }
                chmod($directory.'/'.$file, 0600);
                $size = 0;
                foreach ($this->client->stream($response) as $chunk) {
                    $data = $chunk->getContent();
                    $size += strlen($data);
                    if ($size > TsvOpeningParser::MAX_BYTES) {
                        throw new \RuntimeException($file.': upstream file exceeds size limit');
                    }
                    if (fwrite($stream, $data) !== strlen($data)) {
                        throw new \RuntimeException($file.': incomplete download write');
                    }
                }
            } finally {
                $response->cancel();
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }
    }
}
