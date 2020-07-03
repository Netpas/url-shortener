<?php

declare(strict_types=1);

namespace App\Task;

use App\Business\Shortener;
use App\Common\Base;
use App\Common\TaskInterface;
use App\Env;

class ClearObsoletes extends Base implements TaskInterface
{
    /**
     * Run the task.
     */
    public function run(array $arguments)
    {
        Env::bootstrapDatabase();
        $service = new Shortener();
        $service->clearObsoletes();
    }
}
