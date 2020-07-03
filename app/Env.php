<?php

declare(strict_types=1);

namespace App;

use App\Common\Database;
use App\Common\ErrorHandler;
use App\Common\Logging;
use DI\ContainerBuilder;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Psr7\Factory\ResponseFactory;

class Env
{
    /** @var string 标识：Web 服务 */
    const WEB_SERVICE = 'WEB_SERVICE';
    /** @var string 标识：任务运行器 */
    const TASK_RUNNER = 'TASK_RUNNER';

    /** @var array 支持的进程类型 */
    const PROCESS_TYPES = [self::WEB_SERVICE, self::TASK_RUNNER];

    /** @var array 支持的运行时阶段 */
    const STAGES = ['DEVELOPMENT', 'TESTING', 'STAGING', 'PRODUCTION'];

    /** @var string 进程类型 */
    protected static $processType = '';

    /** @var string Stage */
    protected static $stage = '';

    /** @var array 配置信息 */
    protected static $settings = [];

    /** @var Logger 日志器 */
    protected static $logger;

    /** @var ContainerInterface 容器 */
    protected static $container;

    /** @var App */
    protected static $webService;

    /**
     * 启动脚本
     *
     * @param string $processType 进程类型，目前支持 WEB_SERVICE 和 TASK_RUNNER
     * @param array  $settings    配置信息
     */
    public static function setup(string $processType, array $settings)
    {
        if (!in_array($processType, static::PROCESS_TYPES)) {
            throw new \InvalidArgumentException('Unsupported process type.');
        }
        static::$processType = $processType;
        static::$settings    = $settings;
        static::setStage(APP_STAGE);
        static::buildContainer();
        static::makeLogger();
        if (self::WEB_SERVICE === $processType) {
            static::bootstrapWebService();
        }
    }

    /**
     * 启动关系数据库相关设置
     */
    public static function bootstrapDatabase()
    {
        Database::setup(static::$settings);
    }

    /**
     * 返回进程类型
     */
    public static function getProcessType(): string
    {
        return static::$processType;
    }

    /**
     * 返回 settings 配置
     */
    public static function getSettings(): array
    {
        return static::$settings;
    }

    /**
     * 获取 stage
     */
    public static function getStage(): string
    {
        return static::$stage;
    }

    /**
     * 返回全局的日志器
     */
    public static function getLogger(): ?Logger
    {
        return static::$logger;
    }

    /**
     * 返回全局的依赖注入容器
     */
    public static function getContainer(): ?ContainerInterface
    {
        return static::$container;
    }

    /**
     * 返回 Web 服务应用程序
     */
    public static function getWebService(): ?App
    {
        return static::$webService;
    }

    /**
     * 设置 stage
     */
    protected static function setStage(string $stage)
    {
        if (!in_array($stage, static::STAGES)) {
            throw new \InvalidArgumentException("Unsupported stage '$stage'.");
        }
        static::$stage = $stage;
    }

    /**
     * 构建依赖注入容器
     */
    protected static function buildContainer()
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions(__DIR__.'/dependencies.php');
        static::$container = $builder->build();
    }

    /**
     * 创建日志器
     */
    protected static function makeLogger()
    {
        $defaultSettings = static::$settings['logger']['defaultSettings'] ?? [];
        if (self::WEB_SERVICE === static::$processType) {
            $key = 'webService';
        } else {
            // self::TASK_RUNNER === static::$processType
            $key = 'taskRunner';
        }
        $settings       = array_merge($defaultSettings, static::$settings[$key]['logger']);
        static::$logger = Logging::make($settings);
    }

    /**
     * 完成 Web Service 必要的启动步骤
     */
    protected static function bootstrapWebService()
    {
        $factory = new ResponseFactory();
        $app     = AppFactory::create($factory);

        // Slim 4 将路由组件设计为中间件来解耦与 FastRoute 的关系，
        // 它应该是最贴近 $app 的中间件：
        $app->addRoutingMiddleware();
        // 自动处理 JSON 和 XML 格式的请求
        $app->addBodyParsingMiddleware();
        // Add MethodOverride middleware
        $methodOverrideMiddleware = new MethodOverrideMiddleware();
        $app->add($methodOverrideMiddleware);

        require __DIR__.'/middleware.php';

        // Slim 4 将错误处理也变成了中间件，
        // 它能抓到所有在它之前注册的中间件的错误：
        $errorMiddleware = $app->addErrorMiddleware(true, true, true);
        $errorHandler    = new ErrorHandler($app->getCallableResolver(), $app->getResponseFactory());
        $errorMiddleware->setDefaultErrorHandler($errorHandler);

        require __DIR__.'/routes.php';

        static::$webService = $app;
    }
}
