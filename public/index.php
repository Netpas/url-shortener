<?php

declare(strict_types=1);

if ('cli-server' === PHP_SAPI) {
    // 如果 URI 最后一段包含点，比如 127.0.0.1 会丢失 PATH_INFO
    // https://bugs.php.net/bug.php?id=61286
    if (!isset($_SERVER['PATH_INFO'])) {
        $_SERVER['SCRIPT_NAME']     = '/index.php';
        $_SERVER['SCRIPT_FILENAME'] = $_SERVER['DOCUMENT_ROOT'].$_SERVER['SCRIPT_NAME'];
        $_SERVER['PATH_INFO']       = explode('?', $_SERVER['REQUEST_URI'])[0];
        $_SERVER['PHP_SELF']        = $_SERVER['SCRIPT_NAME'].$_SERVER['PATH_INFO'];
    }
    // 使用 PHP 内建的开发服务器时判断请求的路径是否是静态文件
    $_parsed = parse_url($_SERVER['REQUEST_URI']);
    if (is_file(__DIR__.$_parsed['path'])) {
        return false;
    }
} elseif ('fpm-fcgi' === PHP_SAPI) {
    // 解决 Apache FastCGI 下 Slim 无法正常工作的问题
    $_SERVER['SCRIPT_NAME'] = '/index.php';
}

// 加载 Autoload 机制
require __DIR__.'/../vendor/autoload.php';

// 设置时区
date_default_timezone_set('UTC');
// 本项目不需要 session 机制
//session_start();

$settings = require __DIR__.'/../etc/settings.php';
App\Env::setup(App\Env::WEB_SERVICE, $settings);
App\Env::getWebService()->run();
