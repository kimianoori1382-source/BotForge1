<?php

declare(strict_types=1);

namespace Botforge\Entities;

class Update
{
  public readonly int $updateId;
  public readonly Message $message;
  public readonly CallbackQuery $callbackQuery;
  public readonly InlineQuery $inlineQuery;
  public readonly bool $hasMessage;
  public readonly bool $hasCallbackQuery;
  public readonly bool $hasInlineQuery;
  public readonly array $data;

  public function __construct(array $data = [])
  {
    $this->data = $data;
    $this->updateId = isset($data['update_id']) ? (int) $data['update_id'] : 0;

    $messageData = [];
    $callbackData = [];
    $inlineData = [];

    if (isset($data['message']) && is_array($data['message'])) {
      $messageData = $data['message'];
    }

    if (isset($data['callback_query']) && is_array($data['callback_query'])) {
      $callbackData = $data['callback_query'];
    }

    if (isset($data['inline_query']) && is_array($data['inline_query'])) {
      $inlineData = $data['inline_query'];
    }

    $this->hasMessage = !empty($messageData);
    $this->hasCallbackQuery = !empty($callbackData);
    $this->hasInlineQuery = !empty($inlineData);

    $this->message = new Message($messageData);
    $this->callbackQuery = new CallbackQuery($callbackData);
    $this->inlineQuery = new InlineQuery($inlineData);
  }
}
