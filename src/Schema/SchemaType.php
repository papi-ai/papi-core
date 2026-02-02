<?php

declare(strict_types=1);

namespace PapiAI\Core\Schema;

enum SchemaType: string
{
    case String = 'string';
    case Number = 'number';
    case Integer = 'integer';
    case Boolean = 'boolean';
    case Array = 'array';
    case Object = 'object';
    case Null = 'null';
    case Enum = 'enum';
}
