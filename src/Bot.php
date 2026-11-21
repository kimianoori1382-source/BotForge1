<?php

declare(strict_types=1);

namespace Botforge;

use Botforge\Api\TelegramApiClient;
use Botforge\Container\Container;
use Botforge\Container\ServiceProviderInterface;
use Botforge\Context\Context;
use Botforge\Entities\Update;
use Botforge\Middleware\MiddlewareInterface;
use Botforge\Routing\Dispatcher;
use Botforge\Routing\Router;
use Botforge\Storage\StorageInterface;
use RuntimeException;

class Bot
{
  private Config $config;

  private TelegramApiClient $api;

  private Container $container;

  private Router $router;

  private Dispatcher $dispatcher;

  private StorageInterface $storage;

  public function __construct(
    Config $config,
    TelegramApiClient $api,
    StorageInterface $storage
  ) {
    $this->config = $config;
    $this->api = $api;
    $this->storage = $storage;

    $this->container = new Container();
    $this->router = new Router();

    $this->dispatcher = new Dispatcher(
      $this->router,
      $this->container
    );

    $this->container->instance(Config::class, $this->config);
    $this->container->instance(TelegramApiClient::class, $this->api);
    $this->container->instance(StorageInterface::class, $this->storage);
    $this->container->instance(Container::class, $this->container);
    $this->container->instance(Router::class, $this->router);
    $this->container->instance(Dispatcher::class, $this->dispatcher);
    $this->container->instance(Bot::class, $this);
  }

  public function getConfig(): Config
  {
    return $this->config;
  }

  public function getApi(): TelegramApiClient
  {
    return $this->api;
  }

  public function getContainer(): Container
  {
    return $this->container;
  }

  public function getRouter(): Router
  {
    return $this->router;
  }

  public function getDispatcher(): Dispatcher
  {
    return $this->dispatcher;
  }

  public function getStorage(): StorageInterface
  {
    return $this->storage;
  }

  public function command(string $name, callable $handler): self
  {
    $this->router->command($name, $handler);

    return $this;
  }

  public function text(string $text, callable $handler): self
  {
    $this->router->text($text, $handler);

    return $this;
  }

  public function textRegex(string $pattern, callable $handler): self
  {
    $this->router->textRegex($pattern, $handler);

    return $this;
  }

  public function textContains(string $text, callable $handler): self
  {
    $this->router->textContains($text, $handler);

    return $this;
  }

  public function callback(string $data, callable $handler): self
  {
    $this->router->callback($data, $handler);

    return $this;
  }

  public function callbackRegex(string $pattern, callable $handler): self
  {
    $this->router->callbackRegex($pattern, $handler);

    return $this;
  }

  public function inlineQuery(string $pattern, callable $handler): self
  {
    $this->router->inlineQuery($pattern, $handler);

    return $this;
  }

  public function onMessageType(string $messageType, callable $handler): self
  {
    $this->router->messageType($messageType, $handler);

    return $this;
  }

  public function chatMember(callable $handler): self
  {
    $this->router->chatMember($handler);

    return $this;
  }

  public function myChatMember(callable $handler): self
  {
    $this->router->myChatMember($handler);

    return $this;
  }

  public function updateType(string $type, callable $handler): self
  {
    $this->router->updateType($type, $handler);

    return $this;
  }

  public function fallback(callable $handler): self
  {
    $this->router->fallback($handler);

    return $this;
  }

  public function middleware(MiddlewareInterface $middleware): self
  {
    $this->dispatcher->addMiddleware($middleware);

    return $this;
  }

  public function group(string $prefix, callable $callback): self
  {
    $this->router->group($prefix, $callback);

    return $this;
  }

  public function registerProvider(ServiceProviderInterface $provider): self
  {
    $this->container->registerProvider($provider);

    return $this;
  }

