<?php

declare(strict_types=1);

namespace App\Tests\Service\OpeningImport;

use App\Service\OpeningImport\PChessMoveReplayer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ReplayTest extends TestCase
{
    /** @return iterable<string, array{array<string, mixed>}> */
    public static function fixtures(): iterable
    {
        $fixtures = json_decode(file_get_contents(__DIR__.'/../../Fixtures/Openings/golden.json'), true, flags: JSON_THROW_ON_ERROR);
        foreach ($fixtures as $fixture) {
            yield $fixture['id'] => [$fixture];
        }
    }

    #[DataProvider('fixtures')]
    public function testSharedExpectedStates(array $fixture): void
    {
        $replayer = new PChessMoveReplayer();
        $line = $replayer->replay(array_column($fixture['moves'], 'san'), $fixture['initialFen']);
        self::assertSame($fixture['initial'], ['fen' => $line->initialFen, 'key' => $line->initialKey]);
        foreach ($line->moves as $index => $move) {
            self::assertSame($fixture['moves'][$index], (array) $move);
        }
        foreach ($fixture['illegalSan'] ?? [] as $san) {
            try {
                $replayer->replay([$san], $fixture['initialFen']);
                self::fail('Pinned en-passant must be illegal');
            } catch (\InvalidArgumentException $e) {
                self::assertStringContainsString('ply 1: illegal', $e->getMessage());
            }
        }
    }

    public function testCanonicalIdentityRetainsRightsAndTurnButExcludesClocks(): void
    {
        $replayer = new PChessMoveReplayer();
        $fen = 'r3k2r/8/8/8/8/8/8/R3K2R';
        $original = $replayer->replay([], $fen.' w KQkq - 0 1');
        self::assertSame($original->initialKey, $replayer->replay([], $fen.' w KQkq - 19 10')->initialKey);
        self::assertNotSame($original->initialKey, $replayer->replay([], $fen.' w - - 0 1')->initialKey);
        self::assertNotSame($original->initialKey, $replayer->replay([], $fen.' b KQkq - 0 1')->initialKey);
    }
}
