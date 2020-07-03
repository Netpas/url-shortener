<?php

declare(strict_types=1);

namespace App\Business\Model\Shortener;

use App\Business\Util;
use Illuminate\Database\Eloquent\Model;

/**
 * App
 */
class App extends Model
{
    /** @var string */
    protected $connection = 'shortener';
    /** @var string */
    protected $table = 'shortener.app';

    /** @var string|null */
    const CREATED_AT = 'created';
    /** @var string|null */
    const UPDATED_AT = 'updated';

    /** @var array attribute type casts */
    protected $casts = [
        'disabled' => 'boolean',
        'profile'  => 'array',
    ];

    /** @var array default attribute values */
    protected $attributes = [
        'profile' => '{"version":"1.0.0"}',
    ];

    /**
     * One to many relationship
     */
    public function urls()
    {
        return $this->hasMany(Url::class, 'app_id', 'id');
    }

    /**
     * 创建一个新的 App 对象并返回
     */
    public static function create(string $name): App
    {
        $item           = new App();
        $item->uuid     = Util::makeUuid4();
        $item->name     = $name;
        $item->disabled = false;
        $item->key      = Util::makeKey(32);
        $item->save();

        return $item;
    }

    /**
     * 根据 UUID 获取 App 对象
     */
    public static function byUuid(string $uuid): ?App
    {
        $item = App::firstWhere('uuid', $uuid);

        return $item;
    }
}
