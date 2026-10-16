<?php

declare(strict_types=1);

namespace App\Service\OpeningImport;

final readonly class SourceOpening
{
    public function __construct(
        public string $file,
        public int $row,
        public string $eco,
        public string $name,
        public string $pgn,
    ) {
    }

    public function location(): string
    {
        return $this->file.':'.$this->row;
    }

    public function fingerprint(): string
    {
        // Length-safe, ordered identity. No lossy case, whitespace or Unicode folding.
        return hash('sha256', json_encode([$this->eco, $this->name, $this->pgn], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }
}
