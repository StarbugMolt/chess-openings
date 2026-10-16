<?php

declare(strict_types=1);

namespace App\Service\OpeningImport;

final readonly class OpeningSourceLoader
{
    public function __construct(private OpeningSourceFetcher $fetcher, private OpeningGraphBuilder $builder)
    {
    }

    public function load(?string $sha = null, ?string $sourceDirectory = null): ValidatedCatalogue
    {
        if (null !== $sourceDirectory && null === $sha) {
            throw new \InvalidArgumentException('--source-directory requires --source-ref with its immutable upstream SHA.');
        }
        $sha ??= $this->fetcher->resolveLatestSha();
        if (!preg_match('/\A[0-9a-f]{40}\z/', $sha)) {
            throw new \InvalidArgumentException('--source-ref must be a 40-character lowercase immutable Git SHA.');
        }
        $directory = sys_get_temp_dir().'/opening-sync-'.bin2hex(random_bytes(8));
        if (!mkdir($directory, 0700)) {
            throw new \RuntimeException('Cannot create private source workspace.');
        }
        try {
            if (null === $sourceDirectory) {
                $this->fetcher->fetch($sha, $directory);
            } else {
                foreach (range('a', 'e') as $letter) {
                    $file = $letter.'.tsv';
                    $path = $sourceDirectory.'/'.$file;
                    if (!is_file($path) || is_link($path)) {
                        throw new \InvalidArgumentException($file.': missing regular source file.');
                    }
                    $input = fopen($path, 'rb');
                    if (false === $input) {
                        throw new \RuntimeException($file.': cannot read source.');
                    }
                    $output = fopen($directory.'/'.$file, 'xb');
                    if (false === $output) {
                        fclose($input);
                        throw new \RuntimeException($file.': cannot copy source.');
                    }
                    try {
                        chmod($directory.'/'.$file, 0600);
                        $bytes = stream_copy_to_stream($input, $output, TsvOpeningParser::MAX_BYTES + 1);
                        if (false === $bytes || $bytes > TsvOpeningParser::MAX_BYTES) {
                            throw new \InvalidArgumentException($file.': source exceeds size limit.');
                        }
                    } finally {
                        fclose($input);
                        fclose($output);
                    }
                }
            }
            $records = [];
            $files = [];
            foreach (range('a', 'e') as $letter) {
                $file = $letter.'.tsv';
                $hash = hash_file('sha256', $directory.'/'.$file);
                $bytes = filesize($directory.'/'.$file);
                if (false === $hash || false === $bytes) {
                    throw new \RuntimeException($file.': cannot inspect source.');
                }
                $files[$file] = ['sha256' => $hash, 'bytes' => $bytes, 'rows' => 0];
            }
            $source = function () use ($directory, &$records): \Generator {
                foreach ($this->builder->directory($directory) as $record) {
                    $records[$record->fingerprint()] = $record;
                    yield $record;
                }
            };
            $graph = $this->builder->build($source());
            $rowCounts = array_count_values(array_map(static fn (SourceOpening $record): string => $record->file, $records));
            foreach ($files as $file => $metadata) {
                $files[$file]['rows'] = $rowCounts[$file] ?? 0;
            }

            return new ValidatedCatalogue($sha, $graph, $records, $files);
        } finally {
            foreach (glob($directory.'/*.tsv') ?: [] as $file) {
                unlink($file);
            }
            rmdir($directory);
        }
    }
}
