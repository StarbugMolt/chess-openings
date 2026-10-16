<?php

declare(strict_types=1);

namespace App\Service\OpeningImport;

use App\Service\Migrations\CatalogueOperationLock;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

final readonly class CatalogueSynchronizer
{
    public const IMPORTER_VERSION = '1';
    public const BOOTSTRAP_SHA = 'a6189a30dc273ccb21fc2536a9a2fefd5592a67a';

    public function __construct(private Connection $connection, private CatalogueOperationLock $lock, private OpeningSourceLoader $loader, private CatalogueStager $stager, private CataloguePromoter $promoter, private CatalogueMaintenance $maintenance, private LoggerInterface $logger)
    {
    }

    /** @return array<string, mixed> */
    public function sync(?string $sha = null, ?string $directory = null, bool $dryRun = false, bool $force = false): array
    {
        $start = hrtime(true);
        try {
            // Downloads and legal replay can outlast a host's idle-connection timeout.
            // Read current state only after validation, while owning the mutation lock.
            $source = $this->loader->load($sha, $directory);
            $checksum = $source->graph->checksum();
        } catch (\Throwable $error) {
            try {
                $this->lock->run(fn () => $this->recordFailure($error, null, $dryRun));
            } catch (\Throwable) {
                // A competing operation owns its status; preserve the original source failure.
                $this->log('error', 'Opening source validation failed.', ['errorType' => $error::class, 'errorMessage' => $this->diagnostic($error)]);
            }
            throw $error;
        }

        return $this->lock->run(function () use ($source, $checksum, $directory, $dryRun, $force, $start): array {
            $batch = null;
            try {
                $this->lock->assertHeld();
                $state = $this->connection->fetchAssociative('SELECT s.active_batch_id,s.catalogue_revision,s.automatic_updates_held_at,b.content_checksum,b.importer_version FROM opening_catalogue_state s LEFT JOIN opening_import_batch b ON b.id=s.active_batch_id WHERE s.id=1');
                if (false === $state) {
                    throw new \RuntimeException('Apply schema migrations before synchronising openings.');
                }
                if (!$dryRun) {
                    $this->connection->executeStatement("UPDATE opening_catalogue_state SET last_checked_at=UTC_TIMESTAMP(6),last_check_status='checking',last_error_summary=NULL WHERE id=1");
                }
                $active = null === $state['active_batch_id'] ? null : (int) $state['active_batch_id'];
                $diff = $this->diff($source, $active);
                $unchanged = $checksum === $state['content_checksum'] && self::IMPORTER_VERSION === $state['importer_version'];
                $held = null !== $state['automatic_updates_held_at'];
                $heldUpdate = $held && !$unchanged && !$force;
                if ($held && !$unchanged) {
                    $diff['safeguards'][] = 'automatic updates held after rollback; explicit --force required';
                }
                $summary = [
                    'result' => $heldUpdate ? 'held' : ($unchanged ? 'unchanged' : ($dryRun ? 'dry-run' : 'updated')),
                    'dryRun' => $dryRun, 'sourceSha' => $source->sha, 'contentChecksum' => $checksum,
                    'sourceMode' => null === $directory ? 'upstream' : 'local-directory',
                    'entries' => count($source->records), 'plies' => $source->graph->plies,
                    'positions' => count($source->graph->positions), 'edges' => count($source->graph->edges),
                    'diff' => $diff, 'forceApplied' => $force && ([] !== $diff['safeguards'] || $held),
                    'automaticUpdatesHeld' => $held,
                    'batchId' => $active, 'catalogueRevision' => (int) $state['catalogue_revision'],
                ];
                if ([] !== $diff['safeguards'] && !$force && !$unchanged && !$heldUpdate) {
                    throw new \DomainException('Catalogue safety thresholds exceeded: '.implode('; ', $diff['safeguards']).'. Review --dry-run --force before an explicit --force import.');
                }
                if ($heldUpdate && !$dryRun) {
                    $this->lock->assertHeld();
                    $this->connection->executeStatement("UPDATE opening_catalogue_state SET last_checked_at=UTC_TIMESTAMP(6),last_check_status='held',last_error_summary=NULL WHERE id=1");
                } elseif ($unchanged && !$dryRun) {
                    $this->lock->assertHeld();
                    $this->connection->executeStatement("UPDATE opening_catalogue_state SET last_checked_at=UTC_TIMESTAMP(6),last_check_status='unchanged',last_error_summary=NULL,automatic_updates_held_at=IF(?,NULL,automatic_updates_held_at) WHERE id=1", [$force ? 1 : 0]);
                    $summary['automaticUpdatesHeld'] = $held && !$force;
                } elseif (!$dryRun) {
                    $this->lock->assertHeld();
                    $this->connection->executeStatement("INSERT INTO opening_import_batch (upstream_sha,content_checksum,importer_version,status,created_at) VALUES (?,?,?,'staging',UTC_TIMESTAMP(6))", [$source->sha, $checksum, self::IMPORTER_VERSION]);
                    $batch = (int) $this->connection->lastInsertId();
                    $this->stager->stage($batch, $source);
                    $summary['catalogueRevision'] = $this->promoter->activate($batch, $active);
                    $summary['batchId'] = $batch;
                    $summary['automaticUpdatesHeld'] = false;
                }
                if (!$dryRun) {
                    // Cleanup failure must not report a committed activation as a failed import.
                    try {
                        $this->maintenance->cleanup();
                    } catch (\Throwable $error) {
                        $this->log('warning', 'Opening snapshot cleanup deferred.', ['errorType' => $error::class]);
                    }
                }
                $summary['elapsedSeconds'] = round((hrtime(true) - $start) / 1e9, 3);
                $summary['peakMemoryBytes'] = memory_get_peak_usage(true);
                if (!$this->log($heldUpdate ? 'notice' : 'info', $heldUpdate ? 'Opening catalogue activation held after rollback; review before an explicit --force import.' : 'Opening catalogue check completed.', $summary)) {
                    $summary['logWarning'] = 'Private structured log could not be written; check the log directory.';
                }

                return $summary;
            } catch (\Throwable $error) {
                $this->recordFailure($error, $batch, $dryRun);
                throw $error;
            }
        });
    }

    private function diagnostic(\Throwable $error): string
    {
        $message = mb_scrub($error->getMessage(), 'UTF-8');
        $message = preg_replace('~([a-z][a-z0-9+.-]*://)[^\s/@]+:[^\s/@]+@~i', '$1[redacted]@', $message) ?? '';
        $message = preg_replace('~(authorization\s*[=:]\s*)(?:Bearer|Basic)\s+[^\s&;,]+~i', '$1[redacted]', $message) ?? '';
        $message = preg_replace('~((?:password|passwd|secret|token|authorization|api[_-]?key)\s*[=:]\s*)[^\s&;,]+~i', '$1[redacted]', $message) ?? '';
        $message = preg_replace('/[\x00-\x1F\x7F]/', ' ', $message) ?? '';

        return mb_substr($message, 0, 512, 'UTF-8');
    }

    private function recordFailure(\Throwable $error, ?int $batch, bool $dryRun): void
    {
        $message = $this->diagnostic($error);
        if (!$dryRun) {
            try {
                $this->lock->assertHeld();
                if (null !== $batch) {
                    $this->connection->executeStatement("UPDATE opening_import_batch SET status='failed',finished_at=UTC_TIMESTAMP(6),error_summary=? WHERE id=? AND status IN ('staging','ready')", [$message, $batch]);
                }
                $this->connection->executeStatement("UPDATE opening_catalogue_state SET last_checked_at=UTC_TIMESTAMP(6),last_check_status='failed',last_error_summary=? WHERE id=1", [$message]);
                // Repeated bad imports must not prevent bounded cleanup of old failed snapshots.
                $this->maintenance->cleanup();
            } catch (\Throwable) {
                // Never write operational status after losing the lock or mask the original error.
            }
        }
        $this->log('error', 'Opening catalogue check failed.', ['batchId' => $batch, 'errorType' => $error::class, 'errorMessage' => $message, 'dryRun' => $dryRun]);
    }

    /** @param array<string, mixed> $context */
    private function log(string $level, string $message, array $context): bool
    {
        try {
            $this->logger->log($level, $message, $context);

            return true;
        } catch (\Throwable) {
            // A filesystem/log transport failure cannot undo a committed snapshot or mask its cause.
            return false;
        }
    }

    /** @return array{added: int, removed: int, renameCandidates: list<array{from: string, to: string}>, safeguards: list<string>} */
    private function diff(ValidatedCatalogue $source, ?int $active): array
    {
        $old = null === $active ? [] : $this->connection->fetchAllAssociative('SELECT source_uid,eco,full_name,original_pgn FROM opening_entry WHERE batch_id=?', [$active]);
        $oldUids = array_column($old, 'source_uid');
        $newUids = array_keys($source->records);
        $removed = array_values(array_diff($oldUids, $newUids));
        $removedSet = array_fill_keys($removed, true);
        $added = array_values(array_diff($newUids, $oldUids));
        $safeguards = [];
        if ([] !== $old && count($removed) / count($old) > 0.05) {
            $safeguards[] = sprintf('%d/%d source identities removed (>5%%)', count($removed), count($old));
        }
        if (null !== $active) {
            foreach ($this->connection->fetchAllAssociative('SELECT filename,row_count FROM opening_import_file WHERE batch_id=?', [$active]) as $file) {
                if ($source->files[(string) $file['filename']]['rows'] < (int) $file['row_count'] * 0.9) {
                    $safeguards[] = $file['filename'].' entry count fell by more than 10%';
                }
            }
        }
        $newNames = [];
        foreach ($added as $uid) {
            $record = $source->records[$uid];
            $newNames[hash('sha256', $record->eco.'|'.$record->pgn)][] = $record->name;
        }
        $renames = [];
        foreach ($old as $entry) {
            $names = $newNames[hash('sha256', $entry['eco'].'|'.$entry['original_pgn'])] ?? [];
            if (isset($removedSet[$entry['source_uid']]) && 1 === count($names) && count($renames) < 10) {
                $renames[] = ['from' => (string) $entry['full_name'], 'to' => $names[0]];
            }
        }

        return ['added' => count($added), 'removed' => count($removed), 'renameCandidates' => $renames, 'safeguards' => $safeguards];
    }
}
