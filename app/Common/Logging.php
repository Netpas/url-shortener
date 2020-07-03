<?php

declare(strict_types=1);

namespace App\Common;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Logger;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\UidProcessor;

/**
 * 一组与日志处理相关的操作
 */
class Logging
{
    /** @var array */
    protected static $processors;

    /**
     * 返回符合约定的日志器名称。
     *
     * 返回常量 `APP_NAME` 的值或者字符串 noname。
     */
    public static function getDefaultName(): string
    {
        return @constant('APP_NAME') ?? 'noname';
    }

    /**
     * 返回默认的日志等级。
     *
     * 如果定义了常量 DEBUG 且被认为是真值，则返回 Logger::DEBUG，否则返回 Logger::INFO 。
     */
    public static function getDefaultLevel(): int
    {
        $debug = boolval(@constant('DEBUG'));

        return $debug ? Logger::DEBUG : Logger::INFO;
    }

    /**
     * 返回默认的日志格式。
     *
     * @param mixed $withExtra 是否包含 extra 部分
     */
    public static function getDefaultFormat($withExtra = true): string
    {
        $result = '[%datetime%] [%channel%] [%level_name%] %message% %context%';
        if ($withExtra) {
            $result .= ' %extra%';
        }
        $result .= PHP_EOL;

        return $result;
    }

    /**
     * 返回默认的日志日期格式。
     *
     * 日期格式总是符合 ISO-8601 规范，当系统时区是 UTC 时，采用的是所谓的
     * ISO-8601 Zulu 格式，能够节约几个字节。
     */
    public static function getDefaultDateFormat(): string
    {
        if ('UTC' === date_default_timezone_get()) {
            $result = 'Y-m-d\TH:i:s\Z';
        } else {
            $result = 'Y-m-d\TH:i:sP';
        }

        return $result;
    }

    /**
     * 创建并返回关联了必要的 handler 和 formatter 的日志器。
     *
     * 参数 $settings 目前支持如下 key：
     *
     * - name: 日期器名称
     * - level: 日志等级
     * - format: 日志格式
     * - dateFormat: 日期格式
     * - stderr: 是否输出日志到 stderr
     * - path 与 filename: 需成对出现，指定日志文件的目录和文件名
     * - syslogUdpHost, syslogUdpPort 与 syslogUdpTag: 若存在 syslogUdpHost 则启用
     *   SyslogUDP 机制，默认的 syslogUdpPort 为 514，默认的 syslogUdpTag 为 php
     *
     * 生成的日志器还会额外配好 UidProcessor 预处理器（在 extra 部分显示信息）。
     *
     * @param array $settings 配置参数
     */
    public static function make(array $settings): Logger
    {
        if (null === static::$processors) {
            static::$processors   = [];
            static::$processors[] = new UidProcessor();
            static::$processors[] = new ProcessIdProcessor();
            static::$processors[] = new MemoryUsageProcessor(true, false);
        }
        $name       = $settings['name']       ?? self::getDefaultName();
        $level      = $settings['level']      ?? self::getDefaultLevel();
        $format     = $settings['format']     ?? self::getDefaultFormat();
        $dateFormat = $settings['dateFormat'] ?? self::getDefaultDateFormat();
        // Instantiate the Logger.
        $logger = new Logger($name);
        foreach (static::$processors as $processor) {
            $logger->pushProcessor($processor);
        }
        // 同时存在 path 和 filename，则启用文件系统日志
        if (isset($settings['path']) && isset($settings['filename'])) {
            $path    = $settings['path'].'/'.$settings['filename'];
            $handler = new StreamHandler($path, $level);
            $logger->pushHandler($handler);
            $formatter = new LineFormatter($format, $dateFormat, false, false);
            $handler->setFormatter($formatter);
        }
        // 存在 syslogUdpHost 则启用 Syslog UDP 支持
        if (isset($settings['syslogUdpHost'])) {
            $host    = $settings['syslogUdpHost'];
            $port    = $settings['syslogUdpPort'] ?? 514;
            $tag     = $settings['syslogUdpTag']  ?? 'php';
            $handler = new SyslogUdpHandler($host, $port, LOG_USER, $level, true, $tag);
            $logger->pushHandler($handler);
            $formatter = new LineFormatter($format, $dateFormat, false, false);
            $handler->setFormatter($formatter);
        }
        // 要求同时写入 stderr 的情况
        $stderr = $settings['stderr'] ?? false;
        if ($stderr) {
            $path    = 'php://stderr';
            $handler = new StreamHandler($path, $level);
            $logger->pushHandler($handler);
            $formatter = new LineFormatter($format, $dateFormat, false, false);
            $handler->setFormatter($formatter);
        }

        return $logger;
    }
}
