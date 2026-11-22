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

use PapiAI\Core\Agent;
use PapiAI\Core\Effort;
use PapiAI\Core\Tests\Fixtures\ScriptedProvider;

describe('Agent effort', function () {
    it('takes the effort as an enum, and sends providers its name', function () {
        $provider = new ScriptedProvider();
        $agent = new Agent($provider, 'm');

        $agent->run('Think hard', ['effort' => Effort::High]);
        iterator_to_array($agent->stream('Think hard', ['effort' => Effort::Low]));
        iterator_to_array($agent->streamEvents('Think hard', ['effort' => Effort::Minimal]));

        expect(array_map(static fn (array $call): string => $call['options']['effort'], $provider->calls))
            ->toBe(['high', 'low', 'minimal', 'minimal']);
    });

    it('still takes the effort as a string', function () {
        $provider = new ScriptedProvider();

        (new Agent($provider, 'm'))->run('Think', ['effort' => 'medium']);

        expect($provider->calls[0]['options']['effort'])->toBe('medium');
    });
});
