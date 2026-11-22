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

namespace PapiAI\Core\Workflow\Step;

use PapiAI\Core\Response;
use PapiAI\Core\Usage;

/**
 * What a step's code returned, read as an output to store and the tokens it cost. A Response
 * gives its structured data, or its text, and its usage; anything else is the output itself.
 *
 * @internal
 */
final class Produced
{
    public function __construct(
        private readonly mixed $returned,
    ) {
    }

    public function output(): mixed
    {
        if (!$this->returned instanceof Response) {
            return $this->returned;
        }

        return $this->returned->data ?? $this->returned->text;
    }

    public function usage(): Usage
    {
        return $this->returned instanceof Response ? $this->returned->usage : new Usage();
    }
}
