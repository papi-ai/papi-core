<?php

declare(strict_types=1);

namespace PapiAI\Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Tool
{
    public function __construct(
        public readonly string $description,
        public readonly ?string $name = null,
    ) {}
}
