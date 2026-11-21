<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Botforge\Api\HttpClient;
use Botforge\Api\TelegramApiClient;
use Botforge\Bot;
use Botforge\Config;
use Botforge\Context\Context;
use Botforge\Storage\FileStorage;
use GuzzleHttp\Client;

$root = dirname(__DIR__, 2);

$config = Config::fromEnvironment();

$guzzle = new client([
    'proxy' => [
        'http' => 'socks5h://127.0.0.1:10808',
        'https' => 'socks5h://127.0.0.1:10808',
    ],
]);
$http = new HttpClient($guzzle);

$api = new TelegramApiClient(
  $http,
  $config->getToken()
);

$storage = new FileStorage($root . '/storage');

$bot = new Bot(
  $config,
  $api,
  $storage
);

$bot->command('start', function (Context $ctx): void {
  $ctx->reply(
    'Welcome to BotForge Demo Bot!'
  );
});

$bot->textRegex('/^.+$/s', function (Context $ctx): void {
  $message = $ctx->getMessage();

  $ctx->reply(
    'You said: ' . $message->text
  );
});

$bot->run();
