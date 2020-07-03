<?php

declare(strict_types=1);

namespace App\Common;

use RuntimeException;

/**
 * 终止执行异常。
 *
 * 终止执行表现为一个异常，并特别为 RESTFul 的接口设计，非常适合用于以
 * HTTP 响应的方式结束处理进程。
 */
class Aborter extends RuntimeException
{
    /** @var array */
    protected $headers;

    /**
     * @param mixed $message
     * @param int   $code
     * @param array $headers
     */
    public function __construct($message, int $code = 0, array $headers = null)
    {
        if (!is_string($message)) {
            $message = json_encode($message);
        }
        if ($code < 200 || $code >= 500) {
            $code = 500;
        }
        if (204 == $code || 205 == $code) {
            // 204 No Content, 205 Reset Content
            $message = '';
        }
        parent::__construct($message, $code);
        $this->headers = (null === $headers) ? [] : $headers;
        if (!isset($this->headers['Content-Type'])) {
            $this->headers['Content-Type'] = 'application/json; charset=utf-8';
        }
    }

    /**
     * 返回 HTTP 状态码
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->getCode();
    }

    /**
     * 返回 HTTP 报头
     *
     * @return int
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * 返回 HTTP 报文
     *
     * @return int
     */
    public function getBody(): string
    {
        return $this->getMessage();
    }
}
