<?php

declare(strict_types=1);

namespace PapiAI\Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class Description
{
    public function __construct(
        public readonly string $text,
    ) {}
}
