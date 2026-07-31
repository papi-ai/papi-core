<?php

/*
 * This file is part of PapiAI,
 * A simple but powerful PHP library for building AI agents.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PapiAI\Core;

/**
 * How hard the model should think before answering.
 *
 * A neutral scale over a knob every reasoning provider spells differently: OpenAI takes a
 * `reasoning_effort` string whose accepted values depend on the model, Anthropic wants a token
 * budget for extended thinking, Gemini wants either a budget or one of two levels depending on the
 * model generation.
 *
 * The scale is deliberately wider than any single provider offers, so a caller can say what they
 * mean and each provider narrows it to what it actually has (see {@see nearestOf()}). Narrowing
 * belongs to the provider because only it knows its own range; deciding what a level *costs* is
 * shared, so that arithmetic lives here rather than being reinvented per provider.
 *
 * Providers with no reasoning knob ignore the option entirely, documented per provider. Effort is
 * a hint about quality rather than a guarantee, so being ignored degrades nothing the caller was
 * promised. That is the opposite of `toolChoice`, where being ignored would break a promise.
 */
enum Effort: string
{
    /**
     * The smallest budget worth spending. Anthropic rejects anything under this, and it is a sane
     * floor for everyone else.
     */
    public const MINIMUM_BUDGET = 1024;

    /**
     * Room the answer itself needs, over and above whatever thinking consumes.
     */
    private const ANSWER_HEADROOM = 512;

    /**
     * Do not think at all. Worth asking for explicitly on models that otherwise think by default.
     */
    case None = 'none';

    case Minimal = 'minimal';
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case ExtraHigh = 'extra-high';

    /**
     * Everything the ceiling allows, leaving only room to answer.
     */
    case Maximum = 'maximum';

    /**
     * Whether this level asks the model to think at all.
     */
    public function thinks(): bool
    {
        return $this !== self::None;
    }

    /**
     * Tokens to spend thinking, given the ceiling for the whole response.
     *
     * Thinking counts against the same ceiling as the answer, so the budget is a share of it,
     * clamped to leave room to reply. Callers should check {@see fitsWithin()} first: a ceiling too
     * small for any budget cannot be satisfied by clamping.
     *
     * @param int $maxTokens The response ceiling the request will carry
     *
     * @return int Tokens to allot to thinking, zero when this level does not think
     */
    public function budgetWithin(int $maxTokens): int
    {
        if (!$this->thinks()) {
            return 0;
        }

        $ceiling = $maxTokens - self::ANSWER_HEADROOM;

        return max(self::MINIMUM_BUDGET, min((int) floor($maxTokens * $this->share()), $ceiling));
    }

    /**
     * Whether a thinking budget can fit under this ceiling at all.
     *
     * False means the request cannot both think and answer, which is a caller error rather than
     * something to paper over by silently dropping the option.
     *
     * @param int $maxTokens The response ceiling the request will carry
     */
    public function fitsWithin(int $maxTokens): bool
    {
        if (!$this->thinks()) {
            return true;
        }

        return $maxTokens - self::ANSWER_HEADROOM >= self::MINIMUM_BUDGET;
    }

    /**
     * The closest level a provider actually offers.
     *
     * Providers rarely implement the whole scale: Gemini 3 has two levels, OpenAI's top levels
     * exist only on some models. Rather than each provider inventing its own rounding, they declare
     * what they offer and ask for the nearest match.
     *
     * Ties round **up**. On a two-level scale a request for Medium is equally far from either, and
     * quietly dropping to the floor is the more surprising outcome: the caller asked for real
     * thinking and would get the least available. Erring high costs tokens, which is visible;
     * erring low costs answer quality, which is not.
     *
     * @param non-empty-list<self> $offered The levels this provider can honour
     *
     * @return self The nearest offered level
     */
    public function nearestOf(array $offered): self
    {
        $target = $this->rank();
        $best = null;
        $bestDistance = PHP_INT_MAX;

        foreach ($offered as $candidate) {
            $distance = abs($candidate->rank() - $target);

            if ($distance < $bestDistance || ($distance === $bestDistance && $best !== null && $candidate->rank() > $best->rank())) {
                $best = $candidate;
                $bestDistance = $distance;
            }
        }

        return $best ?? $this;
    }

    /**
     * Position on the scale, low to high.
     */
    private function rank(): int
    {
        return match ($this) {
            self::None => 0,
            self::Minimal => 1,
            self::Low => 2,
            self::Medium => 3,
            self::High => 4,
            self::ExtraHigh => 5,
            self::Maximum => 6,
        };
    }

    /**
     * The proportion of the ceiling this level is willing to spend on thinking.
     */
    private function share(): float
    {
        return match ($this) {
            self::None => 0.0,
            self::Minimal => 0.05,
            self::Low => 0.2,
            self::Medium => 0.4,
            self::High => 0.6,
            self::ExtraHigh => 0.8,
            self::Maximum => 1.0,
        };
    }
}
