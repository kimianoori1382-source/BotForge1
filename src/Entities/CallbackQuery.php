<?php

declare(strict_types=1);

namespace Botforge\Entities;

class CallbackQuery
{
  public readonly string $id;
  public readonly User $from;
  public readonly Message $message;
  public readonly string $data;

  public function __construct(array $data = [])
  {
    $id = '';
    $userData = [];
    $messageData = [];
    $callbackData = '';

    if (isset($data['id'])) {
      $id = (string) $data['id'];
    }

    if (isset($data['from']) && is_array($data['from'])) {
      $userData = $data['from'];
    }

    if (isset($data['message']) && is_array($data['message'])) {
      $messageData = $data['message'];
    }

    if (isset($data['data'])) {
      $callbackData = (string) $data['data'];
    }

    $this->id = $id;
    $this->from = new User($userData);
    $this->message = new Message($messageData);
    $this->data = $callbackData;
  }
}
