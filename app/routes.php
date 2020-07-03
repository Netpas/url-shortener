<?php

declare(strict_types=1);

use Slim\Routing\RouteCollectorProxy;

// ---------------------------------------------------------------------
// URL 路由表配置.
// ---------------------------------------------------------------------
// 进一步的信息请参考： https://www.slimframework.com/docs/v4/objects/routing.html

/* @var \Slim\App $app */

$app->group('/_', function (RouteCollectorProxy $group) {
    $group->get('/meta', '\App\View\Base:meta');
    $group->post('/task', '\App\View\Task:task');
    $group->any('/phpinfo', '\App\View\Base:phpInfo');
});

$app->get('/{token:[A-Za-z0-9]+}', '\App\View\Service:fetch');
$app->post('/__API__/', '\App\View\Service:submit');
