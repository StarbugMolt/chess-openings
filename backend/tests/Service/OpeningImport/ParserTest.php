<?php

declare(strict_types=1);

namespace App\Tests\Service\OpeningImport;

use App\Service\OpeningImport\OpeningGraphBuilder;
use App\Service\OpeningImport\PChessMoveReplayer;
use App\Service\OpeningImport\PgnMovetextParser;
use App\Service\OpeningImport\TsvOpeningParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/opening-parser-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.'/*') as $file) {
            unlink($file);
        }
        rmdir($this->directory);
    }

    public function testQuotedFieldsUnicodeAndDuplicateNamesArePreserved(): void
    {
        $path = $this->directory.'/a.tsv';
        file_put_contents($path, "eco\tname\tpgn\r\nA00\t\"Opening \"\"é\"\"\"\t\"1.Nh3 {a comment} *\"\r\nA00\t\"Opening \"\"é\"\"\"\t1. Nf3\r\n");
        $rows = iterator_to_array((new TsvOpeningParser())->parse($path));
        self::assertCount(2, $rows);
        self::assertSame('Opening "é"', $rows[0]->name);
        self::assertNotSame($rows[0]->fingerprint(), $rows[1]->fingerprint());
    }

    public static function invalidTsv(): iterable
    {
        yield 'header' => ["name\teco\tpgn\n", ':1: expected exactly'];
        yield 'extra header' => ["eco\tname\tpgn\textra\n", ':1: expected exactly'];
        yield 'no records' => ["eco\tname\tpgn\n", 'no opening records'];
        yield 'extra column' => ["eco\tname\tpgn\nA00\tName\t1. e4\textra\n", ':2: expected exactly'];
        yield 'invalid eco' => ["eco\tname\tpgn\nF10\tName\t1. e4\n", ':2: invalid ECO'];
        yield 'empty name' => ["eco\tname\tpgn\nA00\t \t1. e4\n", ':2: invalid opening name'];
        yield 'empty pgn' => ["eco\tname\tpgn\nA00\tName\t\n", ':2: empty or oversized PGN'];
        yield 'invalid utf8' => ["eco\tname\tpgn\nA00\t\xff\t1. e4\n", 'not UTF-8'];
        yield 'open quote' => ["eco\tname\tpgn\nA00\t\"Name\t1. e4\n", ':2: unterminated'];
        yield 'quote junk' => ["eco\tname\tpgn\nA00\t\"Name\"junk\t1. e4\n", ':2: malformed TSV quoting'];
        yield 'unescaped quote' => ["eco\tname\tpgn\nA00\tBad\"Name\t1. e4\n", ':2: malformed TSV quoting'];
        yield 'duplicate' => ["eco\tname\tpgn\nA00\tName\t1. e4\nA00\tName\t1. e4\n", ':3: duplicate source record'];
        yield 'blank record' => ["eco\tname\tpgn\n\n", ':2: expected exactly'];
        yield 'oversized' => [str_repeat('x', TsvOpeningParser::MAX_BYTES + 1), 'size limit'];
    }

    #[DataProvider('invalidTsv')]
    public function testRejectsMalformedTsv(string $content, string $message): void
    {
        $path = $this->directory.'/a.tsv';
        file_put_contents($path, $content);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('a.tsv');
        try {
            iterator_to_array((new TsvOpeningParser())->parse($path));
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString($message, $e->getMessage());
            throw $e;
        }
    }

    public function testMainlineNumbersCommentsAnnotationsAndResults(): void
    {
        $moves = (new PgnMovetextParser())->parse("1.e4! {develop} e5 $1 2.Nf3?! ;line comment\n2...Nc6 3.Bc4 Bc5 4.0-0 *");
        self::assertSame(['e4', 'e5', 'Nf3', 'Nc6', 'Bc4', 'Bc5', 'O-O'], $moves);
    }

    public static function invalidPgn(): iterable
    {
        foreach (['', '1.', '2. e4', '1...e4', '1. e4 1. e5', '1. e4 2. e5', '1. e4 (1. d4)', '[Event "x"] 1.e4', '1.e4 {open', '1.e4 $256', '1.e4 garbage', '1.e4 * e5', '1.e4 1-0 *', '1.e4!!!', '1.e4\n2.', '1.e4\x00'] as $pgn) {
            yield $pgn => [$pgn];
        }
    }

    #[DataProvider('invalidPgn')]
    public function testUnsupportedAndMalformedMovetextIsNotPartiallyAccepted(string $pgn): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ply ');
        (new PgnMovetextParser())->parse($pgn);
    }

    public function testIllegalMoveIncludesFileRowAndPly(): void
    {
        $path = $this->directory.'/a.tsv';
        file_put_contents($path, "eco\tname\tpgn\nA00\tIllegal\t1. e4 e5 2. Bh6\n");
        $builder = new OpeningGraphBuilder(new TsvOpeningParser(), new PgnMovetextParser(), new PChessMoveReplayer());
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('a.tsv:2: ply 3: illegal');
        $builder->build((new TsvOpeningParser())->parse($path));
    }

    public function testMalformedSanDiagnosticDoesNotSplitUnicode(): void
    {
        try {
            (new PgnMovetextParser())->parse('1. '.str_repeat('x', 39).'é');
            self::fail('Malformed SAN was accepted.');
        } catch (\InvalidArgumentException $error) {
            self::assertTrue(mb_check_encoding($error->getMessage(), 'UTF-8'));
            self::assertStringContainsString('malformed SAN token', $error->getMessage());
        }
    }
}
