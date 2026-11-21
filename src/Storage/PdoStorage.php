<?php

declare(strict_types=1);

namespace Botforge\Storage;

use JsonException;
use PDO;
use RuntimeException;
use Throwable;

class PdoStorage implements StorageInterface
{
  private PDO $pdo;

  private string $prefix;

  public function __construct(PDO $pdo, string $prefix = 'botforge:')
  {
    $this->pdo = $pdo;
    $this->prefix = $prefix;

    $this->pdo->exec(
      'CREATE TABLE IF NOT EXISTS botforge_storage (
                storage_key VARCHAR(255) PRIMARY KEY,
                storage_value TEXT NOT NULL,
                expires_at BIGINT NULL
            )'
    );
  }

  public function get(string $key): mixed
  {
    $record = $this->find($key);

    if (empty($record)) {
      return null;
    }

    try {
      return json_decode(
        $record['storage_value'],
        true,
        512,
        JSON_THROW_ON_ERROR
      );
    } catch (JsonException $exception) {
      throw new RuntimeException(
        'Could not decode database data.',
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
        'Could not encode database data.',
        0,
        $exception
      );
    }

    $expiresAt = null;

    if ($ttl > 0) {
      $expiresAt = time() + $ttl;
    }

    $storageKey = $this->prefix . $key;

    $this->pdo->beginTransaction();

    try {
      $delete = $this->pdo->prepare(
        'DELETE FROM botforge_storage WHERE storage_key = :key'
      );

      $delete->execute(['key' => $storageKey]);

      $insert = $this->pdo->prepare(
        'INSERT INTO botforge_storage
                (storage_key, storage_value, expires_at)
                VALUES (:key, :value, :expires_at)'
      );

      $insert->execute([
        'key' => $storageKey,
        'value' => $content,
        'expires_at' => $expiresAt,
      ]);

      $this->pdo->commit();
    } catch (Throwable $exception) {
      if ($this->pdo->inTransaction()) {
        $this->pdo->rollBack();
      }

      throw $exception;
    }
  }

  public function delete(string $key): void
  {
    $statement = $this->pdo->prepare(
      'DELETE FROM botforge_storage WHERE storage_key = :key'
    );

    $statement->execute([
      'key' => $this->prefix . $key,
    ]);
  }

  public function deleteByPrefix(string $prefix): void
  {
    $statement = $this->pdo->prepare(
      'DELETE FROM botforge_storage
             WHERE storage_key LIKE :prefix'
    );

    $statement->execute([
      'prefix' => $this->escapeLike($this->prefix . $prefix) . '%',
    ]);
  }

  public function has(string $key): bool
  {
    return !empty($this->find($key));
  }

  public function clear(): void
  {
    $statement = $this->pdo->prepare(
      'DELETE FROM botforge_storage
             WHERE storage_key LIKE :prefix'
    );

    $statement->execute([
      'prefix' => $this->escapeLike($this->prefix) . '%',
    ]);
  }

  private function find(string $key): array
  {
    $statement = $this->pdo->prepare(
      'SELECT storage_value, expires_at
             FROM botforge_storage
             WHERE storage_key = :key'
    );

    $statement->execute([
      'key' => $this->prefix . $key,
    ]);

    $record = $statement->fetch(PDO::FETCH_ASSOC);

    if (!is_array($record)) {
      return [];
    }

    if (
      $record['expires_at'] !== null
      && (int) $record['expires_at'] <= time()
    ) {
      $this->delete($key);

      return [];
    }

    return $record;
  }

  private function escapeLike(string $value): string
  {
    return str_replace(
      ['\\', '%', '_'],
      ['\\\\', '\\%', '\\_'],
      $value
    );
  }
}
