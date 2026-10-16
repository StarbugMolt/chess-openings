<?php

declare(strict_types=1);

namespace AppMigrations\Support;

use Doctrine\DBAL\Connection;

/** Immutable migration support. Change future contracts in a NEW version, never this class. */
final readonly class LessonGraphV1
{
    public const SOURCE_SHA = 'a6189a30dc273ccb21fc2536a9a2fefd5592a67a';
    private const SOURCES = [
        'italian/1/centre-plan' => '1. e4 e5 2. Nf3 Nc6 3. Bc4 Bc5 4. c3 Nf6',
        'italian/1/quiet-plan' => '1. e4 e5 2. Nf3 Nc6 3. Bc4 Bc5 4. d3 Nf6 5. Nc3',
        'london/1/first-setup' => '1. d4 Nf6 2. Nf3 g6 3. Bf4 Bg7 4. e3 d6',
        'london/2/first-setup' => '1. d4 d5 2. Nf3 e6 3. Bf4 Nf6',
    ];

    public function __construct(private Connection $connection)
    {
    }

    /** @return array<string, list<array{uci: string, san: string, fromKey: string, toKey: string}>> */
    public static function paths(): array
    {
        return require __DIR__.'/Paths20261015.php';
    }

    /** Resolve a frozen prefix of one named path in the pinned bootstrap snapshot, never the cumulative graph.
     * @return list<int>
     */
    public function imported(string $path): array
    {
        $facts = self::paths()[$path] ?? throw new \LogicException('Unknown frozen lesson path.');
        $source = self::SOURCES[$path] ?? throw new \LogicException('No pinned source path.');

        return $this->resolveImported($facts, $source);
    }

    /** @param list<array{uci: string, san: string, fromKey: string, toKey: string}> $facts
     * @return list<int>
     */
    public function resolveImported(array $facts, string $source): array
    {
        if (count($facts) < 2 || count($facts) > 64) {
            throw new \InvalidArgumentException('Invalid frozen lesson length.');
        }
        $entry = $this->connection->fetchOne('SELECT e.id FROM opening_entry e JOIN opening_import_batch b ON b.id=e.batch_id WHERE b.upstream_sha=? AND b.status IN (\'active\',\'retired\') AND BINARY e.original_pgn=? ORDER BY e.id LIMIT 1', [self::SOURCE_SHA, $source]);
        if (false === $entry) {
            throw new \RuntimeException('Prepare the pinned bootstrap named path before publishing this lesson: '.$source);
        }
        $rows = $this->connection->fetchAllAssociative('SELECT m.id,m.uci,m.san,f.position_key AS fromKey,t.position_key AS toKey FROM opening_entry_step s JOIN chess_move m ON m.id=s.chess_move_id JOIN chess_position f ON f.id=m.from_position_id JOIN chess_position t ON t.id=m.to_position_id WHERE s.opening_entry_id=? ORDER BY s.ply LIMIT '.count($facts), [$entry]);
        if (count($rows) !== count($facts)) {
            throw new \RuntimeException('Incomplete pinned source path: '.$source);
        }
        foreach ($rows as $i => $row) {
            foreach ($facts[$i] as $key => $value) {
                if ($row[$key] !== $value) {
                    throw new \RuntimeException('Pinned source facts differ from the frozen lesson: '.$source);
                }
            }
        }

        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
    }

    /** Owner-approved frozen facts, legally checked against both PHP and chess.js in tests.
     * Only newly inserted facts receive authored provenance; source membership is never fabricated.
     *
     * @return list<int>
     */
    public function authored(string $path, string $migration): array
    {
        $facts = self::paths()[$path] ?? throw new \LogicException('Unknown frozen authored path.');
        if (strlen($migration) > 191 || !str_starts_with($migration, 'AppMigrations\\LessonData\\Version')) {
            throw new \InvalidArgumentException('Invalid authored migration identity.');
        }

        return $this->connection->transactional(function () use ($facts, $migration): array {
            $edges = [];
            foreach ($facts as $fact) {
                $from = $this->position($fact['fromKey'], $migration);
                $to = $this->position($fact['toKey'], $migration);
                $edge = $this->connection->fetchAssociative('SELECT id,to_position_id,san FROM chess_move WHERE from_position_id=? AND uci=?', [$from, $fact['uci']]);
                if (false === $edge) {
                    $this->connection->insert('chess_move', ['from_position_id' => $from, 'to_position_id' => $to, 'uci' => $fact['uci'], 'san' => $fact['san']]);
                    $id = (int) $this->connection->lastInsertId();
                    $this->connection->insert('authored_chess_move', ['move_id' => $id, 'authored_in' => $migration, 'created_at' => gmdate('Y-m-d H:i:s')]);
                } else {
                    if ((int) $edge['to_position_id'] !== $to || $edge['san'] !== $fact['san']) {
                        throw new \RuntimeException('Existing graph conflicts with the frozen authored path.');
                    }
                    $id = (int) $edge['id'];
                }
                $edges[] = $id;
            }

            return $edges;
        });
    }

    private function position(string $key, string $migration): int
    {
        $id = $this->connection->fetchOne('SELECT id FROM chess_position WHERE position_key=?', [$key]);
        if (false !== $id) {
            return (int) $id;
        }
        $this->connection->insert('chess_position', ['position_key' => $key]);
        $id = (int) $this->connection->lastInsertId();
        $this->connection->insert('authored_chess_position', ['position_id' => $id, 'authored_in' => $migration, 'created_at' => gmdate('Y-m-d H:i:s')]);

        return $id;
    }
}
