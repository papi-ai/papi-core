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

use PapiAI\Core\Workflow\State;

/**
 * One named step of a workflow, as the Workflow runs it.
 *
 * @internal
 */
interface StepInterface
{
    /**
     * Does one unit of work and says where the run stands after it.
     *
     * @param string|null $following The step after this one in the workflow, null for the last
     */
    public function run(State $state, ?string $following): State;
}
