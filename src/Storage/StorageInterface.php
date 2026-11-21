<?php

declare(strict_types=1);

namespace Botforge\Storage;

interface StorageInterface
{
  public function get(string $key): mixed;

  public function set(string $key, mixed $value, int $ttl = 0): void;

  public function delete(string $key): void;

  public function deleteByPrefix(string $prefix): void;

  public function has(string $key): bool;

  public function clear(): void;
}
