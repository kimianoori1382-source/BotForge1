<?php

declare(strict_types=1);

namespace Botforge\Entities;

class InlineQuery
{
  public readonly string $id;
  public readonly User $from;
  public readonly string $query;
  public readonly string $offset;

  public function __construct(array $data = [])
  {
    $id = '';
    $userData = [];
    $query = '';
    $offset = '';

    if (isset($data['id'])) {
      $id = (string) $data['id'];
    }

    if (isset($data['from']) && is_array($data['from'])) {
      $userData = $data['from'];
    }

    if (isset($data['query'])) {
      $query = (string) $data['query'];
    }

    if (isset($data['offset'])) {
      $offset = (string) $data['offset'];
    }

    $this->id = $id;
    $this->from = new User($userData);
    $this->query = $query;
    $this->offset = $offset;
  }
}
