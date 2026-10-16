<?php

declare(strict_types=1);

namespace App\Service\OpeningImport;

/** Validated in-memory source graph, separate from persistence and curated curriculum. */
final class OpeningGraph
{
    /** @var array<string, true> */
    public array $positions = [];
    /** @var array<string, array{from: string, uci: string, san: string, to: string}> */
    public array $edges = [];
    /** @var array<string, array{eco: string, name: string, pgn: string, steps: list<string>}> */
    public array $paths = [];
    public int $plies = 0;

    public function add(SourceOpening $opening, ReplayedLine $line): void
    {
        $uid = $opening->fingerprint();
        if (isset($this->paths[$uid])) {
            throw new \InvalidArgumentException($opening->location().': duplicate source record across files');
        }
        $from = $line->initialKey;
        $this->positions[$from] = true;
        $steps = [];
        foreach ($line->moves as $move) {
            $edgeKey = $from.'|'.$move->uci;
            $edge = ['from' => $from, 'uci' => $move->uci, 'san' => $move->san, 'to' => $move->key];
            if (isset($this->edges[$edgeKey]) && $this->edges[$edgeKey] !== $edge) {
                throw new \InvalidArgumentException($opening->location().': inconsistent destination/SAN for '.$edgeKey);
            }
            $this->positions[$move->key] = true;
            $this->edges[$edgeKey] = $edge;
            $steps[] = $edgeKey;
            $from = $move->key;
            ++$this->plies;
        }
        $this->paths[$uid] = ['eco' => $opening->eco, 'name' => $opening->name, 'pgn' => $opening->pgn, 'steps' => $steps];
    }

    public function checksum(): string
    {
        $positions = $this->positions;
        $edges = $this->edges;
        $paths = $this->paths;
        ksort($positions, SORT_STRING);
        ksort($edges, SORT_STRING);
        ksort($paths, SORT_STRING);

        return hash('sha256', json_encode([$positions, $edges, $paths], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }
}
