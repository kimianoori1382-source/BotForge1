<?php

declare(strict_types=1);

namespace Botforge\Storage;

use JsonException;
use Redis;
use RuntimeException;

class RedisStorage implements StorageInterface
{
  private Redis $redis;

  private string $prefix;

  public function __construct(Redis $redis, string $prefix = 'botforge:')
  {
    $this->redis = $redis;
    $this->prefix = $prefix;
  }

  public function get(string $key): mixed
  {
    $value = $this->redis->get($this->prefix . $key);

    if ($value === false) {
      return null;
    }

    try {
      return json_decode(
        $value,
        true,
        512,
        JSON_THROW_ON_ERROR
      );
    } catch (JsonException $exception) {
      throw new RuntimeException(
        'Could not decode Redis data.',
        0,
        $exception
      );
    }
  }

  public function set(string $key, mixed $value, int $ttl = 0): void
  {
    try {
      $content = json_encode($value, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
      throw new RuntimeException(
        'Could not encode Redis data.',
        0,
        $exception
      );
    }

    $redisKey = $this->prefix . $key;

    if ($ttl > 0) {
      $result = $this->redis->setex($redisKey, $ttl, $content);
    } else {
      $result = $this->redis->set($redisKey, $content);
    }

    if ($result === false) {
      throw new RuntimeException('Could not save Redis data.');
    }
  }

  public function delete(string $key): void
  {
    $this->redis->del($this->prefix . $key);
  }

  public function deleteByPrefix(string $prefix): void
  {
    $iterator = null;
    $pattern = $this->prefix . $prefix . '*';

    do {
      $keys = $this->redis->scan($iterator, $pattern, 100);

      if (is_array($keys) && !empty($keys)) {
        $this->redis->del($keys);
      }
    } while ($iterator !== 0);
  }

  public function has(string $key): bool
  {
    return $this->redis->exists($this->prefix . $key) > 0;
  }

  public function clear(): void
  {
    $iterator = null;
    $pattern = $this->prefix . '*';

    do {
      $keys = $this->redis->scan($iterator, $pattern, 100);

      if (is_array($keys) && !empty($keys)) {
        $this->redis->del($keys);
      }
    } while ($iterator !== 0);
  }
}
