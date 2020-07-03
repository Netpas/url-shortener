<?php

declare(strict_types=1);

namespace App\View;

use App\Business\Caching;
use App\Business\Model\Shortener\Url;
use App\Business\Shortener;
use App\Business\Util;
use App\Env;
use Carbon\Carbon;
use function JmesPath\search as jmesPath;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Service extends Base
{
    /**
     * 返回一个表示 fault 的响应
     */
    protected function fault(Response $response, array $fault)
    {
        [$status, $message] = $fault;
        $result             = [
            'status'  => $status,
            'message' => $message,
        ];

        return $this->jsonify($response, $result);
    }

    /**
     * 提交短链
     *
     * 需要通过表单或者 JSON 提交：
     *
     * app: 应用程序 UUID
     * timestamp: 当前时间戳
     * key: sha256(app uuid + timestamp + static key)
     * target: 目标网址
     * expired_at: (可选)过期时间字符串，PHP 可识别即可
     * expired_in: (可选)多少秒后过期（如果有 expired_at 则无效）
     * duplicable: (可选)默认为 false，表示尽可能同一个目标返回同一个 token
     */
    public function submit(Request $request, Response $response, array $args): Response
    {
        Env::bootstrapDatabase();
        // 表单或者 JSON 都支持
        $params    = $request->getParsedBody();
        $uuid      = strval(jmesPath('app', $params) ?? '');
        $timestamp = intval(jmesPath('timestamp', $params) ?? 0);
        $digest    = strval(jmesPath('key', $params) ?? '');
        $target    = strval(jmesPath('target', $params) ?? '');
        $expiredAt = strval(jmesPath('expired_at', $params) ?? '');
        if (!empty($expiredAt)) {
            $expiredAt = new Carbon($expiredAt);
        } else {
            $seconds = intval(jmesPath('expired_in', $params) ?? 0);
            if (!empty($seconds)) {
                $expiredAt = new Carbon('now');
                $expiredAt->addSeconds($seconds);
            } else {
                $expiredAt = null;
            }
        }
        $duplicable = Util::boolean(jmesPath('duplicable', $params) ?? false);
        // 检查 target 是否看起来靠谱
        $fault = Util::checkUrl($target);
        if (is_array($fault)) {
            return $this->fault($response, $fault);
        }
        // 检查 App 的鉴权是否正确
        $shortener = new Shortener();
        $app       = $shortener->getAppByAuth($uuid, $timestamp, $digest);
        if (is_array($app)) {
            return $this->fault($response, $app);
        }
        // 构建短链
        $item   = Url::create($target, $app, $expiredAt, $duplicable);
        $result = [
            'status'  => 200,
            'message' => 'OK',
            'data'    => [
                'token'      => $item->token,
                'target'     => $item->target,
                'expired_at' => $item->expired_at,
            ],
        ];

        return $this->jsonify($response, $result);
    }

    /**
     * 获取指定 $token 的目标并重定向
     */
    public function fetch(Request $request, Response $response, array $args): Response
    {
        $token = $args['token'];
        if (empty($token)) {
            return $response->withStatus(404);
        }
        $caching = new Caching();
        $target  = $caching->getTargetByToken($token);
        if (null === $target) {
            Env::bootstrapDatabase();
            $item = Url::byToken($token);
            if ($item && !$item->disabled && !$item->isExpired()) {
                $target = $item->target;
                $caching->cacheUrl($item);
            }
        }
        if (empty($target)) {
            // 可能是 null 或 ''
            if (null === $target) {
                $caching->cacheMissed($token);
            }

            return $response->withStatus(404);
        }

        return $response
            ->withHeader('Location', $target)
            ->withStatus(302);
    }
}
