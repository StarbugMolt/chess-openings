<?php

declare(strict_types=1);

namespace App\Service\OpeningImport;

use PChess\Chess\Chess;
use PChess\Chess\Move;

final class PChessMoveReplayer implements ChessMoveReplayerInterface
{
    public function replay(array $sanMoves, ?string $initialFen = null): ReplayedLine
    {
        $chess = new Chess($initialFen);
        $initial = $this->canonicalFen($chess);
        $moves = [];
        foreach ($sanMoves as $index => $san) {
            $move = $chess->move($san);
            if (null === $move || $move->san !== $san) {
                throw new \InvalidArgumentException('ply '.($index + 1).': illegal or noncanonical SAN '.$san);
            }
            $fen = $this->canonicalFen($chess);
            $moves[] = new ReplayedMove($move->from.$move->to.($move->promotion ?? ''), $san, $fen, $this->key($fen));
        }

        return new ReplayedLine($initial, $this->key($initial), $moves);
    }

    private function canonicalFen(Chess $chess): string
    {
        $fields = explode(' ', $chess->fen());
        if ('-' !== $fields[3]) {
            $legalCapture = false;
            foreach ($chess->moves() as $move) {
                if (0 !== ($move->flags & Move::BITS['EP_CAPTURE'])) {
                    $legalCapture = true;
                    break;
                }
            }
            if (!$legalCapture) {
                $fields[3] = '-';
            }
        }

        return implode(' ', $fields);
    }

    private function key(string $fen): string
    {
        return implode(' ', array_slice(explode(' ', $fen), 0, 4));
    }
}