  public function handleUpdate(array $data): void
  {
    $update = new Update($data);

    $context = new Context(
      $this->api,
      $update,
      $this->storage
    );

    if ($update->hasMessage) {
      $message = $update->message;

      if (isset($message->text)) {
        $text = $message->text;
        $matched = false;

        if (strpos($text, '/') === 0) {
          $matched = $this->dispatcher->dispatch(
            'command',
            $text,
            $context
          );
        } else {
          $matched = $this->dispatcher->dispatch(
            'text',
            $text,
            $context
          );

          if (!$matched) {
            $matched = $this->dispatcher->dispatch(
              'regex',
              $text,
              $context
            );
          }

          if (!$matched) {
            $matched = $this->dispatcher->dispatch(
              'contains',
              $text,
              $context
            );
          }
        }

        if (!$matched) {
          $this->dispatcher->dispatch(
            'fallback',
            $text,
            $context
          );
        }

        return;
      }

      $messageType = '';

      $types = [
        'photo',
        'video',
        'animation',
        'document',
        'audio',
        'voice',
        'video_note',
        'contact',
        'location',
        'venue',
        'sticker',
        'dice',
        'game',
      ];

      foreach ($types as $type) {
        if (isset($data['message'][$type])) {
          $messageType = $type;
          break;
        }
      }

      $matched = false;

      if ($messageType !== '') {
        $matched = $this->dispatcher->dispatch(
          'message_type',
          $messageType,
          $context
        );
      }

      if (!$matched) {
        $this->dispatcher->dispatch(
          'fallback',
          $messageType,
          $context
        );
      }

      return;
    }

    if ($update->hasCallbackQuery) {
      $callbackData = $update->callbackQuery->data;

      $matched = $this->dispatcher->dispatch(
        'callback',
        $callbackData,
        $context
      );

      if (!$matched) {
        $matched = $this->dispatcher->dispatch(
          'callback_regex',
          $callbackData,
          $context
        );
      }

      if (!$matched) {
        $this->dispatcher->dispatch(
          'fallback',
          $callbackData,
          $context
        );
      }

      return;
    }

    if ($update->hasInlineQuery) {
      $query = $update->inlineQuery->query;

      $matched = $this->dispatcher->dispatch(
        'inline_query',
        $query,
        $context
      );

      if (!$matched) {
        $this->dispatcher->dispatch(
          'fallback',
          $query,
          $context
        );
      }

      return;
    }

    if (isset($data['chat_member'])) {
      $this->dispatcher->dispatch(
        'chat_member',
        'chat_member',
        $context
      );

      return;
    }

    if (isset($data['my_chat_member'])) {
      $this->dispatcher->dispatch(
        'my_chat_member',
        'my_chat_member',
        $context
      );

      return;
    }

    $knownTypes = [
      'update_id',
      'message',
      'edited_message',
      'channel_post',
      'edited_channel_post',
      'business_connection',
      'business_message',
      'edited_business_message',
      'deleted_business_messages',
      'callback_query',
      'inline_query',
      'chosen_inline_result',
      'shipping_query',
      'pre_checkout_query',
      'poll',
      'poll_answer',
      'chat_member',
      'my_chat_member',
      'chat_join_request',
      'message_reaction',
      'message_reaction_count',
    ];

    foreach ($data as $type => $value) {
      if (in_array($type, $knownTypes, true)) {
        if (is_array($value)) {
          $this->dispatcher->dispatch(
            $type,
            $type,
            $context
          );

          return;
        }

        continue;
      }

      if (is_array($value)) {
        $this->dispatcher->dispatch(
          $type,
          $type,
          $context
        );

        return;
      }
    }
  }


  public function polling(): void
  {
    $offset = 0;

    while (true) {
      $updates = $this->api->getUpdates([
        'offset' => $offset,
        'timeout' => 30,
      ]);

      foreach ($updates as $update) {
        if (!is_array($update)) {
          continue;
        }

        if (!isset($update['update_id'])) {
          continue;
        }

        $this->handleUpdate($update);

        $offset = (int) $update['update_id'] + 1;
      }
    }
  }


  public function webhook(array $data, string $secret): void
  {
    $this->handleWebhook($data, $secret);
  }

  public function handleWebhook(array $data, string $secret): void
  {
    $expectedSecret = $this->config->getWebhookSecret();

    if (
      !is_string($expectedSecret)
      || $expectedSecret === ''
      || !hash_equals($expectedSecret, $secret)
    ) {
      throw new RuntimeException('Invalid webhook secret');
    }

    $this->handleUpdate($data);
  }

  public function run(): void
  {
    $this->polling();
  }

  public function setWebhook(string $url, string $secret): array
  {
    return $this->api->setWebhook([
      'url' => $url,
      'secret_token' => $secret,
    ]);
  }

  public function deleteWebhook(): array
  {
    return $this->api->deleteWebhook();
  }

  public function getWebhookInfo(): array
  {
    return $this->api->getWebhookInfo();
  }
}
