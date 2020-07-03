<?php

declare(strict_types=1);

// ---------------------------------------------------------------------
// 初始化中间件
// ---------------------------------------------------------------------
// Slim 4 必要的中间件(路由、错误处理)已经在 Env.php 中加载，这里无需处理。
// 越早定义的中间件越贴近实际的应用程序，即它更迟被调用并更早结束处理。
// 进一步的信息请参考： https://www.slimframework.com/docs/v4/concepts/middleware.html

/* @var \Slim\App $app */

// // 用来返回人性化的 JSON 格式的中间件。
// $app->add(\App\Common\Middleware\HumanFriendly::class);
