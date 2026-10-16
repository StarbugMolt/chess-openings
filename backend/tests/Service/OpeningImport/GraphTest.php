<?php

declare(strict_types=1);

namespace App\Tests\Service\OpeningImport;

use App\Service\OpeningImport\OpeningGraphBuilder;
use App\Service\OpeningImport\PChessMoveReplayer;
use App\Service\OpeningImport\PgnMovetextParser;
use App\Service\OpeningImport\SourceOpening;
use App\Service\OpeningImport\TsvOpeningParser;
use PHPUnit\Framework\TestCase;

final class GraphTest extends TestCase
{
    private function builder(): OpeningGraphBuilder
    {
        return new OpeningGraphBuilder(new TsvOpeningParser(), new PgnMovetextParser(), new PChessMoveReplayer());
    }

    public function testTranspositionsDeduplicatePositionsAndEdgesWhileKeepingPaths(): void
    {
        $sources = [
            new SourceOpening('a.tsv', 2, 'A00', 'First move order', '1. d4 d5 2. c4 c6 3. Nf3'),
            new SourceOpening('a.tsv', 3, 'A00', 'Other move order', '1. d4 c6 2. c4 d5 3. Nf3'),
        ];
        $graph = $this->builder()->build($sources);
        self::assertCount(2, $graph->paths);
        self::assertCount(8, $graph->positions);
        self::assertCount(8, $graph->edges);
        $paths = array_values($graph->paths);
        self::assertSame($paths[0]['steps'][4], $paths[1]['steps'][4]);
        self::assertNotSame($paths[0]['steps'][2], $paths[1]['steps'][2]);
        self::assertSame($graph->checksum(), $this->builder()->build(array_reverse($sources))->checksum());
        self::assertSame($graph->checksum(), $this->builder()->build($sources)->checksum());
    }

    public function testSamePathCanHaveDistinctNamesButExactRecordsAreRejected(): void
    {
        $source = new SourceOpening('a.tsv', 2, 'A00', 'Name', '1. e4');
        $alias = new SourceOpening('a.tsv', 3, 'A00', 'Alias', '1. e4');
        $graph = $this->builder()->build([$source, $alias]);
        self::assertCount(1, $graph->edges);
        self::assertCount(2, $graph->paths);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('duplicate source record');
        $this->builder()->build([$source, $source]);
    }

    public function testPinnedFixtureFilesMatchProvenanceAndBuildRepeatably(): void
    {
        $root = __DIR__.'/../../Fixtures/Openings';
        $manifest = json_decode(file_get_contents($root.'/provenance.json'), true, flags: JSON_THROW_ON_ERROR);
        foreach ($manifest['files'] as $file => $metadata) {
            self::assertSame($metadata['fixtureSha256'], hash_file('sha256', $root.'/source/'.$file));
        }
        $first = $this->builder()->build($this->builder()->directory($root.'/source'));
        $second = $this->builder()->build($this->builder()->directory($root.'/source'));
        self::assertCount(10, $first->paths);
        self::assertSame(93, $first->plies);
        self::assertSame($first->checksum(), $second->checksum());
    }
}
