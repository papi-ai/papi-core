<?php

declare(strict_types=1);

namespace PapiAI\Core;

enum Role: string
{
    case System = 'system';
    case User = 'user';
    case Assistant = 'assistant';
    case Tool = 'tool';
}
