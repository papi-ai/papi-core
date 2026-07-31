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

namespace PapiAI\Core\Exception;

use InvalidArgumentException;
use PapiAI\Core\Effort;

/**
 * Thrown when a caller asks for a level of effort that does not exist.
 *
 * The message is built here rather than at each provider so the accepted vocabulary is stated in
 * one place and cannot drift as levels are added. Extends `InvalidArgumentException` to sit
 * alongside the other option-validation failures, which callers already catch.
 */
final class UnknownEffortException extends InvalidArgumentException
{
    public function __construct(string $value)
    {
        parent::__construct(sprintf(
            'Unknown effort "%s". Expected one of: %s.',
            $value,
            implode(', ', array_map(static fn (Effort $effort): string => '"' . $effort->value . '"', Effort::cases())),
        ));
    }
}
