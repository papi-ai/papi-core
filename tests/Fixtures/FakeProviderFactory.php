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

namespace PapiAI\Core\Tests\Fixtures;

use PapiAI\Core\Contracts\ProviderInterface;
use PapiAI\Core\Factory\AbstractProviderFactory;
use PapiAI\Core\Factory\Setting;
use PapiAI\Core\Factory\SettingValues;

final class FakeProviderFactory extends AbstractProviderFactory
{
    public function __construct(
        private readonly string $name = 'fake',
        private readonly string $package = 'papi-ai/fake',
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function package(): string
    {
        return $this->package;
    }

    public function settings(): array
    {
        return [
            new Setting('api_key', true, 'Fake API key'),
            new Setting('model', false, 'Default model, fake-1 unless set'),
            new Setting('max_tokens', false, 'Output ceiling, 4096 unless set'),
            new Setting('effort', false, 'Default reasoning effort'),
            new Setting('base_url', false, 'Server address'),
        ];
    }

    protected function build(SettingValues $values): ProviderInterface
    {
        return new FakeProvider(...$this->argumentsGiven([
            'apiKey' => $values->string('api_key'),
            'model' => $values->stringOrNull('model'),
            'maxTokens' => $values->intOrNull('max_tokens'),
            'effort' => $values->effort(),
            'baseUrl' => $values->stringOrNull('base_url'),
        ]));
    }
}
