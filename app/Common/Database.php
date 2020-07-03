<?php

declare(strict_types=1);

namespace App\Common;

use DateTimeZone;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Events\Dispatcher;
use function JmesPath\search as jmesPath;
use Monolog\Logger;

/**
 * 启动阶段与数据库相关的一些代码。
 */
class Database
{
    /** @var array */
    protected static $settings = [];

    /** @var Capsule */
    protected static $capsule;

    /** @var Logger */
    protected static $logger;

    /**
     * 返回全局的数据库管理器实例
     */
    public static function getCapsule(): ?Capsule
    {
        return static::$capsule;
    }

    /**
     * 返回查询日志器实例
     */
    public static function getLogger(): ?Logger
    {
        return static::$logger;
    }

    /**
     * 返回设置中指定的时区
     *
     * MySQL 中的日期时间不携带时区信息，因此我们需要有地方告诉程序我们在
     * MySQL 中处理时间时，用的是哪个时区。
     */
    public static function getTimezone(): DateTimeZone
    {
        $timezone = jmesPath('timezone', static::$settings) ?? date_default_timezone_get();

        return new DateTimeZone($timezone);
    }

    /**
     * Make a "Capsule" manager instance.
     *
     * So that we can use Laravel database layer standalone. See also:
     * https://stackoverflow.com/questions/26083175/can-i-use-laravels-database-layer-standalone
     */
    public static function setup(array $settings)
    {
        // 全局的数据库设置
        static::$settings = $settings;

        // 实例化数据库管理器
        $settings    = jmesPath('database', $settings)        ?? [];
        $defaults    = jmesPath('defaultSettings', $settings) ?? [];
        $connections = jmesPath('connections', $settings)     ?? [];
        $capsule     = new Capsule();
        foreach ($connections as $name => $config) {
            $config = array_merge($defaults, $config);
            $capsule->addConnection($config, $name);
        }
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        static::$capsule = $capsule;

        // 如果需要，启用查询日志
        if (jmesPath('enableQueryLog', $settings) ?? false) {
            static::enableQueryLog();
        }
    }

    /**
     * 绑定日志器和 Illuminate 库使用的数据库连接对象用于记录查询日志。
     */
    protected static function enableQueryLog()
    {
        // 启用查询日志
        $defaults       = jmesPath('logger.defaultSettings', static::$settings) ?? [];
        $settings       = jmesPath('database.logger', static::$settings)        ?? [];
        $settings       = array_merge($defaults, $settings);
        $logger         = Logging::make($settings);
        static::$logger = $logger;

        // 对每一个连接绑定事件
        $connections = jmesPath('database.connections', static::$settings) ?? [];
        foreach ($connections as $name => $config) {
            $connection = static::$capsule->connection($name);
            // Should not share a event dispatcher with other connections.
            $connection->setEventDispatcher(new Dispatcher());
            $connection->listen(function (QueryExecuted $queryExecuted) use ($logger) {
                $context = [
                    'parameters'      => $queryExecuted->bindings,
                    'time'            => intval($queryExecuted->time),
                    'connection_name' => $queryExecuted->connectionName,
                ];
                $logger->info($queryExecuted->sql, $context);
            });
        }
    }
}
