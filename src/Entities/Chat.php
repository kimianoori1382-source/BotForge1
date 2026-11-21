<?php

declare(strict_types=1);

namespace Botforge\Entities;

class Chat
{
  public readonly int $id;
  public readonly string $type;
  public readonly string $title;
  public readonly string $username;
  public readonly string $firstName;
  public readonly string $lastName;

  public function __construct(array $data = [])
  {
    $id = 0;
    $type = '';
    $title = '';
    $username = '';
    $firstName = '';
    $lastName = '';

    if (isset($data['id'])) {
      $id = (int) $data['id'];
    }

    if (isset($data['type'])) {
      $type = (string) $data['type'];
    }

    if (isset($data['title'])) {
      $title = (string) $data['title'];
    }

    if (isset($data['username'])) {
      $username = (string) $data['username'];
    }

    if (isset($data['first_name'])) {
      $firstName = (string) $data['first_name'];
    }

    if (isset($data['last_name'])) {
      $lastName = (string) $data['last_name'];
    }

    $this->id = $id;
    $this->type = $type;
    $this->title = $title;
    $this->username = $username;
    $this->firstName = $firstName;
    $this->lastName = $lastName;
  }
}
