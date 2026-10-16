<?php

declare(strict_types=1);

namespace App\Tests\Service\Lesson;

use App\Service\OpeningImport\PChessMoveReplayer;
use AppMigrations\LessonData\Version20261015010300;
use AppMigrations\LessonData\Version20261015010400;
use AppMigrations\LessonData\Version20261015010500;
use AppMigrations\LessonData\Version20261015010600;
use AppMigrations\LessonData\Version20261015010700;
use AppMigrations\LessonData\Version20261015010800;
use AppMigrations\LessonData\Version20261015010900;
use AppMigrations\LessonData\Version20261015011000;
use AppMigrations\LessonData\Version20261015011100;
use AppMigrations\LessonData\Version20261015011200;
use AppMigrations\LessonData\Version20261015011300;
use AppMigrations\LessonData\Version20261015011400;
use AppMigrations\LessonData\Version20261015011500;
use AppMigrations\LessonData\Version20261015011600;
use AppMigrations\LessonData\Version20261015011700;
use AppMigrations\LessonData\Version20261015011800;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FrozenLessonPathsTest extends TestCase
{
    /** The published teaching contract; a correction must introduce another revision. */
    private const EXPECTED_PATHS = [
        'italian/1/centre-plan' => [8, 'e4 e5 Nf3 Nc6 Bc4 Bc5 c3 Nf6'],
        'italian/1/quiet-plan' => [8, 'e4 e5 Nf3 Nc6 Bc4 Bc5 d3 Nf6'],
        'london/1/first-setup' => [8, 'd4 Nf6 Nf3 g6 Bf4 Bg7 e3 d6'],
        'london/2/first-setup' => [6, 'd4 d5 Nf3 e6 Bf4 Nf6'],
        'london/3/first-setup' => [15, 'd4 d5 Nf3 Nf6 Bf4 e6 e3 c5 c3 Nc6 Nbd2 Be7 Bd3 O-O O-O'],
        'caro-kann/1/exchange-setup' => [10, 'e4 c6 d4 d5 exd5 cxd5 Bd3 Nc6 c3 Nf6'],
        'caro-kann/1/panov-development' => [18, 'e4 c6 d4 d5 exd5 cxd5 c4 Nc6 Nc3 Nf6 Nf3 e6 cxd5 exd5 Bb5 Bd6 Bg5 O-O'],
        'french/1/advance-centre' => [14, 'e4 e6 d4 d5 e5 c5 c3 Nc6 Nf3 Qb6 Bd3 cxd4 cxd4 Bd7'],
        'queens-gambit/1/declined-development' => [14, 'd4 d5 c4 e6 Nc3 Nf6 Nf3 c5 e3 Nc6 Bd3 Bd6 O-O O-O'],
        'italian/2/pianissimo-main-line' => [17, 'e4 e5 Nf3 Nc6 Bc4 Bc5 c3 Nf6 d3 d6 O-O O-O Re1 a6 Bb3 Ba7 h3'],
        'italian/2/two-knights-knorre' => [23, 'e4 e5 Nf3 Nc6 Bc4 Nf6 Ng5 d5 exd5 Na5 Bb5+ c6 dxc6 bxc6 Be2 h6 Nf3 e4 Ne5 Bd6 d4 Qc7 Bd2'],
        'caro-kann/2/classical-main-line' => [24, 'e4 c6 d4 d5 Nd2 dxe4 Nxe4 Bf5 Ng3 Bg6 h4 h6 Nf3 Nd7 h5 Bh7 Bd3 Bxd3 Qxd3 e6 Bd2 Ngf6 O-O-O Be7'],
        'caro-kann/2/karpov-main-line' => [19, 'e4 c6 d4 d5 Nd2 dxe4 Nxe4 Nd7 Ng5 Ngf6 Bd3 e6 N1f3 Bd6 Qe2 h6 Ne4 Nxe4 Qxe4'],
        'french/2/classical-stahlberg' => [20, 'e4 e6 d4 d5 Nc3 Nf6 Bg5 Be7 e5 Nfd7 Bxe7 Qxe7 f4 O-O Nf3 c5 Qd2 Nc6 O-O-O c4'],
        'french/2/winawer-positional' => [18, 'e4 e6 d4 d5 Nc3 Bb4 e5 c5 a3 Bxc3+ bxc3 Ne7 a4 Nbc6 Nf3 Qa5 Bd2 Bd7'],
        'queens-gambit/2/declined-development' => [19, 'd4 d5 c4 e6 Nc3 Nf6 Nf3 c5 e3 Nc6 Bd3 Bd6 O-O O-O Qe2 Qe7 dxc5 Bxc5 e4'],
        'queens-gambit/2/orthodox-classical' => [28, 'd4 d5 c4 e6 Nc3 Nf6 Bg5 Be7 e3 O-O Nf3 Nbd7 Rc1 c6 Bd3 dxc4 Bxc4 Nd5 Bxe7 Qxe7 O-O Nxc3 Rxc3 e5 dxe5 Nxe5 Nxe5 Qxe5'],
        'queens-gambit/2/accepted-classical' => [26, 'd4 d5 c4 dxc4 Nf3 Nf6 e3 e6 Bxc4 c5 O-O a6 Qe2 b5 Bb3 Nc6 Rd1 c4 Bc2 Nb4 Nc3 Nxc2 Qxc2 Bb7 d5 Qc7'],
        'ruy-lopez/1/chigorin-main-line' => [28, 'e4 e5 Nf3 Nc6 Bb5 a6 Ba4 Nf6 O-O Be7 Re1 b5 Bb3 d6 c3 O-O h3 Na5 Bc2 c5 d4 Qc7 Nbd2 Bd7 Nf1 Rfe8 Ne3 g6'],
        'ruy-lopez/1/breyer-defense' => [24, 'e4 e5 Nf3 Nc6 Bb5 a6 Ba4 Nf6 O-O Be7 Re1 b5 Bb3 d6 c3 O-O h3 Nb8 d4 Nbd7 Nbd2 Bb7 Bc2 c5'],
        'ruy-lopez/1/marshall-attack' => [36, 'e4 e5 Nf3 Nc6 Bb5 a6 Ba4 Nf6 O-O Be7 Re1 b5 Bb3 O-O c3 d5 exd5 Nxd5 Nxe5 Nxe5 Rxe5 c6 d4 Bd6 Re1 Qh4 g3 Qh3 Be3 Bg4 Qd3 Rae8 Nd2 Re6 a4 Qh5'],
        'catalan/1/closed-main-line' => [23, 'd4 Nf6 c4 e6 Nf3 d5 g3 Be7 Bg2 O-O O-O Nbd7 Qc2 c6 Bf4 b6 Nbd2 Bb7 e4 dxe4 Nxe4 Nxe4 Qxe4'],
        'catalan/1/closed-sokolsky' => [20, 'd4 Nf6 c4 e6 g3 d5 Bg2 Be7 Nf3 O-O O-O Nbd7 Qc2 c6 Nbd2 b6 b3 a5 Bb2 Ba6'],
        'sicilian/1/najdorf-main-line' => [18, 'e4 c5 Nf3 d6 d4 cxd4 Nxd4 Nf6 Nc3 a6 Bg5 e6 f4 Be7 Qf3 Qc7 O-O-O Nbd7'],
        'sicilian/1/dragon-yugoslav' => [24, 'e4 c5 Nf3 d6 d4 cxd4 Nxd4 Nf6 Nc3 g6 Be3 Bg7 f3 O-O Qd2 Nc6 Bc4 Bd7 O-O-O Qa5 h4 Rfc8 Bb3 h5'],
        'sicilian/1/sveshnikov' => [20, 'e4 c5 Nf3 Nc6 d4 cxd4 Nxd4 Nf6 Nc3 e5 Ndb5 d6 Bg5 a6 Na3 b5 Bxf6 gxf6 Nd5 f5'],
        'kings-indian/1/classical-main-line' => [27, 'd4 Nf6 c4 g6 Nc3 Bg7 e4 d6 Nf3 O-O Be2 e5 O-O Nc6 d5 Ne7 Ne1 Nd7 Be3 f5 f3 f4 Bf2 g5 Rc1 Ng6 c5'],
        'kings-indian/1/fianchetto-panno' => [26, 'd4 Nf6 c4 g6 Nf3 Bg7 g3 O-O Bg2 d6 O-O Nc6 Nc3 a6 d5 Na5 Nd2 c5 Qc2 Rb8 b3 b5 Bb2 bxc4 bxc4 Bh6'],
        'slav/1/czech-main-line' => [17, 'd4 d5 c4 c6 Nf3 Nf6 Nc3 dxc4 a4 Bf5 e3 e6 Bxc4 Bb4 O-O O-O Qe2'],
        'slav/1/semi-slav-meran' => [29, 'd4 d5 c4 c6 Nc3 Nf6 Nf3 e6 e3 Nbd7 Bd3 dxc4 Bxc4 b5 Bd3 a6 e4 c5 e5 cxd4 Nxb5 Nxe5 Nxe5 axb5 O-O Qd5 Qe2 Ba6 Bg5'],
        'italian/3/giuoco-piano-moller' => [27, 'e4 e5 Nf3 Nc6 Bc4 Bc5 c3 Nf6 d4 exd4 cxd4 Bb4+ Nc3 Nxe4 O-O Bxc3 d5 Bf6 Re1 Ne7 Rxe4 d6 Bg5 Bxg5 Nxg5 O-O Nxh7'],
        'caro-kann/3/panov-development' => [19, 'e4 c6 d4 d5 exd5 cxd5 c4 Nc6 Nc3 Nf6 Nf3 e6 cxd5 exd5 Bb5 Bd6 Bg5 O-O O-O'],
        'french/3/tarrasch-chistyakov' => [20, 'e4 e6 d4 d5 Nd2 c5 exd5 Qxd5 Ngf3 cxd4 Bc4 Qd6 O-O Nf6 Nb3 Nc6 Nbxd4 Nxd4 Nxd4 a6'],
        'slav/2/czech-main-line' => [19, 'd4 d5 c4 c6 Nf3 Nf6 Nc3 dxc4 a4 Bf5 e3 e6 Bxc4 Bb4 O-O O-O Qe2 Ne4 g4'],
    ];

    /** @return iterable<string, array{list<array{uci: string, san: string, fromKey: string, toKey: string}>, int, string}> */
    public static function paths(): iterable
    {
        foreach (self::frozenPaths() as $id => $facts) {
            [$minimumPlies, $expectedSan] = self::EXPECTED_PATHS[$id] ?? throw new \LogicException('Add the new frozen path to its reviewed teaching contract: '.$id);
            yield $id => [$facts, $minimumPlies, $expectedSan];
        }
    }

    public function testEveryPublishedFrozenPathHasARegressionCase(): void
    {
        $expected = array_keys(self::EXPECTED_PATHS);
        $actual = array_keys(self::frozenPaths());
        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual, 'An immutable lesson path was removed or published without regression coverage.');
    }

    /** @param list<array{uci: string, san: string, fromKey: string, toKey: string}> $facts */
    #[DataProvider('paths')]
    public function testFrozenFactsMatchLegalReplay(array $facts, int $minimumPlies, string $expectedSan): void
    {
        self::assertGreaterThanOrEqual($minimumPlies, count($facts), 'The published lesson must retain its useful teaching depth.');
        self::assertLessThanOrEqual(64, count($facts), 'A lesson must remain finite and within the trainer depth limit.');
        self::assertSame(explode(' ', $expectedSan), array_column($facts, 'san'), 'A historical lesson path was changed rather than publishing a new revision.');

        $line = (new PChessMoveReplayer())->replay(array_column($facts, 'san'));
        $previousKey = $line->initialKey;
        self::assertCount(count($facts), $line->moves);
        foreach ($line->moves as $index => $move) {
            $context = 'Frozen ply '.($index + 1).' ('.$move->san.').';
            self::assertSame($previousKey, $facts[$index]['fromKey'], 'Discontinuous source position. '.$context);
            self::assertSame($move->uci, $facts[$index]['uci'], 'Unexpected UCI. '.$context);
            self::assertSame($move->san, $facts[$index]['san'], 'Unexpected SAN. '.$context);
            self::assertSame($move->key, $facts[$index]['toKey'], 'Unexpected canonical destination. '.$context);
            self::assertCount(4, explode(' ', $facts[$index]['fromKey']), 'Source keys exclude the move clocks. '.$context);
            self::assertCount(4, explode(' ', $facts[$index]['toKey']), 'Destination keys exclude the move clocks. '.$context);
            $previousKey = $move->key;
        }
    }

    public function testFullLondonRetainsEightWhiteMovesAndBothCastles(): void
    {
        $facts = self::frozenPaths()['london/3/first-setup'];
        self::assertCount(15, $facts);
        self::assertCount(8, array_filter($facts, static fn (array $fact): bool => 'w' === explode(' ', $fact['fromKey'])[1]));
        self::assertCount(7, array_filter($facts, static fn (array $fact): bool => 'b' === explode(' ', $fact['fromKey'])[1]));
        self::assertSame(['e8g8', 'e1g1'], array_column(array_slice($facts, -2), 'uci'));
        self::assertSame(['O-O', 'O-O'], array_column(array_slice($facts, -2), 'san'));

        $lastPosition = explode(' ', $facts[14]['toKey']);
        $ranks = explode('/', $lastPosition[0]);
        self::assertSame('r1bq1rk1', $ranks[0], 'Black must finish with its king on g8 and castled rook on f8.');
        self::assertSame('R2Q1RK1', $ranks[7], 'White must finish with its king on g1 and castled rook on f1.');
        self::assertSame('-', $lastPosition[2], 'Both kings have castled and lost their castling rights.');
    }

    /** @return array<string, list<array{uci: string, san: string, fromKey: string, toKey: string}>> */
    private static function frozenPaths(): array
    {
        $migrationRoot = dirname(__DIR__, 3).'/migrations';
        require_once $migrationRoot.'/LessonData/Version20261015010300.php';
        require_once $migrationRoot.'/LessonData/Version20261015010400.php';
        require_once $migrationRoot.'/LessonData/Version20261015010500.php';
        require_once $migrationRoot.'/LessonData/Version20261015010600.php';
        require_once $migrationRoot.'/LessonData/Version20261015010700.php';
        require_once $migrationRoot.'/LessonData/Version20261015010800.php';
        require_once $migrationRoot.'/LessonData/Version20261015010900.php';
        require_once $migrationRoot.'/LessonData/Version20261015011000.php';
        require_once $migrationRoot.'/LessonData/Version20261015011100.php';
        require_once $migrationRoot.'/LessonData/Version20261015011200.php';
        require_once $migrationRoot.'/LessonData/Version20261015011300.php';
        require_once $migrationRoot.'/LessonData/Version20261015011400.php';
        require_once $migrationRoot.'/LessonData/Version20261015011500.php';
        require_once $migrationRoot.'/LessonData/Version20261015011600.php';
        require_once $migrationRoot.'/LessonData/Version20261015011700.php';
        require_once $migrationRoot.'/LessonData/Version20261015011800.php';

        /** @var array<string, list<array{uci: string, san: string, fromKey: string, toKey: string}>> $paths */
        $paths = require $migrationRoot.'/Support/Paths20261015.php';
        $constants = [
            'caro-kann/1/exchange-setup' => [Version20261015010300::class, 'EXCHANGE_FACTS'],
            'caro-kann/1/panov-development' => [Version20261015010300::class, 'PANOV_FACTS'],
            'french/1/advance-centre' => [Version20261015010400::class, 'FACTS'],
            'queens-gambit/1/declined-development' => [Version20261015010500::class, 'FACTS'],
            'italian/2/pianissimo-main-line' => [Version20261015010600::class, 'PIANISSIMO_FACTS'],
            'italian/2/two-knights-knorre' => [Version20261015010600::class, 'KNORRE_FACTS'],
            'caro-kann/2/classical-main-line' => [Version20261015010800::class, 'CLASSICAL_FACTS'],
            'caro-kann/2/karpov-main-line' => [Version20261015010800::class, 'KARPOV_FACTS'],
            'french/2/classical-stahlberg' => [Version20261015010900::class, 'STAHLBERG_FACTS'],
            'french/2/winawer-positional' => [Version20261015010900::class, 'WINAWER_FACTS'],
            'queens-gambit/2/declined-development' => [Version20261015010700::class, 'LEVENFISH_FACTS'],
            'queens-gambit/2/orthodox-classical' => [Version20261015010700::class, 'ORTHODOX_FACTS'],
            'queens-gambit/2/accepted-classical' => [Version20261015010700::class, 'ACCEPTED_FACTS'],
            'ruy-lopez/1/chigorin-main-line' => [Version20261015011000::class, 'CHIGORIN_FACTS'],
            'ruy-lopez/1/breyer-defense' => [Version20261015011000::class, 'BREYER_FACTS'],
            'ruy-lopez/1/marshall-attack' => [Version20261015011000::class, 'MARSHALL_FACTS'],
            'catalan/1/closed-main-line' => [Version20261015011100::class, 'CLOSED_FACTS'],
            'catalan/1/closed-sokolsky' => [Version20261015011100::class, 'SOKOLSKY_FACTS'],
            'sicilian/1/najdorf-main-line' => [Version20261015011200::class, 'NAJDORF_FACTS'],
            'sicilian/1/dragon-yugoslav' => [Version20261015011200::class, 'DRAGON_FACTS'],
            'sicilian/1/sveshnikov' => [Version20261015011200::class, 'SVESHNIKOV_FACTS'],
            'kings-indian/1/classical-main-line' => [Version20261015011300::class, 'CLASSICAL_FACTS'],
            'kings-indian/1/fianchetto-panno' => [Version20261015011300::class, 'PANNO_FACTS'],
            'slav/1/czech-main-line' => [Version20261015011400::class, 'CZECH_FACTS'],
            'slav/1/semi-slav-meran' => [Version20261015011400::class, 'MERAN_FACTS'],
            'italian/3/giuoco-piano-moller' => [Version20261015011500::class, 'MOLLER_FACTS'],
            'caro-kann/3/panov-development' => [Version20261015011600::class, 'PANOV_FACTS'],
            'french/3/tarrasch-chistyakov' => [Version20261015011700::class, 'TARRASCH_FACTS'],
            'slav/2/czech-main-line' => [Version20261015011800::class, 'CZECH_FACTS'],
        ];
        foreach ($constants as $id => [$class, $constant]) {
            /** @var list<array{uci: string, san: string, fromKey: string, toKey: string}> $facts */
            $facts = (new \ReflectionClass($class))->getConstant($constant);
            if (!is_array($facts)) {
                throw new \LogicException('The frozen lesson constant is missing: '.$id);
            }
            $paths[$id] = $facts;
        }

        return $paths;
    }
}
