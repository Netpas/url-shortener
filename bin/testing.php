#!/usr/bin/env php
<?php

// 加载 Autoload 机制

require __DIR__.'/../vendor/autoload.php';

// 设置时区
date_default_timezone_set('UTC');

// 初始化应用程序
$settings = require __DIR__.'/../app/settings.php';
App\Env::setup(App\Env::TASK_RUNNER, $settings);

App\Env::bootstrapDatabase();

// 1

// $apps = App\Business\Model\Shortener\App::all();
// foreach ($apps as $app) {
//     var_dump($app->name);
//     var_dump($app->created);
//     var_dump($app->profile);
//     var_dump($app->uuid);
//     var_dump($app->key);
// }

// $app = App\Business\Model\Shortener\App::create('Testing App');
// var_dump($app->name);
// var_dump($app->created);
// var_dump($app->profile);
// var_dump($app->uuid);
// var_dump($app->key);

// 2

// $uuid = '66523f51-3b16-4c0a-ad82-4ec47340a370';
// $app  = App\Business\Model\Shortener\App::where('uuid', $uuid)->first();
// var_dump($app);

// $item = App\Business\Model\Shortener\Url::create('https://www.google.com/', $app);
// var_dump($item->token);

// 3

// $token = 'PtwzKqZ';
// $url   = App\Business\Model\Shortener\Url::firstWhere('token', $token);
// var_dump($url->target);
// var_dump($url->app->name);

// 4

// $uuid   = '66523f51-3b16-4c0a-ad82-4ec47340a370';
// $key    = '0vNdKguL8obkb8MVvyjgHYQn8pq8DPY4';
// $ts     = time();
// $digest = hash('sha256', "$uuid$ts$key");
// $s      = new App\Business\Shortener();
// $app    = $s->getAppByAuth($uuid, $ts, $digest);
// var_dump($app->uuid);

// 5
$service = new App\Business\Shortener();
$service->clearObsoletes();
