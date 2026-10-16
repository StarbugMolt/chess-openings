<?php

declare(strict_types=1);

namespace App\Service\OpeningImport;

final class TsvOpeningParser
{
    public const MAX_BYTES = 4_194_304;

    /** @return \Generator<int, SourceOpening> */
    public function parse(string $path): \Generator
    {
        $file = basename($path);
        $stream = @fopen($path, 'rb');
        if (false === $stream) {
            throw new \InvalidArgumentException($file.': cannot open source');
        }
        try {
            $content = stream_get_contents($stream, self::MAX_BYTES + 1);
            if (false === $content || strlen($content) > self::MAX_BYTES || !mb_check_encoding($content, 'UTF-8')) {
                throw new \InvalidArgumentException($file.': source exceeds size limit or is not UTF-8');
            }
            $this->validateQuotes($content, $file);
            rewind($stream);
            if (['eco', 'name', 'pgn'] !== fgetcsv($stream, null, "\t", '"', '')) {
                throw new \InvalidArgumentException($file.':1: expected exactly eco, name, pgn columns');
            }
            $seen = [];
            $row = 1;
            while (false !== $fields = fgetcsv($stream, null, "\t", '"', '')) {
                ++$row;
                $location = $file.':'.$row;
                if (3 !== count($fields) || in_array(null, $fields, true)) {
                    throw new \InvalidArgumentException($location.': expected exactly three nonempty fields');
                }
                [$eco, $name, $pgn] = $fields;
                if (1 !== preg_match('/\A[A-E][0-9]{2}\z/', $eco)) {
                    throw new \InvalidArgumentException($location.': invalid ECO code');
                }
                if ('' === trim($name) || strlen($name) > 512 || preg_match('/[\x00-\x1f\x7f]/', $name)) {
                    throw new \InvalidArgumentException($location.': invalid opening name');
                }
                if ('' === trim($pgn) || strlen($pgn) > 32_768) {
                    throw new \InvalidArgumentException($location.': empty or oversized PGN');
                }
                $opening = new SourceOpening($file, $row, $eco, $name, $pgn);
                $uid = $opening->fingerprint();
                if (isset($seen[$uid])) {
                    throw new \InvalidArgumentException($location.': duplicate source record (first row '.$seen[$uid].')');
                }
                $seen[$uid] = $row;
                yield $opening;
            }
            if (1 === $row) {
                throw new \InvalidArgumentException($file.': no opening records');
            }
        } finally {
            fclose($stream);
        }
    }

    // fgetcsv accepts unterminated/misplaced quotes. Check the TSV dialect before parsing.
    private function validateQuotes(string $content, string $file): void
    {
        $quoted = false;
        $start = true;
        $closed = false;
        $row = 1;
        $length = strlen($content);
        for ($i = 0; $i < $length; ++$i) {
            $char = $content[$i];
            if ($quoted) {
                if ('"' === $char) {
                    if ($i + 1 < $length && '"' === $content[$i + 1]) {
                        ++$i;
                    } else {
                        $quoted = false;
                        $closed = true;
                    }
                }
                continue;
            }
            if ("\r" === $char && $i + 1 < $length && "\n" === $content[$i + 1]) {
                continue;
            }
            if ("\t" === $char || "\n" === $char) {
                $start = true;
                $closed = false;
                if ("\n" === $char) {
                    ++$row;
                }
            } elseif ('"' === $char && $start) {
                $quoted = true;
                $start = false;
            } elseif ($closed || '"' === $char || "\r" === $char) {
                throw new \InvalidArgumentException($file.':'.$row.': malformed TSV quoting');
            } else {
                $start = false;
            }
        }
        if ($quoted) {
            throw new \InvalidArgumentException($file.':'.$row.': unterminated quoted field');
        }
    }
}
