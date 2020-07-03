<?php

declare(strict_types=1);

namespace App\Common;

use Monolog\Logger;
use App\Env;

/**
 * 基础设施类
 *
 * 这个类提供一个静态方法 getContainer() 用于在需要的时候获取全局的依赖注入容器，
 * 而不是使用全局变量去获取该容器。
 *
 * 我们的设计意图是在程序执行的非常早的时期，使用静态方法 setup() 设置好全局的依赖注入容器。
 * 不过，有些类是由依赖注入容器动态实例化的，此时，容器会将资深作为构造方法的参数传入，
 * 对此我们也进行了处理，确保传入的容器就是最初初始化的那个。同时允许不传入任何参数。
 * 经过这样的改造之后，这个类已经适合作为大多数类的基类来使用了。
 *
 * 作为基类时，这个类提供了若干方便调试的方法，包括输出到日志的 var_dump() 方法。
 *
 * 目前大概有两种类型的类不需要这个类做基类：
 *
 * -   小品类，一般是简单的数据模型，很可能实现了 JsonSerializable 接口
 * -   纯工具类，特点是（几乎）所有的方法都是静态方法
 */
class Base
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @return Logger
     */
    protected function getLogger(): Logger
    {
        if (null === $this->logger) {
            $logger    = Env::getLogger();
            if (!isset($this->loggerName)) {
                $name             = $logger->getName();
                $className        = get_class($this);
                $segments         = explode('\\', $className);
                $suffix           = $segments[count($segments) - 1];
                $this->loggerName = $name.'.'.$suffix;
            }
            // Replace with a new one.
            $this->logger = $logger->withName($this->loggerName);
        }

        return $this->logger;
    }

    /**
     * 记录 DEBUG 级别日志的快捷方式.
     *
     * @param string $message
     * @param array  $context
     */
    protected function debug($message, array $context = [])
    {
        $logger = $this->getLogger();
        $logger->debug($message, $context);
    }

    /**
     * 记录 INFO 级别日志的快捷方式.
     *
     * @param string $message
     * @param array  $context
     */
    protected function info($message, array $context = [])
    {
        $logger = $this->getLogger();
        $logger->info($message, $context);
    }

    /**
     * 记录 NOTICE 级别日志的快捷方式.
     *
     * @param string $message
     * @param array  $context
     */
    protected function notice($message, array $context = [])
    {
        $logger = $this->getLogger();
        $logger->notice($message, $context);
    }

    /**
     * 记录 WARNING 级别日志的快捷方式.
     *
     * @param string $message
     * @param array  $context
     */
    protected function warning($message, array $context = [])
    {
        $logger = $this->getLogger();
        $logger->warning($message, $context);
    }

    /**
     * 记录 ERROR 级别日志的快捷方式.
     *
     * @param string $message
     * @param array  $context
     */
    protected function error($message, array $context = [])
    {
        $logger = $this->getLogger();
        $logger->error($message, $context);
    }

    /**
     * 将 var_dump() 的结果写入 DEBUG 日志。
     *
     * @param mixed ...$values
     */
    protected function var_dump(...$values)
    {
        ob_start();
        var_dump(...$values);
        $contents = ob_get_contents();
        ob_end_clean();
        $this->debug($contents);
    }
}
