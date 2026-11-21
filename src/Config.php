<?php

declare(strict_types=1);

namespace Botforge;

use Dotenv\Dotenv;
use InvalidArgumentException;

class Config
{
  private array $settings;

  public function __construct(array $settings)
  {
    $this->settings = $settings;
  }

  public static function fromEnvironment(): self //.env رو میخونه
  {
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->safeLoad();  //اگر فایل envنباشه برنامه متوقف نمیشه

    $token = '';
    $secret = '';
    $debug = false;
    $language = 'fa';

    if (isset($_ENV['BOT_TOKEN'])) {
      $token = $_ENV['BOT_TOKEN'];
    }

    if (isset($_ENV['BOT_WEBHOOK_SECRET'])) {
      $secret = $_ENV['BOT_WEBHOOK_SECRET'];
    }

    if (isset($_ENV['BOT_DEBUG'])) {
      $debug = filter_var(
        $_ENV['BOT_DEBUG'],
        FILTER_VALIDATE_BOOLEAN
      );
    }

    if (isset($_ENV['BOT_LANGUAGE'])) {
      $language = $_ENV['BOT_LANGUAGE'];
    }

    return new self([
      'token' => $token,
      'webhook_secret' => $secret,
      'debug' => $debug,
      'language' => $language,
    ]);
  }

  public function get(string $key, mixed $default = null): mixed
  {
    if (!array_key_exists($key, $this->settings)) {
      return $default;
    }

    return $this->settings[$key];
  }

  public function set(string $key, mixed $value): void
  {
    $this->settings[$key] = $value;
  }

  public function has(string $key): bool
  {
    return array_key_exists($key, $this->settings);
  }

  public function all(): array
  {
    return $this->settings;
  }

  public function getToken(): string
  {
    $token = $this->get('token');

    if (!is_string($token) || $token === '') {
      throw new InvalidArgumentException(
        'Telegram bot token is missing.'
      );
    }

    return $token;
  }

  public function getWebhookSecret(): string
  {
    $secret = $this->get('webhook_secret');

    if (!is_string($secret) || $secret === '') {
      throw new InvalidArgumentException(
        'Webhook secret is missing.'
      );
    }

    return $secret;
  }
}
