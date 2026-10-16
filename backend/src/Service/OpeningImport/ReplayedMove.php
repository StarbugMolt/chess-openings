<?php

declare(strict_types=1);

namespace App\Service\OpeningImport;

final readonly class ReplayedMove
{
    public function __construct(public string $uci, public string $san, public string $fen, public string $key)
    {
    }
}
