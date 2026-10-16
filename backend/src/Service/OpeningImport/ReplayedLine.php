<?php

declare(strict_types=1);

namespace App\Service\OpeningImport;

final readonly class ReplayedLine
{
    /** @param list<ReplayedMove> $moves */
    public function __construct(public string $initialFen, public string $initialKey, public array $moves)
    {
    }
}
