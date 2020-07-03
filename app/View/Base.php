<?php

declare(strict_types=1);

namespace App\View;

use App\Common\Aborter;
use App\Common\Base as CommonBase;
use Carbon\Carbon;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

/**
 * 视图基类。
 */
class Base extends CommonBase
{
    /**
     * 终止运行
     *
     * @param mixed $message
     * @param array $headers
     */
    protected function abort($message, int $code = 0, array $headers = null)
    {
        throw new Aborter($message, $code, $headers);
    }

    /**
     * 以 Slim 的方式返回 404 Not Found，避免不同情况返回的 404 表现不同.
     *
     * @param ?string $message
     *
     * @return Response
     */
    protected function notFound(Request $request, ?string $message = null)
    {
        throw new HttpNotFoundException($request, $message);
    }

    /**
     * 返回 JSON 类型的响应
     *
     * @see https://www.slimframework.com/docs/v4/objects/response.html#returning-json
     *
     * @param mixed $data
     */
    protected function jsonify(Response $response, $data, bool $humanFriendly = false)
    {
        // PHP 默认给斜杠做转码不是必要的，所以我们以取消这个行为为默认设置
        $options = JSON_UNESCAPED_SLASHES;
        if ($humanFriendly) {
            $options = $options | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;
        }
        $content = json_encode($data, $options);
        $response->getBody()->write($content);

        return $response
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * 从报头中提取指定信息，如果不存在则抛 400 异常
     */
    protected function getHeaderFor(Request $request, string $name): string
    {
        $value = $request->getHeader($name)[0] ?? null;
        if (null === $value) {
            throw new Aborter("Header $name not found.", 400);
        }

        return $value;
    }

    /**
     * 检查 requestAt 时间戳
     */
    protected function verifyRequestAt(string $requestAt, int $maxGap = 120)
    {
        $now    = time();
        $format = 'Y-m-d\TH:i:s\Z';
        $moment = new Carbon($requestAt);
        if ($requestAt != $moment->format($format)) {
            // 这样可以避免使用诸如 `now` 这样的 $requestAt
            throw new Aborter('Invalid request timestamp format.', 400);
        }
        $timestamp = $moment->getTimestamp();
        if (abs($now - $timestamp) > $maxGap) {
            throw new Aborter('Invalid request timestamp.', 400);
        }
    }

    /**
     * 显示 phpinfo() 信息。
     */
    public function phpInfo(Request $request, Response $response, array $args): Response
    {
        $this->warning('Invoking `phpinfo()`...');
        ob_start();
        phpinfo();
        $content = ob_get_contents();
        ob_end_clean();
        $response->getBody()->write($content);

        return $response;
    }

    /**
     * 显示元信息
     */
    public function meta(Request $request, Response $response, array $args): Response
    {
        $data = [
            'name'    => APP_NAME,
            'version' => APP_VERSION,
            'stage'   => APP_STAGE,
        ];

        return $this->jsonify($response, $data, true);
    }
}
