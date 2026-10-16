<?php

declare(strict_types=1);

namespace App\Service\OpeningImport;

final readonly class ValidatedCatalogue
{
    /**
     * @param array<string, SourceOpening>                                $records
     * @param array<string, array{sha256: string, bytes: int, rows: int}> $files
     */
    public function __construct(public string $sha, public OpeningGraph $graph, public array $records, public array $files)
    {
    }
}
