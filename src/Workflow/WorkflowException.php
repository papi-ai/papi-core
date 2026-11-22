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

namespace PapiAI\Core\Workflow;

use PapiAI\Core\Exception\PapiException;

/**
 * A workflow was defined or resumed in a way that cannot run. A step that throws is not one of
 * these: that ends the run as failed.
 */
final class WorkflowException extends PapiException
{
}
