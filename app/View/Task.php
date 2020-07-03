<?php

declare(strict_types=1);

namespace App\View;

use App\Common\Aborter;
use App\Env;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Task View
 */
class Task extends Base
{
    /**
     * 确认环境变量 PATH 包含一些必需的路径。
     *
     * Docker 环境中，我们 exec 一个以 #!/usr/bin/env php 开始的脚本，
     * 因为配置缺少环境变量 PATH，会导致什么也不做。因此我们需要一个机制，
     * 在 exec 外部命令之前，确保环境变量 PATH 是合理的。
     */
    protected function ensurePathEnv()
    {
        $reservedPaths = [
            '/sbin',
            '/usr/sbin',
            '/usr/local/sbin',
            '/bin',
            '/usr/bin',
            '/usr/local/bin',
        ];
        $currentPath = getenv('PATH');
        if (false === $currentPath) {
            $currentPath = '';
        }
        $currentPaths = explode(PATH_SEPARATOR, $currentPath);
        $paths        = array_unique(array_merge($reservedPaths, $currentPaths));
        $path         = implode(PATH_SEPARATOR, $paths);
        putenv('PATH='.$path);
    }

    /**
     * 执行异步任务
     */
    public function task(Request $request, Response $response, array $args): Response
    {
        $settings = Env::getSettings();
        $taskName = $request->getQueryParams()['name'] ?? '';
        if (!array_key_exists($taskName, $settings['taskMap'])) {
            $payload = [
                'status'  => 2,
                'message' => "Unknown task '$taskName'.",
            ];
            throw new Aborter($payload, 400);
        }
        if ([''] == $request->getHeader('Content-Type')) {
            $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        }
        $params = $request->getParsedBody() ?? [];
        $script = realpath($settings['taskRunner']['script']);
        if (!file_exists($script)) {
            $payload = [
                'status'  => 1,
                'message' => 'Task runner not found.',
            ];
            throw new Aborter($payload, 500);
        }
        $commandParts   = ['nohup'];
        $commandParts[] = escapeshellarg($script);
        $commandParts[] = escapeshellarg($taskName);
        foreach ($params as $key => $value) {
            if (1 === strlen($key)) {
                $commandParts[] = escapeshellarg('-'.$key);
                $commandParts[] = escapeshellarg($value);
            } else {
                $longOption     = '--'.$key.'='.$value;
                $commandParts[] = escapeshellarg($longOption);
            }
        }
        $commandParts[] = '>/dev/null';
        $commandParts[] = '2>&1';
        $commandParts[] = '&';
        $command        = implode(' ', $commandParts);
        $this->debug("Execute: $command");
        $this->ensurePathEnv();
        exec($command, $output, $status);
        $result = [
            'status'  => 200,
            'message' => 'OK',
        ];

        return $this->jsonify($response, $result);
    }
}
