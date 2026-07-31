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

use PapiAI\Core\Effort;

describe('Effort', function () {
    it('covers the full range providers offer between them', function () {
        expect(array_map(static fn (Effort $e): string => $e->value, Effort::cases()))
            ->toBe(['none', 'minimal', 'low', 'medium', 'high', 'extra-high', 'maximum']);
    });

    it('reads levels from their neutral names', function () {
        expect(Effort::tryFrom('minimal'))->toBe(Effort::Minimal);
        expect(Effort::tryFrom('extra-high'))->toBe(Effort::ExtraHigh);
        expect(Effort::tryFrom('enormous'))->toBeNull();
    });

    describe('whether to think at all', function () {
        it('knows None means do not', function () {
            expect(Effort::None->thinks())->toBeFalse();
        });

        it('knows every other level does', function () {
            foreach (Effort::cases() as $effort) {
                if ($effort !== Effort::None) {
                    expect($effort->thinks())->toBeTrue();
                }
            }
        });
    });

    describe('thinking budget', function () {
        it('rises with every step up the scale', function () {
            $previous = -1;

            foreach (Effort::cases() as $effort) {
                $budget = $effort->budgetWithin(100_000);
                expect($budget)->toBeGreaterThan($previous);
                $previous = $budget;
            }
        });

        it('spends nothing at all for None', function () {
            expect(Effort::None->budgetWithin(20_000))->toBe(0);
        });

        it('always leaves room for the answer', function () {
            foreach (Effort::cases() as $effort) {
                expect($effort->budgetWithin(20_000))->toBeLessThan(20_000);
            }
        });

        it('never falls below the floor providers enforce', function () {
            foreach (Effort::cases() as $effort) {
                if ($effort->thinks()) {
                    expect($effort->budgetWithin(4_096))->toBeGreaterThanOrEqual(Effort::MINIMUM_BUDGET);
                }
            }
        });

        it('reports when a ceiling cannot fit a thinking budget at all', function () {
            expect(Effort::Low->fitsWithin(1_200))->toBeFalse();
            expect(Effort::Low->fitsWithin(4_096))->toBeTrue();
        });

        it('always fits when the answer is not going to think', function () {
            expect(Effort::None->fitsWithin(10))->toBeTrue();
        });
    });

    describe('narrowing to what a provider actually offers', function () {
        it('maps every level onto a two-level scale', function () {
            // Gemini 3 offers only LOW and HIGH.
            expect(Effort::None->nearestOf([Effort::Low, Effort::High]))->toBe(Effort::Low);
            expect(Effort::Minimal->nearestOf([Effort::Low, Effort::High]))->toBe(Effort::Low);
            expect(Effort::Medium->nearestOf([Effort::Low, Effort::High]))->toBe(Effort::High);
            expect(Effort::Maximum->nearestOf([Effort::Low, Effort::High]))->toBe(Effort::High);
        });

        it('returns the level itself when the provider offers it', function () {
            $all = Effort::cases();

            foreach ($all as $effort) {
                expect($effort->nearestOf($all))->toBe($effort);
            }
        });

        it('clamps to the highest on offer rather than overshooting', function () {
            // OpenAI's xhigh is model-dependent, so a provider may only offer up to high.
            $offered = [Effort::None, Effort::Minimal, Effort::Low, Effort::Medium, Effort::High];

            expect(Effort::ExtraHigh->nearestOf($offered))->toBe(Effort::High);
            expect(Effort::Maximum->nearestOf($offered))->toBe(Effort::High);
        });
    });
});
