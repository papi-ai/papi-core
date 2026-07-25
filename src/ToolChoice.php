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

namespace PapiAI\Core;

use InvalidArgumentException;

/**
 * Normalised, validated representation of the provider-agnostic `toolChoice` option.
 *
 * Callers pass `toolChoice` to a provider's chat() as one of:
 *   - `'auto'`      the model decides (the default when the option is absent)
 *   - `'none'`      the model must not call tools
 *   - `'required'`  the model must call one of the declared tools
 *   - `['name' => '<tool>']`  the model must call exactly that tool
 *
 * Each provider calls {@see self::fromOption()} to validate the value against the declared tools
 * (failing loudly, before any HTTP call) and then maps the normalised `mode`/`toolName` to its own
 * API mechanism. Keeping validation here guarantees identical semantics across every provider.
 */
final class ToolChoice
{
    public const AUTO = 'auto';
    public const NONE = 'none';
    public const REQUIRED = 'required';

    /**
     * @param string      $mode     One of AUTO, NONE, REQUIRED
     * @param string|null $toolName The specific tool to force, when the caller named one
     */
    private function __construct(
        public readonly string $mode,
        public readonly ?string $toolName = null,
    ) {
    }

    /**
     * Validate and normalise a raw `toolChoice` option against the declared tools.
     *
     * @param string|array<string, mixed>       $value The raw toolChoice option
     * @param array<int, array<string, mixed>>  $tools The declared tool definitions (each with a `name`)
     *
     * @throws InvalidArgumentException On an unknown value; on `none`/`required`/specific with no tools
     *   declared; or when a named tool is not among the declared tools
     */
    public static function fromOption(string|array $value, array $tools): self
    {
        if (is_string($value)) {
            if (!in_array($value, [self::AUTO, self::NONE, self::REQUIRED], true)) {
                throw new InvalidArgumentException(sprintf(
                    'Unknown toolChoice "%s". Expected "auto", "none", "required", or ["name" => "<tool>"].',
                    $value,
                ));
            }

            $mode = $value;
            $toolName = null;
        } else {
            $name = $value['name'] ?? null;
            if (!is_string($name) || $name === '') {
                throw new InvalidArgumentException('Invalid toolChoice array. Expected ["name" => "<tool>"].');
            }

            $mode = self::REQUIRED;
            $toolName = $name;
        }

        if ($mode !== self::AUTO && $tools === []) {
            throw new InvalidArgumentException(sprintf(
                'toolChoice "%s" requires at least one declared tool, but none were provided.',
                $toolName ?? $mode,
            ));
        }

        if ($toolName !== null) {
            $names = self::toolNames($tools);
            if (!in_array($toolName, $names, true)) {
                throw new InvalidArgumentException(sprintf(
                    'toolChoice names tool "%s", which is not among the declared tools (%s).',
                    $toolName,
                    $names === [] ? 'none' : implode(', ', $names),
                ));
            }
        }

        return new self($mode, $toolName);
    }

    /**
     * Whether this is the default, model-decides choice.
     *
     * @return bool True for auto with no forced tool
     */
    public function isAuto(): bool
    {
        return $this->mode === self::AUTO && $this->toolName === null;
    }

    /**
     * Whether a specific tool is being forced.
     *
     * @return bool True when a tool name was given
     */
    public function forcesSpecificTool(): bool
    {
        return $this->toolName !== null;
    }

    /**
     * Extract the declared tool names.
     *
     * @param array<int, array<string, mixed>> $tools
     *
     * @return list<string>
     */
    private static function toolNames(array $tools): array
    {
        $names = [];
        foreach ($tools as $tool) {
            if (isset($tool['name']) && is_string($tool['name'])) {
                $names[] = $tool['name'];
            }
        }

        return $names;
    }
}
