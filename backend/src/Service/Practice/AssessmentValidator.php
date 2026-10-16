<?php

declare(strict_types=1);

namespace App\Service\Practice;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/** @phpstan-type Assessment array{version: 1, usedHint: bool, usedRetry: bool, usedUndo: bool, decisions: list<array{ply: int, attempts: int, hinted: bool}>} */
final class AssessmentValidator
{
    /** @param list<array{fromKey: string}> $steps */
    public function normalize(mixed $assessment, array $steps, string $playingColor, int $hintCount): string
    {
        $json = json_encode($this->validate($assessment, $steps, $playingColor, $hintCount), JSON_THROW_ON_ERROR);
        if (strlen($json) > 4096) {
            throw new BadRequestHttpException();
        }

        return $json;
    }

    /**
     * @param list<array{fromKey: string}> $steps
     *
     * @return Assessment
     */
    public function validate(mixed $assessment, array $steps, string $playingColor, int $hintCount): array
    {
        if (!in_array($playingColor, ['w', 'b'], true) || $hintCount < 0 || $hintCount > 64 || count($steps) < 2 || count($steps) > 64
            || !is_array($assessment) || !$this->keys($assessment, ['version', 'usedHint', 'usedRetry', 'usedUndo', 'decisions'])
            || 1 !== $assessment['version'] || !is_bool($assessment['usedHint']) || !is_bool($assessment['usedRetry']) || !is_bool($assessment['usedUndo'])
            || $assessment['usedHint'] !== ($hintCount > 0) || !is_array($assessment['decisions']) || !array_is_list($assessment['decisions'])
            || [] === $assessment['decisions'] || count($assessment['decisions']) > 32) {
            throw new BadRequestHttpException();
        }
        $expected = [];
        foreach ($steps as $ply => $step) {
            if ($playingColor === (explode(' ', $step['fromKey'])[1] ?? null)) {
                $expected[] = $ply;
            }
        }
        $decisions = [];
        foreach ($assessment['decisions'] as $decision) {
            if (!is_array($decision) || !$this->keys($decision, ['ply', 'attempts', 'hinted'])
                || !is_int($decision['ply']) || !in_array($decision['ply'], $expected, true) || isset($decisions[$decision['ply']])
                || !is_int($decision['attempts']) || $decision['attempts'] < 1 || $decision['attempts'] > 64 || !is_bool($decision['hinted'])
                || ($decision['hinted'] && !$assessment['usedHint']) || ($decision['attempts'] > 1 && !$assessment['usedRetry'] && !$assessment['usedUndo'])) {
                throw new BadRequestHttpException();
            }
            $decisions[$decision['ply']] = ['ply' => $decision['ply'], 'attempts' => $decision['attempts'], 'hinted' => $decision['hinted']];
        }
        $hinted = count(array_filter($decisions, static fn (array $decision): bool => $decision['hinted']));
        $retried = [] !== array_filter($decisions, static fn (array $decision): bool => $decision['attempts'] > 1);
        if (count($decisions) !== count($expected) || $hinted > $hintCount
            || (!$assessment['usedUndo'] && ($hinted !== $hintCount || $assessment['usedRetry'] !== $retried))) {
            throw new BadRequestHttpException();
        }
        ksort($decisions);

        return ['version' => 1, 'usedHint' => $assessment['usedHint'], 'usedRetry' => $assessment['usedRetry'], 'usedUndo' => $assessment['usedUndo'], 'decisions' => array_values($decisions)];
    }

    /** @param array<array-key, mixed> $value
     * @param list<string> $keys
     */
    private function keys(array $value, array $keys): bool
    {
        return count($value) === count($keys) && [] === array_diff($keys, array_keys($value));
    }
}
