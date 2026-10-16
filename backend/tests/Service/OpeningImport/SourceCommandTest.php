<?php

declare(strict_types=1);

namespace App\Tests\Service\OpeningImport;

use App\Command\ValidateOpeningSourceCommand;
use App\Service\OpeningImport\OpeningGraphBuilder;
use App\Service\OpeningImport\OpeningSourceFetcher;
use App\Service\OpeningImport\PChessMoveReplayer;
use App\Service\OpeningImport\PgnMovetextParser;
use App\Service\OpeningImport\TsvOpeningParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class SourceCommandTest extends TestCase
{
    private const SHA = 'a6189a30dc273ccb21fc2536a9a2fefd5592a67a';

    private function command(MockHttpClient $client): CommandTester
    {
        return new CommandTester(new ValidateOpeningSourceCommand(
            new OpeningGraphBuilder(new TsvOpeningParser(), new PgnMovetextParser(), new PChessMoveReplayer()),
            new OpeningSourceFetcher($client),
        ));
    }

    public function testLocalCommandNeedsNoNetworkOrDatabaseAndReportsDeterministicGraph(): void
    {
        $client = new MockHttpClient(static fn () => throw new \LogicException('Local validation must not make HTTP requests'));
        $command = $this->command($client);
        self::assertSame(0, $command->execute(['--source-directory' => __DIR__.'/../../Fixtures/Openings/source']));
        $first = json_decode($command->getDisplay(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(10, $first['entries']);
        self::assertSame(93, $first['plies']);
        self::assertSame(0, $first['databaseWrites']);
        self::assertCount(5, $first['files']);
        self::assertSame(0, $command->execute(['--source-directory' => __DIR__.'/../../Fixtures/Openings/source']));
        $second = json_decode($command->getDisplay(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame($first['graphChecksum'], $second['graphChecksum']);
    }

    public function testAllFiveDownloadsUseOneImmutableShaAndPrivateTemporaryFilesAreRemoved(): void
    {
        $urls = [];
        $client = new MockHttpClient(static function (string $method, string $url, array $options) use (&$urls): MockResponse {
            self::assertSame('GET', $method);
            self::assertSame(0, $options['max_redirects']);
            self::assertSame(60.0, (float) $options['max_duration']);
            $urls[] = $url;

            return new MockResponse(file_get_contents(__DIR__.'/../../Fixtures/Openings/source/'.basename($url)));
        });
        $before = glob(sys_get_temp_dir().'/opening-spike-*');
        $command = $this->command($client);
        self::assertSame(0, $command->execute(['--source-sha' => self::SHA]));
        self::assertSame(array_map(static fn (string $letter): string => 'https://raw.githubusercontent.com/lichess-org/chess-openings/'.self::SHA.'/'.$letter.'.tsv', range('a', 'e')), $urls);
        self::assertSame($before, glob(sys_get_temp_dir().'/opening-spike-*'));
    }

    public static function failures(): iterable
    {
        yield 'bad status' => [new MockResponse('', ['http_code' => 404]), 'upstream HTTP 404'];
        yield 'oversized' => [new MockResponse(str_repeat('x', TsvOpeningParser::MAX_BYTES + 1)), 'size limit'];
        yield 'timeout' => [new MockResponse((static function (): \Generator { yield ''; })()), 'Idle timeout'];
        yield 'malformed source' => [new MockResponse("invalid\theader\n"), 'expected exactly'];
    }

    #[DataProvider('failures')]
    public function testFailedDownloadOrReplayCleansPrivateFilesAndReturnsNonzero(MockResponse $response, string $message): void
    {
        $before = glob(sys_get_temp_dir().'/opening-spike-*');
        $command = $this->command(new MockHttpClient(static fn (): MockResponse => $response));
        self::assertSame(1, $command->execute(['--source-sha' => self::SHA]));
        self::assertStringContainsString($message, $command->getDisplay());
        self::assertSame($before, glob(sys_get_temp_dir().'/opening-spike-*'));
    }

    public function testMutableRefAndMissingOrConflictingInputsAreRejected(): void
    {
        $client = new MockHttpClient(static fn () => throw new \LogicException('Invalid options must not contact upstream'));
        $command = $this->command($client);
        self::assertSame(2, $command->execute([]));
        self::assertSame(2, $command->execute(['--source-sha' => self::SHA, '--source-directory' => '/unused']));
        self::assertSame(1, $command->execute(['--source-sha' => 'master']));
        self::assertStringContainsString('40 lowercase hexadecimal', $command->getDisplay());
    }

    public function testMissingSourceDoesNotProduceSuccessReport(): void
    {
        $command = $this->command(new MockHttpClient());
        self::assertSame(1, $command->execute(['--source-directory' => __DIR__.'/does-not-exist']));
        self::assertStringContainsString('a.tsv: missing', $command->getDisplay());
        self::assertStringNotContainsString('graphChecksum', $command->getDisplay());
    }
}
