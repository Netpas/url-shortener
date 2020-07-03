#!/usr/bin/env php
<?php

// 加载 Autoload 机制

require __DIR__.'/../vendor/autoload.php';

// 设置时区
date_default_timezone_set('UTC');

// 初始化应用程序
$settings = require __DIR__.'/../etc/settings.php';
App\Env::setup(App\Env::TASK_RUNNER, $settings);
$runner = new App\Common\TaskRunner();
$runner->run();
