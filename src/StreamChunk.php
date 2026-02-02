<?php

declare(strict_types=1);

namespace PapiAI\Core;

final class StreamChunk
{
    public function __construct(
        public readonly string $text,
        public readonly bool $isComplete = false,
    ) {}
}
