<?php

declare(strict_types=1);

namespace App\Business\Model\Shortener;

use App\Business\Util;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use PDOException;

/**
 * URL
 */
class Url extends Model
{
    /** @var string */
    protected $connection = 'shortener';
    /** @var string */
    protected $table = 'shortener.url';

    /** @var string|null */
    const CREATED_AT = 'created';
    /** @var string|null */
    const UPDATED_AT = null;

    /** @var array attribute type casts */
    protected $casts = [
        'expired_at' => 'datetime',
        'disabled'   => 'boolean',
        'profile'    => 'array',
    ];

    /** @var array default attribute values */
    protected $attributes = [
        'profile' => '{"version":"1.0.0"}',
    ];

    /**
     * many to one relationship
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    /**
     * Is this URL expired?
     */
    public function isExpired(): bool
    {
        $now      = new Carbon();
        $deadline = $this->expired_at;

        return $now > $deadline;
    }

    /**
     * 创建新的 Url 对象并返回
     *
     * @param Carbon $expiredAt
     */
    public static function create(string $target, App $app, ?Carbon $expiredAt = null, bool $duplicable = false): Url
    {
        if (null === $expiredAt) {
            $expiredAt = new Carbon();
            $expiredAt = $expiredAt->addCentury();
        }
        if (!$duplicable) {
            $item = Url::where('target', $target)
            ->where('app_id', $app->id)
            ->where('disabled', false)
            ->first();
        } else {
            $item = null;
        }
        if (null === $item) {
            $item             = new Url();
            $item->app_id     = $app->id;
            $item->target     = $target;
            $item->expired_at = $expiredAt;
            $pending          = true;
            while ($pending) {
                $keyLength   = 7;
                $item->token = Util::makeKey($keyLength);
                try {
                    $item->save();
                    $pending = false;
                } catch (PDOException $err) {
                    if (!strpos($err->getMessage(), '1062 Duplicate entry')) {
                        throw $err;
                    }
                }
            }
        }

        return $item;
    }

    /**
     * 根据 token 获取 URL
     */
    public static function byToken(string $token): ?Url
    {
        $item = Url::firstWhere('token', $token);

        return $item;
    }
}
