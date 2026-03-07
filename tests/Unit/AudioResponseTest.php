<?php

declare(strict_types=1);

use PapiAI\Core\AudioResponse;

describe('AudioResponse', function () {
    it('stores data, format, and model', function () {
        $response = new AudioResponse(
            data: 'audio-bytes',
            format: 'mp3',
            model: 'tts-1',
        );

        expect($response->data)->toBe('audio-bytes');
        expect($response->format)->toBe('mp3');
        expect($response->model)->toBe('tts-1');
    });

    it('returns size in bytes', function () {
        $response = new AudioResponse(data: '12345', format: 'mp3', model: 'tts-1');

        expect($response->size())->toBe(5);
    });

    it('saves to file', function () {
        $response = new AudioResponse(data: 'audio-content', format: 'mp3', model: 'tts-1');
        $path = sys_get_temp_dir() . '/papi-test-audio-' . uniqid() . '.mp3';

        $bytes = $response->save($path);

        expect($bytes)->toBe(13);
        expect(file_get_contents($path))->toBe('audio-content');

        unlink($path);
    });
});
