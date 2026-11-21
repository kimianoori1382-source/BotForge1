<?php

declare(strict_types=1);

namespace Botforge\Context;

use Botforge\Api\TelegramApiClient;
use Botforge\Entities\Update;
use Botforge\Entities\Message;
use Botforge\Entities\User;
use Botforge\Entities\Chat;
use Botforge\Entities\CallbackQuery;
use Botforge\Entities\InlineQuery;
use Botforge\Storage\StorageInterface;
use RuntimeException;

class Context
{
  private TelegramApiClient $api;

  private Update $update;

  private StorageInterface $storage;

  public function __construct(
    TelegramApiClient $api,
    Update $update,
    StorageInterface $storage
  ) {
    $this->api = $api;
    $this->update = $update;
    $this->storage = $storage;
  }

  public function getApi(): TelegramApiClient
  {
    return $this->api;
  }

  public function getUpdate(): Update
  {
    return $this->update;
  }

  public function getMessage(): Message
  {
    if (!$this->update->hasMessage) {
      throw new RuntimeException('This update has no message.');
    }

    return $this->update->message;
  }

  public function getUser(): User
  {
    if ($this->update->hasCallbackQuery) {
      return $this->update->callbackQuery->from;
    }

    if ($this->update->hasInlineQuery) {
      return $this->update->inlineQuery->from;
    }

    if ($this->update->hasMessage) {
      return $this->update->message->from;
    }

    throw new RuntimeException('User is not available in this update.');
  }

  public function getChat(): Chat
  {
    if ($this->update->hasCallbackQuery) {
      return $this->update->callbackQuery->message->chat;
    }

    if ($this->update->hasMessage) {
      return $this->update->message->chat;
    }

    throw new RuntimeException('Chat is not available in this update.');
  }

  public function getCallbackQuery(): CallbackQuery
  {
    if (!$this->update->hasCallbackQuery) {
      throw new RuntimeException('Callback query is not available.');
    }

    return $this->update->callbackQuery;
  }

  public function getInlineQuery(): InlineQuery
  {
    if (!$this->update->hasInlineQuery) {
      throw new RuntimeException('Inline query is not available.');
    }

    return $this->update->inlineQuery;
  }

  public function reply(string $text): array
  {
    return $this->api->sendMessage([
      'chat_id' => $this->getChat()->id,
      'text' => $text,
    ]);
  }

  public function replyHtml(string $text): array
  {
    return $this->api->sendMessage([
      'chat_id' => $this->getChat()->id,
      'text' => $text,
      'parse_mode' => 'HTML',
    ]);
  }

  public function replyMarkdown(string $text): array
  {
    return $this->api->sendMessage([
      'chat_id' => $this->getChat()->id,
      'text' => $text,
      'parse_mode' => 'MarkdownV2',
    ]);
  }

  public function replyTo(string $text): array
  {
    return $this->api->sendMessage([
      'chat_id' => $this->getChat()->id,
      'text' => $text,
      'reply_parameters' => [
        'message_id' => $this->getMessage()->messageId,
      ],
    ]);
  }

  public function sendKeyboard(string $text, array $keyboard): array
  {
    return $this->api->sendMessage([
      'chat_id' => $this->getChat()->id,
      'text' => $text,
      'reply_markup' => $keyboard,
    ]);
  }

  public function editMessage(string $text, int $messageId): array
  {
    return $this->api->editMessageText([
      'chat_id' => $this->getChat()->id,
      'message_id' => $messageId,
      'text' => $text,
    ]);
  }

  public function deleteMessage(int $messageId): array
  {
    return $this->api->deleteMessage([
      'chat_id' => $this->getChat()->id,
      'message_id' => $messageId,
    ]);
  }

  public function answerCallback(string $text = ''): array
  {
    return $this->api->answerCallbackQuery([
      'callback_query_id' => $this->getCallbackQuery()->id,
      'text' => $text,
    ]);
  }

  public function getFile(string $fileId): array
  {
    return $this->api->getFile([
      'file_id' => $fileId,
    ]);
  }

  public function downloadFile(string $filePath): string
  {
    return $this->api->downloadFile($filePath);
  }

  private function getSessionPrefix(): string
  {
    return 'session:' . $this->getUser()->id . ':';
  }

  public function getSession(string $key, mixed $default = null): mixed
  {
    $value = $this->storage->get(
      $this->getSessionPrefix() . $key
    );

    if ($value === null) {
      return $default;
    }

    return $value;
  }

  public function setSession(string $key, mixed $value): void
  {
    $this->storage->set(
      $this->getSessionPrefix() . $key,
      $value
    );
  }

  public function deleteSession(string $key): void
  {
    $this->storage->delete(
      $this->getSessionPrefix() . $key
    );
  }

  public function clearSession(): void
  {
    $this->storage->deleteByPrefix(
      $this->getSessionPrefix()
    );
  }
}
