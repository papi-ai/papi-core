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

namespace PapiAI\Core\Schema;

use InvalidArgumentException;

/**
 * A regular expression as PHP writes it: delimited, with flags after the closing delimiter.
 *
 * JSON Schema has neither delimiters nor flags, so a pattern on its way to a provider loses the
 * first and may not carry the second.
 */
final class PhpRegex
{
    private const BRACKET_PAIRS = ['(' => ')', '[' => ']', '{' => '}', '<' => '>'];

    private const OPERATORS = '.^$*+?()[]{}|';

    public function __construct(
        private readonly string $regex,
    ) {
    }

    /**
     * The same expression as a JSON Schema `pattern`.
     *
     * An expression that is not delimited comes back as it is.
     *
     * @throws InvalidArgumentException When it carries a flag other than `u`
     */
    public function asJsonSchemaPattern(): string
    {
        $regex = ltrim($this->regex);
        $opening = $regex[0] ?? '';

        if ($opening === '' || $opening === '\\' || ctype_alnum($opening)) {
            return $this->regex;
        }

        $closing = self::BRACKET_PAIRS[$opening] ?? $opening;
        $end = strrpos($regex, $closing);
        $flags = $end === false ? '' : rtrim(substr($regex, $end + 1));

        if ($end === false || $end === 0 || !ctype_alpha($flags.'u')) {
            return $this->regex;
        }

        $this->assertFlagsCanBeSent($flags);

        return $this->withoutDelimiterEscapes(substr($regex, 1, $end - 1), $opening.$closing);
    }

    private function assertFlagsCanBeSent(string $flags): void
    {
        foreach (str_split($flags) as $flag) {
            if ($flag !== 'u' && $flag !== '') {
                throw new InvalidArgumentException(sprintf('The flag "%s" of the pattern "%s" cannot be sent to a provider: JSON Schema patterns have no flags.', $flag, $this->regex));
            }
        }
    }

    /**
     * A delimiter had to be escaped inside the expression. Without delimiters the escape goes,
     * unless the character is a regex operator, which the backslash still makes literal.
     */
    private function withoutDelimiterEscapes(string $body, string $delimiters): string
    {
        $pattern = '';
        $length = strlen($body);

        for ($i = 0; $i < $length; ++$i) {
            $next = $body[$i + 1] ?? '';

            if ($body[$i] !== '\\' || $next === '') {
                $pattern .= $body[$i];

                continue;
            }

            $escapesADelimiter = str_contains($delimiters, $next) && !str_contains(self::OPERATORS, $next);
            $pattern .= $escapesADelimiter ? $next : '\\'.$next;
            ++$i;
        }

        return $pattern;
    }
}
