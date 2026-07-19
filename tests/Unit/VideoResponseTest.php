<?php

declare(strict_types=1);

use PapiAI\Core\VideoResponse;

describe('VideoResponse', function () {
    it('stores inline bytes, mime type, model, and duration', function () {
        $response = new VideoResponse(
            data: 'video-bytes',
            uri: null,
            mimeType: 'video/mp4',
            model: 'veo-3.0',
            durationSeconds: 8.0,
        );

        expect($response->data)->toBe('video-bytes');
        expect($response->uri)->toBeNull();
        expect($response->mimeType)->toBe('video/mp4');
        expect($response->model)->toBe('veo-3.0');
        expect($response->durationSeconds)->toBe(8.0);
    });

    it('builds from inline bytes via fromBytes()', function () {
        $response = VideoResponse::fromBytes('bytes', 'veo-3.0', 'video/webm', 5.0, ['tokens' => 1]);

        expect($response->data)->toBe('bytes');
        expect($response->uri)->toBeNull();
        expect($response->mimeType)->toBe('video/webm');
        expect($response->model)->toBe('veo-3.0');
        expect($response->durationSeconds)->toBe(5.0);
        expect($response->usage)->toBe(['tokens' => 1]);
        expect($response->hasData())->toBeTrue();
    });

    it('builds from a remote uri via fromUri()', function () {
        $response = VideoResponse::fromUri('https://example.test/clip.mp4', 'veo-3.0');

        expect($response->uri)->toBe('https://example.test/clip.mp4');
        expect($response->data)->toBe('');
        expect($response->hasData())->toBeFalse();
        expect($response->model)->toBe('veo-3.0');
    });

    it('returns inline size in bytes', function () {
        $response = VideoResponse::fromBytes('12345', 'veo-3.0');

        expect($response->size())->toBe(5);
    });

    it('saves inline bytes to file', function () {
        $response = VideoResponse::fromBytes('video-content', 'veo-3.0');
        $path = sys_get_temp_dir() . '/papi-test-video-' . uniqid() . '.mp4';

        $bytes = $response->save($path);

        expect($bytes)->toBe(13);
        expect(file_get_contents($path))->toBe('video-content');

        unlink($path);
    });
});
