<?php

declare(strict_types=1);

namespace App\Service\OpeningImport;

final readonly class OpeningGraphBuilder
{
    public function __construct(private TsvOpeningParser $tsv, private PgnMovetextParser $pgn, private ChessMoveReplayerInterface $replayer)
    {
    }

    /** @param iterable<SourceOpening> $openings */
    public function build(iterable $openings): OpeningGraph
    {
        $graph = new OpeningGraph();
        foreach ($openings as $opening) {
            try {
                $line = $this->replayer->replay($this->pgn->parse($opening->pgn));
            } catch (\InvalidArgumentException $e) {
                throw new \InvalidArgumentException($opening->location().': '.$e->getMessage(), 0, $e);
            }
            $graph->add($opening, $line);
            if (count($graph->paths) > 20_000 || $graph->plies > 250_000) {
                throw new \InvalidArgumentException('Catalogue exceeds the 20,000-entry or 250,000-ply safety limit.');
            }
        }

        return $graph;
    }

    /** @return \Generator<int, SourceOpening> */
    public function directory(string $directory): \Generator
    {
        foreach (range('a', 'e') as $letter) {
            $path = $directory.'/'.$letter.'.tsv';
            if (!is_file($path) || is_link($path)) {
                throw new \InvalidArgumentException($letter.'.tsv: missing regular source file');
            }
            foreach ($this->tsv->parse($path) as $opening) {
                if ($opening->eco[0] !== strtoupper($letter)) {
                    throw new \InvalidArgumentException($opening->location().': ECO family does not match source file');
                }
                yield $opening;
            }
        }
    }
}
