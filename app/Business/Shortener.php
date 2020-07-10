<?php

declare(strict_types=1);

namespace App\Business;

use App\Business\Model\Shortener\App;
use App\Business\Model\Shortener\Url;
use App\Common\Base;
use Carbon\Carbon;

// use App\Common\Database;
// use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Shortener Service
 */
class Shortener extends Base
{
    /**
     * 根据鉴权信息获取 App 对象
     *
     * @return array|App
     */
    public function getAppByAuth(string $uuid, int $timestamp, string $digest)
    {
        $now = time();
        if (abs($now - $timestamp) > 60 * 15) {
            $this->warning('Invalid timestamp.');

            return [400, 'Bad Request'];
        }
        $app = App::byUuid($uuid);
        if (null === $app) {
            $this->warning('Application not found.');

            return [403, 'Forbidden'];
        }
        if ($app->disabled) {
            $this->warning('Invalid application.');

            return [403, 'Forbidden'];
        }
        $spam = sprintf('%s%d%s', $uuid, $timestamp, $app->key);
        if ($digest !== hash('sha256', $spam)) {
            $this->warning('Incorrect digest.');

            return [403, 'Forbidden'];
        }

        return $app;
    }

    /**
     * 清除过期的短链
     */
    public function clearObsoletes()
    {
        $now   = new Carbon();
        $query = Url::select()
            ->where('expired_at', '<', $now)
        ;
        $count = $query->delete();
        if ($count) {
            $this->info("$count obsolete row(s) removed.");
        }
    }
}
