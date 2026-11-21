<?php

declare(strict_types=1);

namespace Botforge\Storage;

use JsonException;
use RuntimeException;

class FileStorage implements StorageInterface
{
  private string $directory;

  public function __construct(string $directory)
  {
    $this->directory = rtrim($directory, DIRECTORY_SEPARATOR);

    if ($this->directory === '') {
      $this->directory = DIRECTORY_SEPARATOR;
    }

    if (!is_dir($this->directory)) {
      if (!mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
        throw new RuntimeException('Could not create storage directory.');
      }
    }
  }

  public function get(string $key): mixed
  {
    $record = $this->read($key);

    if (!array_key_exists('value', $record)) {
      return null;
    }

    return $record['value'];
  }

  public function set(string $key, mixed $value, int $ttl = 0): void
  {
    $expiresAt = null;

    if ($ttl > 0) {
      $expiresAt = time() + $ttl;
    }

    $record = [
      'value' => $value,
      'expires_at' => $expiresAt,
    ];

    try {
      $content = json_encode($record, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
      throw new RuntimeException('Could not encode storage data.', 0, $exception);
    }

    $result = file_put_contents(
      $this->getPath($key),
      $content,
      LOCK_EX
    );

    if ($result === false) {
      throw new RuntimeException('Could not write storage data.');
    }
  }

  public function delete(string $key): void
  {
    $path = $this->getPath($key);

    if (is_file($path) && !unlink($path)) {
      throw new RuntimeException('Could not delete storage data.');
    }
  }

  public function deleteByPrefix(string $prefix): void
  {
    $files = glob($this->directory . DIRECTORY_SEPARATOR . '*.json');

    if ($files === false) {
      throw new RuntimeException('Could not read storage directory.');
    }

    foreach ($files as $file) {
      $content = file_get_contents($file);

      if ($content === false) {
        throw new RuntimeException('Could not read storage data.');
      }

      try {
        $record = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
      } catch (JsonException $exception) {
        throw new RuntimeException('Could not decode storage data.', 0, $exception);
      }

      if (!is_array($record) || !isset($record['key'])) {
        continue;
      }

      if (str_starts_with($record['key'], $prefix)) {
        if (!unlink($file)) {
          throw new RuntimeException('Could not delete storage data.');
        }
      }
    }
  }

  public function has(string $key): bool
  {
    $record = $this->read($key);

    return array_key_exists('value', $record);
  }

  public function clear(): void
  {
    $files = glob($this->directory . DIRECTORY_SEPARATOR . '*.json');

    if ($files === false) {
      throw new RuntimeException('Could not read storage directory.');
    }

    foreach ($files as $file) {
      if (!unlink($file)) {
        throw new RuntimeException('Could not clear storage data.');
      }
    }
  }

  private function read(string $key): array
  {
    $path = $this->getPath($key);

    if (!is_file($path)) {
      return [];
    }

    $content = file_get_contents($path);

    if ($content === false) {
      throw new RuntimeException('Could not read storage data.');
    }

    try {
      $record = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
      throw new RuntimeException('Could not decode storage data.', 0, $exception);
    }

    if (!is_array($record)) {
      throw new RuntimeException('Invalid storage data.');
    }

    if (
      array_key_exists('expires_at', $record)
      && $record['expires_at'] !== null
      && $record['expires_at'] <= time()
    ) {
      $this->delete($key);

      return [];
    }

    return $record;
  }

  private function getPath(string $key): string
  {
    return $this->directory
      . DIRECTORY_SEPARATOR
      . hash('sha256', $key)
      . '.json';
  }
}
