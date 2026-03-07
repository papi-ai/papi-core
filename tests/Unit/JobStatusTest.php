<?php

declare(strict_types=1);

use PapiAI\Core\JobStatus;
use PapiAI\Core\Response;

describe('JobStatus', function () {
    it('reports pending status', function () {
        $status = new JobStatus(jobId: 'job-1', status: JobStatus::PENDING);

        expect($status->isPending())->toBeTrue();
        expect($status->isRunning())->toBeFalse();
        expect($status->isCompleted())->toBeFalse();
        expect($status->isFailed())->toBeFalse();
    });

    it('reports running status', function () {
        $status = new JobStatus(jobId: 'job-1', status: JobStatus::RUNNING);

        expect($status->isRunning())->toBeTrue();
    });

    it('reports completed status with result', function () {
        $response = new Response(text: 'Done');
        $status = new JobStatus(
            jobId: 'job-1',
            status: JobStatus::COMPLETED,
            result: $response,
        );

        expect($status->isCompleted())->toBeTrue();
        expect($status->result)->toBe($response);
    });

    it('reports failed status with error', function () {
        $status = new JobStatus(
            jobId: 'job-1',
            status: JobStatus::FAILED,
            error: 'Something went wrong',
        );

        expect($status->isFailed())->toBeTrue();
        expect($status->error)->toBe('Something went wrong');
    });
});
