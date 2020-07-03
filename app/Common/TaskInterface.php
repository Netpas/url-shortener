<?php

declare(strict_types=1);

namespace App\Common;

interface TaskInterface
{
    /**
     * Run the task.
     *
     * @param array $arguments
     */
    public function run(array $arguments);
}
