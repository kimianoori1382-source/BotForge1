<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Botforge\Api\HttpClient;
use Botforge\Api\TelegramApiClient;
use Botforge\Bot;
use Botforge\Config;
use Botforge\Storage\FileStorage;
use GuzzleHttp\Client;

$config = Config::fromEnvironment();

$guzzle = new Client();
$http = new HttpClient($guzzle);

$api = new TelegramApiClient(
    $http,
    $config->getToken()
);

$storage = new FileStorage(__DIR__ . '/storage');

$bot = new Bot(
    $config,
    $api,
    $storage
);

$bot->command('start', function ($context) {
    $context->reply('Welcome to my bot!');
});

$bot->run();