<?php

declare(strict_types=1);

namespace App\Tests\Service\OpeningImport;

use App\Service\OpeningImport\CatalogueSynchronizer;
use App\Service\OpeningImport\OpeningGraphBuilder;
use App\Service\OpeningImport\OpeningSourceFetcher;
use App\Service\OpeningImport\OpeningSourceLoader;
use App\Service\OpeningImport\PChessMoveReplayer;
use App\Service\OpeningImport\PgnMovetextParser;
use App\Service\OpeningImport\TsvOpeningParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class SourceLoaderTest extends TestCase
{
    private function loader(MockHttpClient $http): OpeningSourceLoader
    {
        return new OpeningSourceLoader(new OpeningSourceFetcher($http), new OpeningGraphBuilder(new TsvOpeningParser(), new PgnMovetextParser(), new PChessMoveReplayer()));
    }

    public function testScheduledDefaultResolvesOnceThenDownloadsAllFilesFromThatSha(): void
    {
        $urls = [];
        $client = new MockHttpClient(static function (string $method, string $url) use (&$urls): MockResponse {
            $urls[] = $url;
            self::assertSame('GET', $method);
            if (str_contains($url, 'api.github.com')) {
                return new MockResponse(json_encode(['sha' => CatalogueSynchronizer::BOOTSTRAP_SHA], JSON_THROW_ON_ERROR));
            }
            self::assertStringContainsString('/'.CatalogueSynchronizer::BOOTSTRAP_SHA.'/', $url);

            return new MockResponse(file_get_contents(__DIR__.'/../../Fixtures/Openings/source/'.basename($url)));
        });
        $before = glob(sys_get_temp_dir().'/opening-sync-*');
        $source = $this->loader($client)->load();
        self::assertCount(6, $urls);
        self::assertSame(CatalogueSynchronizer::BOOTSTRAP_SHA, $source->sha);
        self::assertCount(10, $source->records);
        self::assertSame(93, $source->graph->plies);
        self::assertSame(2, $source->files['a.tsv']['rows']);
        self::assertSame($before, glob(sys_get_temp_dir().'/opening-sync-*'));
    }

    public function testOfflineCopyNeedsNoHttpAndPreservesRecordedChecksums(): void
    {
        $client = new MockHttpClient(static fn () => throw new \LogicException('Offline mode made an HTTP request'));
        $source = $this->loader($client)->load(CatalogueSynchronizer::BOOTSTRAP_SHA, __DIR__.'/../../Fixtures/Openings/source');
        $manifest = json_decode(file_get_contents(__DIR__.'/../../Fixtures/Openings/provenance.json'), true, flags: JSON_THROW_ON_ERROR);
        foreach ($source->files as $file => $data) {
            self::assertSame($manifest['files'][$file]['fixtureSha256'], $data['sha256']);
        }
    }

    public static function invalidResolution(): iterable
    {
        yield 'mutable ref returned' => ['{"sha":"master"}', 200, 'no immutable SHA'];
        yield 'missing sha' => ['{}', 200, 'no immutable SHA'];
        yield 'bad status' => ['', 503, 'HTTP 503'];
        yield 'oversized metadata' => [str_repeat('x', 262_145), 200, 'size limit'];
    }

    #[DataProvider('invalidResolution')]
    public function testInvalidResolutionDoesNotProceedToDownloads(string $body, int $status, string $message): void
    {
        $client = new MockHttpClient(static fn () => new MockResponse($body, ['http_code' => $status]));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($message);
        $this->loader($client)->load();
    }

    public function testInterruptedDownloadCleansAllPrivateFiles(): void
    {
        $before = glob(sys_get_temp_dir().'/opening-sync-*');
        $client = new MockHttpClient(static fn (string $method, string $url) => new MockResponse(str_ends_with($url, 'a.tsv') ? "eco\tname\tpgn\nA00\tName\t1. e4\n" : '', ['http_code' => str_ends_with($url, 'a.tsv') ? 200 : 503]));
        try {
            $this->loader($client)->load(CatalogueSynchronizer::BOOTSTRAP_SHA);
            self::fail('Interrupted download should fail.');
        } catch (\RuntimeException $error) {
            self::assertStringContainsString('b.tsv: upstream HTTP 503', $error->getMessage());
        }
        self::assertSame($before, glob(sys_get_temp_dir().'/opening-sync-*'));
    }

    public function testOfflineSourceCannotInventProvenance(): void
    {
        $client = new MockHttpClient(static fn () => throw new \LogicException('Invalid options made an HTTP request'));
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('requires --source-ref');
        $this->loader($client)->load(null, __DIR__.'/../../Fixtures/Openings/source');
    }
}
