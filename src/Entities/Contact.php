<?php

declare(strict_types=1);

namespace Botforge\Entities;

class Contact
{
  public readonly string $phoneNumber;
  public readonly string $firstName;
  public readonly string $lastName;
  public readonly int $userId;

  public function __construct(array $data = [])
  {
    $phoneNumber = '';
    $firstName = '';
    $lastName = '';
    $userId = 0;

    if (isset($data['phone_number'])) {
      $phoneNumber = (string) $data['phone_number'];
    }

    if (isset($data['first_name'])) {
      $firstName = (string) $data['first_name'];
    }

    if (isset($data['last_name'])) {
      $lastName = (string) $data['last_name'];
    }

    if (isset($data['user_id'])) {
      $userId = (int) $data['user_id'];
    }

    $this->phoneNumber = $phoneNumber;
    $this->firstName = $firstName;
    $this->lastName = $lastName;
    $this->userId = $userId;
  }
}
