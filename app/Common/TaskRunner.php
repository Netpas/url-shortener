<?php

declare(strict_types=1);

namespace App\Common;

use App\Env;

/**
 * 类似 \Slim\App 的任务运行器。
 *
 * 根据我们的任务运行器做了优化：移除了 HTTP 服务相关的内容；
 * 保留了依赖注入容器和错误处理机制，增加了简单的命令行处理机制。
 */
class TaskRunner extends Base
{
    /** @var array */
    protected $argv;
    /** @var string */
    protected $scriptName = '';

    public function __construct()
    {
        $this->argv       = $GLOBALS['argv'];
        $this->scriptName = basename($this->argv[0]);
    }

    /**
     * Run application
     *
     * This method traverses the application middleware stack and then sends the
     * resultant Response object to the HTTP client.
     */
    public function run()
    {
        $index = null;
        $opts  = getopt('h', ['help', 'version'], $index);
        if (array_key_exists('h', $opts) || array_key_exists('help', $opts)) {
            $this->displayUsage();
        } elseif (array_key_exists('version', $opts)) {
            $this->displayVersion();
        } else {
            $arguments = array_slice($this->argv, $index);
            $taskName  = $arguments[0] ?? '';
            $arguments = array_slice($arguments, 1);
            $this->dispatchTask($taskName, $arguments);
        }
    }

    protected function dispatchTask(string $name, array $arguments)
    {
        $settings  = Env::getSettings();
        $taskMap   = $settings['taskMap'] ?? [];
        $className = $taskMap[$name]      ?? null;
        $logger    = Env::getLogger();
        if (null === $className) {
            $logger->error("Unknown task '$name'.");

            exit(2);
        } else {
            if ('' === $className) {
                $className = $name;
            }
            $className = '\App\Task\\'.$className;
        }
        try {
            $task = new $className();
        } catch (\Exception $err) {
            $logger->error("Cannot create task '$name'. ".strval($err));

            exit(1);
        }
        try {
            $logger->info("Task '$name' started.", $arguments);
            $task->run($arguments);
            $logger->info("Task '$name' finished.", $arguments);
        } catch (\Exception $err) {
            $logger->error("Task '$name' failed. ".strval($err));

            exit(1);
        }
    }

    /**
     * Display the help message.
     */
    protected function displayUsage()
    {
        echo <<<EOL
Task Runner

Run the task named NAME. Its arguments
should be followed by the task name.

Usage:
    $this->scriptName [OPTIONS] NAME [ARGUMENTS]

Options:
    -h, --help  Display this message and exit.
    --version   Display the version and exit.

EOL;
    }

    /**
     * Display the version information.
     *
     * @param string $scriptName
     */
    protected function displayVersion()
    {
        $name    = @constant('APP_NAME')    ?? 'APP_NAME';
        $stage   = @constant('APP_STAGE')   ?? 'APP_STAGE';
        $version = @constant('APP_VERSION') ?? 'APP_VERSION';
        echo "$name ($stage) $version".PHP_EOL;
    }
}
