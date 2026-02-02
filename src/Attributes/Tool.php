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
