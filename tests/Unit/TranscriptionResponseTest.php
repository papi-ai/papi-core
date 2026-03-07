<?php

declare(strict_types=1);

use PapiAI\Core\TranscriptionResponse;

describe('TranscriptionResponse', function () {
    it('stores text, model, language, and duration', function () {
        $response = new TranscriptionResponse(
            text: 'Hello world',
            model: 'whisper-1',
            language: 'en',
            duration: 2.5,
        );

        expect($response->text)->toBe('Hello world');
        expect($response->model)->toBe('whisper-1');
        expect($response->language)->toBe('en');
        expect($response->duration)->toBe(2.5);
    });

    it('defaults language and duration to null', function () {
        $response = new TranscriptionResponse(text: 'Hello', model: 'whisper-1');

        expect($response->language)->toBeNull();
        expect($response->duration)->toBeNull();
    });

    it('stores segments', function () {
        $segments = [
            ['start' => 0.0, 'end' => 1.0, 'text' => 'Hello'],
            ['start' => 1.0, 'end' => 2.0, 'text' => 'world'],
        ];

        $response = new TranscriptionResponse(
            text: 'Hello world',
            model: 'whisper-1',
            segments: $segments,
        );

        expect($response->hasSegments())->toBeTrue();
        expect($response->segmentCount())->toBe(2);
        expect($response->segments[0]['text'])->toBe('Hello');
    });

    it('reports no segments when empty', function () {
        $response = new TranscriptionResponse(text: 'Hello', model: 'whisper-1');

        expect($response->hasSegments())->toBeFalse();
        expect($response->segmentCount())->toBe(0);
    });
});
