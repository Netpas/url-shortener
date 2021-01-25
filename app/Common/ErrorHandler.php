<?php

declare(strict_types=1);

namespace App\Common;

use App\Env;
use Slim\Error\Renderers\JsonErrorRenderer;
use Slim\Exception\HttpNotFoundException;
use Slim\Handlers\ErrorHandler as BaseErrorHandler;
use Slim\Interfaces\ErrorRendererInterface;

/**
 * 错误处理器
 *
 * 符合 Slim 框架要求的错误处理器，为我们的 Aborter 异常机制提供了特别的支持。
 */
class ErrorHandler extends BaseErrorHandler
{
    /**
     * @var string
     */
    protected $defaultErrorRendererContentType = 'application/json';

    /**
     * @var ErrorRendererInterface|string|callable
     */
    protected $defaultErrorRenderer = JsonErrorRenderer::class;

    protected function determineStatusCode(): int
    {
        if ($this->exception instanceof Aborter) {
            /** @var Aborter $aborter */
            $aborter = $this->exception;

            return $aborter->getStatusCode();
        } else {
            return parent::determineStatusCode();
        }
    }

    protected function logError(string $error): void
    {
        $logger = Env::getLogger();
        if ($this->exception instanceof HttpNotFoundException) {
            if (null === $logger) {
                error_log('404 Not Found');
            } else {
                $logger->error('404 Not Found');
            }
        } else {
            if (null === $logger) {
                error_log($error);
            } else {
                $logger->error($error);
            }
        }
    }
}
