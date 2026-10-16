<?php

declare(strict_types=1);

namespace App\Service\OpeningImport;

use App\Service\Migrations\CatalogueOperationLock;
use Doctrine\DBAL\Connection;

final readonly class CatalogueStager
{
    public function __construct(private Connection $connection, private CatalogueOperationLock $lock)
    {
    }

    /** @param list<list<int|string>> $rows */
    private function insert(string $table, string $columns, array $rows, bool $shared = false): void
    {
        foreach (array_chunk($rows, 200) as $chunk) {
            $this->lock->assertHeld();
            $this->connection->transactional(function () use ($table, $columns, $chunk, $shared): void {
                $placeholders = implode(',', array_fill(0, count($chunk), '('.implode(',', array_fill(0, count($chunk[0]), '?')).')'));
                // Existing facts retain all their values. Validate edge equality separately below.
                $sql = 'INSERT INTO '.$table.' ('.$columns.') VALUES '.$placeholders.($shared ? ' ON DUPLICATE KEY UPDATE id=id' : '');
                $this->connection->executeStatement($sql, array_merge(...$chunk));
            });
        }
    }

    public function stage(int $batch, ValidatedCatalogue $source): void
    {
        $fileRows = [];
        foreach ($source->files as $file => $metadata) {
            $fileRows[] = [$batch, $file, $metadata['sha256'], $metadata['bytes'], $metadata['rows']];
        }
        $this->insert('opening_import_file', 'batch_id,filename,sha256,byte_count,row_count', $fileRows);
        $this->insert('chess_position', 'position_key', array_map(static fn (string $key): array => [$key], array_keys($source->graph->positions)), true);
        $positions = [];
        foreach (array_chunk(array_keys($source->graph->positions), 200) as $keys) {
            $placeholders = implode(',', array_fill(0, count($keys), '?'));
            foreach ($this->connection->fetchAllAssociative('SELECT id,position_key FROM chess_position WHERE position_key IN ('.$placeholders.')', $keys) as $row) {
                $positions[(string) $row['position_key']] = (int) $row['id'];
            }
        }
        $rows = [];
        foreach ($source->graph->edges as $edge) {
            $rows[] = [$positions[$edge['from']], $positions[$edge['to']], $edge['uci'], $edge['san']];
        }
        $this->insert('chess_move', 'from_position_id,to_position_id,uci,san', $rows, true);
        $edges = [];
        foreach (array_chunk(array_values($positions), 200) as $ids) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            foreach ($this->connection->fetchAllAssociative('SELECT m.id,m.uci,m.san,p.position_key AS from_key,q.position_key AS to_key FROM chess_move m JOIN chess_position p ON p.id=m.from_position_id JOIN chess_position q ON q.id=m.to_position_id WHERE m.from_position_id IN ('.$placeholders.')', $ids) as $row) {
                $key = $row['from_key'].'|'.$row['uci'];
                if (!isset($source->graph->edges[$key])) {
                    continue;
                }
                $expected = $source->graph->edges[$key];
                if ($row['to_key'] !== $expected['to'] || $row['san'] !== $expected['san']) {
                    throw new \DomainException('Stored chess edge conflicts with replayed destination or SAN: '.$row['uci']);
                }
                $edges[$key] = (int) $row['id'];
            }
        }
        if (count($edges) !== count($source->graph->edges)) {
            throw new \DomainException('Staged graph is missing required edges.');
        }
        $entryRows = [];
        foreach ($source->records as $uid => $record) {
            $path = $source->graph->paths[$uid]['steps'];
            if ([] === $path) {
                throw new \DomainException('Source opening has no replayed moves.');
            }
            $last = $source->graph->edges[$path[array_key_last($path)]];
            $entryRows[] = [$batch, $uid, $record->file, $record->row, $record->eco, $record->name, $record->pgn, $positions[$last['to']]];
        }
        $this->insert('opening_entry', 'batch_id,source_uid,source_file,source_row,eco,full_name,original_pgn,terminal_position_id', $entryRows);
        $steps = [];
        foreach ($this->connection->fetchAllAssociative('SELECT id,source_uid FROM opening_entry WHERE batch_id=?', [$batch]) as $entry) {
            foreach ($source->graph->paths[(string) $entry['source_uid']]['steps'] as $ply => $key) {
                $steps[] = [(int) $entry['id'], $ply + 1, $edges[$key]];
                if (count($steps) >= 200) {
                    $this->insert('opening_entry_step', 'opening_entry_id,ply,chess_move_id', $steps);
                    $steps = [];
                }
            }
        }
        $this->insert('opening_entry_step', 'opening_entry_id,ply,chess_move_id', $steps);
        $this->validate($batch, count($source->records), $source->graph->plies);
        $this->lock->assertHeld();
        $this->connection->executeStatement("UPDATE opening_import_batch SET status='ready',validated_at=UTC_TIMESTAMP(6) WHERE id=? AND status='staging'", [$batch]);
    }

    public function validate(int $batch, int $expectedEntries, int $expectedPlies): void
    {
        $files = $this->connection->fetchAllAssociative('SELECT filename,row_count FROM opening_import_file WHERE batch_id=? ORDER BY filename', [$batch]);
        $counts = array_column($files, 'row_count');
        if ([] === $counts || ['a.tsv', 'b.tsv', 'c.tsv', 'd.tsv', 'e.tsv'] !== array_column($files, 'filename') || min($counts) < 1 || array_sum($counts) !== $expectedEntries) {
            throw new \DomainException('Staged source-file counts are incomplete.');
        }
        $badFile = $this->connection->fetchOne('SELECT f.filename FROM opening_import_file f LEFT JOIN opening_entry e ON e.batch_id=f.batch_id AND e.source_file=f.filename WHERE f.batch_id=? GROUP BY f.filename,f.row_count HAVING COUNT(e.id)<>f.row_count LIMIT 1', [$batch]);
        if (false !== $badFile) {
            throw new \DomainException('Staged source-file counts do not match each stored family.');
        }
        $entries = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM opening_entry WHERE batch_id=?', [$batch]);
        $plies = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM opening_entry_step s JOIN opening_entry e ON e.id=s.opening_entry_id WHERE e.batch_id=?', [$batch]);
        if ($entries !== $expectedEntries || $plies !== $expectedPlies) {
            throw new \DomainException('Staged source paths have unexpected counts.');
        }
        $bad = $this->connection->fetchOne('SELECT e.id FROM opening_entry e LEFT JOIN opening_entry_step s ON s.opening_entry_id=e.id WHERE e.batch_id=? GROUP BY e.id HAVING COUNT(s.ply)=0 OR MIN(s.ply)<>1 OR MAX(s.ply)<>COUNT(s.ply) LIMIT 1', [$batch]);
        $discontinuous = $this->connection->fetchOne('SELECT e.id FROM opening_entry e JOIN opening_entry_step s ON s.opening_entry_id=e.id JOIN chess_move m ON m.id=s.chess_move_id JOIN opening_entry_step n ON n.opening_entry_id=e.id AND n.ply=s.ply+1 JOIN chess_move next_move ON next_move.id=n.chess_move_id WHERE e.batch_id=? AND m.to_position_id<>next_move.from_position_id LIMIT 1', [$batch]);
        $wrongTerminal = $this->connection->fetchOne('SELECT e.id FROM opening_entry e JOIN opening_entry_step s ON s.opening_entry_id=e.id JOIN chess_move m ON m.id=s.chess_move_id WHERE e.batch_id=? AND NOT EXISTS (SELECT 1 FROM opening_entry_step n WHERE n.opening_entry_id=e.id AND n.ply>s.ply) AND m.to_position_id<>e.terminal_position_id LIMIT 1', [$batch]);
        $wrongStart = $this->connection->fetchOne('SELECT e.id FROM opening_entry e JOIN opening_entry_step s ON s.opening_entry_id=e.id AND s.ply=1 JOIN chess_move m ON m.id=s.chess_move_id JOIN chess_position p ON p.id=m.from_position_id WHERE e.batch_id=? AND p.position_key<>? LIMIT 1', [$batch, 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -']);
        if (false !== $bad || false !== $discontinuous || false !== $wrongTerminal || false !== $wrongStart) {
            throw new \DomainException('Staged source paths fail continuity, start or terminal validation.');
        }
    }
}
