<?php

declare(strict_types=1);

namespace App\Business\Model\Shortener;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /** @var string */
    protected $connection = 'shortener';
    /** @var string */
    protected $table = 'shortener.setting';

    /** @var string */
    protected $primaryKey = 'key';
    /** @var bool */
    public $incrementing = false;
    /** @var string */
    protected $keyType = 'string';

    /** @var string|null */
    const CREATED_AT = null;
    /** @var string|null */
    const UPDATED_AT = null;

    /**
     * Get a setting value by key.
     */
    public static function getValueByKey(string $key, string $default = null): ?string
    {
        $item = Setting::firstWhere('key', $key);
        if (null === $item) {
            return $default;
        }

        return $item->value;
    }
}
