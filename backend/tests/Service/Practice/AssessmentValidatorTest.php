<?php

declare(strict_types=1);

namespace App\Tests\Service\Practice;

use App\Service\Practice\AssessmentValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class AssessmentValidatorTest extends TestCase
{
    private static function steps(): array
    {
        return [['fromKey' => 'position w KQkq -'], ['fromKey' => 'position b KQkq -'], ['fromKey' => 'position w KQkq -'], ['fromKey' => 'position b KQkq -']];
    }

    private static function valid(): array
    {
        return ['version' => 1, 'usedHint' => false, 'usedRetry' => false, 'usedUndo' => false, 'decisions' => [['ply' => 0, 'attempts' => 1, 'hinted' => false], ['ply' => 2, 'attempts' => 1, 'hinted' => false]]];
    }

    public function testCanonicalOrderMakesEquivalentSummariesIdentical(): void
    {
        $valid = self::valid();
        $reordered = ['decisions' => [['hinted' => false, 'attempts' => 1, 'ply' => 2], ['attempts' => 1, 'ply' => 0, 'hinted' => false]], 'usedUndo' => false, 'usedRetry' => false, 'usedHint' => false, 'version' => 1];
        $validator = new AssessmentValidator();
        self::assertSame(json_encode($valid, JSON_THROW_ON_ERROR), $validator->normalize($reordered, self::steps(), 'w', 0));
    }

    public function testBlackDecisionsAreBoundToTheirOwnFinalPlies(): void
    {
        $assessment = self::valid();
        $assessment['decisions'][0]['ply'] = 1;
        $assessment['decisions'][1]['ply'] = 3;
        self::assertSame($assessment, (new AssessmentValidator())->validate($assessment, self::steps(), 'b', 0));
    }

    public function testWholeAttemptFlagsCanReflectAbandonedBranchesWithoutFinalPositionCredit(): void
    {
        $assessment = [...self::valid(), 'usedHint' => true, 'usedRetry' => true, 'usedUndo' => true];
        self::assertSame($assessment, (new AssessmentValidator())->validate($assessment, self::steps(), 'w', 1));
    }

    public function testAcceptedReplayAfterUndoDoesNotRequireAnOutsideScopeRetry(): void
    {
        $assessment = [...self::valid(), 'usedUndo' => true];
        $assessment['decisions'][0]['attempts'] = 2;
        self::assertSame($assessment, (new AssessmentValidator())->validate($assessment, self::steps(), 'w', 0));
    }

    public function testMaximumFinalPlayerDecisionAndRetryBounds(): void
    {
        $steps = [];
        $assessment = [...self::valid(), 'usedHint' => true, 'usedRetry' => true, 'usedUndo' => true, 'decisions' => []];
        foreach (range(0, 63) as $ply) {
            $steps[] = ['fromKey' => 'position '.(0 === $ply % 2 ? 'w' : 'b').' KQkq -'];
            if (0 === $ply % 2) {
                $assessment['decisions'][] = ['ply' => $ply, 'attempts' => 64, 'hinted' => true];
            }
        }
        $normalized = (new AssessmentValidator())->normalize($assessment, $steps, 'w', 64);
        self::assertLessThan(4096, strlen($normalized));
        self::assertCount(32, json_decode($normalized, true, flags: JSON_THROW_ON_ERROR)['decisions']);
    }

    #[DataProvider('invalidAssessments')]
    public function testInvalidOrContradictorySummaryIsRejected(mixed $assessment, int $hintCount = 0): void
    {
        $this->expectException(BadRequestHttpException::class);
        (new AssessmentValidator())->normalize($assessment, self::steps(), 'w', $hintCount);
    }

    public static function invalidAssessments(): iterable
    {
        foreach ([null, false, [], 'summary', (object) self::valid()] as $index => $value) {
            yield 'root '.$index => [$value];
        }
        foreach (array_keys(self::valid()) as $key) {
            $value = self::valid();
            unset($value[$key]);
            yield 'missing '.$key => [$value];
        }
        foreach ([2, '1', 1.0, true] as $index => $version) {
            yield 'version '.$index => [[...self::valid(), 'version' => $version]];
        }
        yield 'unexpected field' => [[...self::valid(), 'moves' => []]];
        yield 'typed flag' => [[...self::valid(), 'usedUndo' => 1]];
        yield 'hint flag without count' => [[...self::valid(), 'usedHint' => true]];
        yield 'unattributed hint without undo' => [[...self::valid(), 'usedHint' => true], 1];
        yield 'unattributed retry without undo' => [[...self::valid(), 'usedRetry' => true]];
        yield 'hint count without flag' => [self::valid(), 1];
        yield 'unbounded hint count' => [[...self::valid(), 'usedHint' => true], 65];
        yield 'more hinted decisions than distinct hint markers' => [[...self::valid(), 'usedHint' => true, 'decisions' => [['ply' => 0, 'attempts' => 1, 'hinted' => true], ['ply' => 2, 'attempts' => 1, 'hinted' => true]]], 1];
        yield 'empty decisions' => [[...self::valid(), 'decisions' => []]];
        yield 'too many decisions' => [[...self::valid(), 'decisions' => array_fill(0, 33, self::valid()['decisions'][0])]];
        yield 'missing player decision' => [[...self::valid(), 'decisions' => [self::valid()['decisions'][0]]]];
        yield 'opponent decision' => [[...self::valid(), 'decisions' => [['ply' => 0, 'attempts' => 1, 'hinted' => false], ['ply' => 1, 'attempts' => 1, 'hinted' => false]]]];
        yield 'duplicate decision' => [[...self::valid(), 'decisions' => [self::valid()['decisions'][0], self::valid()['decisions'][0]]]];
        foreach ([['ply', -1], ['ply', 4], ['ply', '0'], ['attempts', 0], ['attempts', 65], ['attempts', '1'], ['hinted', 1], ['hinted', true], ['attempts', 2], ['extra', false]] as $index => [$field, $value]) {
            $assessment = self::valid();
            $assessment['decisions'][0][$field] = $value;
            yield 'decision '.$index => [$assessment];
        }
    }
}
