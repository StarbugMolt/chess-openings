<?php

declare(strict_types=1);

namespace App\Service\OpeningImport;

interface ChessMoveReplayerInterface
{
    /** @param list<string> $sanMoves */
    public function replay(array $sanMoves, ?string $initialFen = null): ReplayedLine;
}
