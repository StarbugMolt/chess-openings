<?php

declare(strict_types=1);

namespace App\Service\Privacy;

/** Only known application files; never recurse, follow child links or print private names. */
final class RetentionFiles
{
    /** @return array{eligible: int, removed: int, skipped: int} */
    public function run(string $directory, string $category, bool $apply, int $limit, int $now): array
    {
        $age = match ($category) {
            'sessions' => PrivacyConfiguration::SESSION_SECONDS,
            'logs' => PrivacyConfiguration::LOG_DAYS * 86400,
            'backups' => PrivacyConfiguration::BACKUP_DAYS * 86400,
            default => throw new \InvalidArgumentException('Unknown retention category.'),
        };
        if ($limit < 1 || $limit > 10000) {
            throw new \InvalidArgumentException('Cleanup limit must be 1–10000 per category.');
        }
        $result = ['eligible' => 0, 'removed' => 0, 'skipped' => 0];
        if (!file_exists($directory)) {
            return $result;
        }
        if (!is_dir($directory) || !is_readable($directory)) {
            throw new \RuntimeException('Retention requires a readable private directory.');
        }
        $candidates = [];
        foreach (new \DirectoryIterator($directory) as $entry) {
            if ($entry->isDot() || $entry->isLink() || !$entry->isFile() || !$this->known($entry->getFilename(), $category)) {
                continue;
            }
            if ($entry->getMTime() <= $now - $age) {
                $candidates[$entry->getPathname()] = $entry->getMTime();
            }
        }
        asort($candidates, SORT_NUMERIC);
        $result['eligible'] = count($candidates);
        foreach (array_slice(array_keys($candidates), 0, $limit) as $path) {
            // PHP sessions and configured application logs use the same advisory lock.
            $file = @fopen($path, 'rb');
            if (false === $file) {
                ++$result['skipped'];
                continue;
            }
            try {
                if (!flock($file, LOCK_EX | LOCK_NB)) {
                    ++$result['skipped'];
                    continue;
                }
                clearstatcache(true, $path);
                $opened = fstat($file);
                $current = @lstat($path);
                if (false === $opened || false === $current || is_link($path) || $opened['ino'] !== $current['ino'] || $opened['dev'] !== $current['dev'] || $opened['mtime'] > $now - $age) {
                    ++$result['skipped'];
                    continue;
                }
                if ($apply) {
                    if (!@unlink($path)) {
                        throw new \RuntimeException('An expired application file could not be removed.');
                    }
                    ++$result['removed'];
                }
            } finally {
                fclose($file);
            }
        }

        return $result;
    }

    private function known(string $name, string $category): bool
    {
        if ('sessions' === $category) {
            return 1 === preg_match('/\Asess_[A-Za-z0-9,-]{1,128}\z/', $name);
        }
        if ('logs' === $category) {
            if (1 !== preg_match('/\A(?:prod|openings)-(\d{4}-\d{2}-\d{2})\.log\z/', $name, $match)) {
                return false;
            }

            return false !== ($date = \DateTimeImmutable::createFromFormat('!Y-m-d', $match[1])) && $date->format('Y-m-d') === $match[1];
        }
        if (1 !== preg_match('/\A(\d{8})-(\d{6})-\d{6}-[a-f0-9]{8}\.sql\z/', $name, $match)) {
            return false;
        }

        return false !== ($date = \DateTimeImmutable::createFromFormat('!YmdHis', $match[1].$match[2])) && $match[1].$match[2] === $date->format('YmdHis');
    }
}
