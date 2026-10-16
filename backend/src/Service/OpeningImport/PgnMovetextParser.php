<?php

declare(strict_types=1);

namespace App\Service\OpeningImport;

/** Bounded main-line movetext, not a general PGN game/study importer. */
final class PgnMovetextParser
{
    /** @return list<string> */
    public function parse(string $pgn): array
    {
        if (strlen($pgn) > 32_768) {
            throw new \InvalidArgumentException('ply 1: PGN exceeds size limit');
        }
        $moves = [];
        $offset = 0;
        $finished = false;
        $numberPending = false;
        while ($offset < strlen($pgn)) {
            if (preg_match('/\G(?:\s+|\{[^{}]*\}|;[^\r\n]*(?:\r?\n|\z))/', $pgn, $match, 0, $offset)) {
                $offset += strlen($match[0]);
                continue;
            }
            $ply = count($moves) + 1;
            if ($finished) {
                throw new \InvalidArgumentException('ply '.$ply.': content after game result');
            }
            if (preg_match('/\G([1-9][0-9]*)\.(\.\.)?/', $pgn, $match, 0, $offset)) {
                $expected = (int) ceil($ply / 2);
                $black = isset($match[2]);
                if ($numberPending || (int) $match[1] !== $expected || $black !== (0 === $ply % 2)) {
                    throw new \InvalidArgumentException('ply '.$ply.': inconsistent move number');
                }
                $numberPending = true;
                $offset += strlen($match[0]);
                continue;
            }
            if (!preg_match('/\G[^\s{};()\[\]]+/', $pgn, $match, 0, $offset)) {
                throw new \InvalidArgumentException('ply '.$ply.': unsupported PGN syntax (tags, variations or malformed comment)');
            }
            $token = $match[0];
            $offset += strlen($token);
            if (in_array($token, ['1-0', '0-1', '1/2-1/2', '*'], true)) {
                if ($numberPending || [] === $moves) {
                    throw new \InvalidArgumentException('ply '.$ply.': misplaced game result');
                }
                $finished = true;
                continue;
            }
            if (preg_match('/\A\$([0-9]{1,3})\z/', $token, $nag) && (int) $nag[1] <= 255 && [] !== $moves && !$numberPending) {
                continue;
            }
            $token = preg_replace('/[!?]{1,2}\z/', '', $token) ?? $token;
            // Accept the common zero spelling of castling, then require canonical legal SAN.
            $token = str_replace('0', 'O', $token);
            if (!preg_match('/\A(?:O-O(?:-O)?|[KQRBN](?:[a-h][1-8]?|[1-8])?x?[a-h][1-8]|[a-h](?:x[a-h])?[1-8](?:=[QRBN])?)[+#]?\z/', $token)) {
                throw new \InvalidArgumentException('ply '.$ply.': malformed SAN token '.mb_strcut(mb_scrub($token, 'UTF-8'), 0, 40, 'UTF-8'));
            }
            $moves[] = $token;
            $numberPending = false;
            if (count($moves) > 512) {
                throw new \InvalidArgumentException('ply '.$ply.': line exceeds 512 plies');
            }
        }
        if ([] === $moves || $numberPending) {
            throw new \InvalidArgumentException('ply '.(count($moves) + 1).': missing move');
        }

        return $moves;
    }
}
