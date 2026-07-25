<?php

declare(strict_types=1);

use PapiAI\Core\ToolChoice;

$tools = [
    ['name' => 'get_weather', 'description' => 'Weather', 'parameters' => []],
    ['name' => 'search', 'description' => 'Search', 'parameters' => []],
];

describe('ToolChoice', function () use ($tools) {
    it('normalises auto', function () {
        $choice = ToolChoice::fromOption('auto', []);

        expect($choice->mode)->toBe(ToolChoice::AUTO);
        expect($choice->toolName)->toBeNull();
        expect($choice->isAuto())->toBeTrue();
        expect($choice->forcesSpecificTool())->toBeFalse();
    });

    it('normalises none', function () use ($tools) {
        expect(ToolChoice::fromOption('none', $tools)->mode)->toBe(ToolChoice::NONE);
    });

    it('normalises required', function () use ($tools) {
        $choice = ToolChoice::fromOption('required', $tools);

        expect($choice->mode)->toBe(ToolChoice::REQUIRED);
        expect($choice->toolName)->toBeNull();
        expect($choice->forcesSpecificTool())->toBeFalse();
    });

    it('normalises a specific tool to required + name', function () use ($tools) {
        $choice = ToolChoice::fromOption(['name' => 'search'], $tools);

        expect($choice->mode)->toBe(ToolChoice::REQUIRED);
        expect($choice->toolName)->toBe('search');
        expect($choice->forcesSpecificTool())->toBeTrue();
    });

    describe('validation (fails loud, before any HTTP)', function () use ($tools) {
        it('rejects an unknown string value', function () use ($tools) {
            expect(fn () => ToolChoice::fromOption('always', $tools))
                ->toThrow(InvalidArgumentException::class, 'Unknown toolChoice');
        });

        it('rejects a malformed array', function () use ($tools) {
            expect(fn () => ToolChoice::fromOption(['tool' => 'search'], $tools))
                ->toThrow(InvalidArgumentException::class, 'Invalid toolChoice array');
            expect(fn () => ToolChoice::fromOption(['name' => ''], $tools))
                ->toThrow(InvalidArgumentException::class);
        });

        it('rejects required with no tools declared', function () {
            expect(fn () => ToolChoice::fromOption('required', []))
                ->toThrow(InvalidArgumentException::class, 'requires at least one declared tool');
        });

        it('rejects none with no tools declared', function () {
            expect(fn () => ToolChoice::fromOption('none', []))
                ->toThrow(InvalidArgumentException::class, 'requires at least one declared tool');
        });

        it('rejects a specific tool with no tools declared', function () {
            expect(fn () => ToolChoice::fromOption(['name' => 'search'], []))
                ->toThrow(InvalidArgumentException::class);
        });

        it('rejects a specific tool name not among the declared tools', function () use ($tools) {
            expect(fn () => ToolChoice::fromOption(['name' => 'unknown_tool'], $tools))
                ->toThrow(InvalidArgumentException::class, 'not among the declared tools');
        });
    });
});
