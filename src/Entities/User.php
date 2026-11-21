<?php

declare(strict_types=1);

namespace Botforge\Entities;

class User
{
  public readonly int $id;
  public readonly bool $isBot;
  public readonly string $firstName;
  public readonly string $lastName;
  public readonly string $username;
  public readonly string $languageCode;

  public function __construct(array $data = [])
  {
    $id = 0;
    $isBot = false;
    $firstName = '';
    $lastName = '';
    $username = '';
    $languageCode = '';

    if (isset($data['id'])) {
      $id = (int) $data['id'];
    }

    if (isset($data['is_bot'])) {
      $isBot = (bool) $data['is_bot'];
    }

    if (isset($data['first_name'])) {
      $firstName = (string) $data['first_name'];
    }

    if (isset($data['last_name'])) {
      $lastName = (string) $data['last_name'];
    }

    if (isset($data['username'])) {
      $username = (string) $data['username'];
    }

    if (isset($data['language_code'])) {
      $languageCode = (string) $data['language_code'];
    }

    $this->id = $id;
    $this->isBot = $isBot;
    $this->firstName = $firstName;
    $this->lastName = $lastName;
    $this->username = $username;
    $this->languageCode = $languageCode;
  }
}
