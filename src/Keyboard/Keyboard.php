<?php

declare(strict_types=1);

namespace Botforge\Keyboard;

use InvalidArgumentException;

class Keyboard
{
  private array $buttons = [];

  public function row(array $buttons): self
  {
    $this->buttons[] = $buttons;

    return $this;
  }

  public function button(string $text, string $callbackData): self
  {
    $this->addButton([
      'text' => $text,
      'callback_data' => $callbackData,
    ]);

    return $this;
  }

  public function urlButton(string $text, string $url): self
  {
    $this->addButton([
      'text' => $text,
      'url' => $url,
    ]);

    return $this;
  }

  public function webAppButton(string $text, string $url): self
  {
    $this->addButton([
      'text' => $text,
      'web_app' => [
        'url' => $url,
      ],
    ]);

    return $this;
  }

  public function replyButton(string $text): self
  {
    $this->addButton([
      'text' => $text,
    ]);

    return $this;
  }

  public function contactButton(string $text): self
  {
    $this->addButton([
      'text' => $text,
      'request_contact' => true,
    ]);

    return $this;
  }

  public function locationButton(string $text): self
  {
    $this->addButton([
      'text' => $text,
      'request_location' => true,
    ]);

    return $this;
  }

  public function inline(): array
  {
    return [
      'inline_keyboard' => $this->buttons,
    ];
  }

  public function reply(): array
  {
    return [
      'keyboard' => $this->buttons,
      'resize_keyboard' => true,
    ];
  }

  public function remove(): array
  {
    return [
      'remove_keyboard' => true,
    ];
  }

  public function forceReply(): array
  {
    return [
      'force_reply' => true,
    ];
  }

  public static function paginate(
    array $items,
    int $page,
    int $perPage,
    string $callbackPrefix
  ): array {
    if ($perPage < 1) {
      throw new InvalidArgumentException('Per page must be greater than zero.');
    }

    $totalItems = count($items);
    $totalPages = max(1, (int) ceil($totalItems / $perPage));

    $page = max(1, min($page, $totalPages));

    $offset = ($page - 1) * $perPage;
    $pageItems = array_slice($items, $offset, $perPage);

    $keyboard = new self();

    foreach ($pageItems as $item) {
      if (!isset($item['id']) || !isset($item['title'])) {
        continue;
      }

      $keyboard->button(
        (string) $item['title'],
        $callbackPrefix . ':' . $item['id']
      );
    }

    $navigation = [];

    if ($page > 1) {
      $navigation[] = [
        'text' => 'Previous',
        'callback_data' => $callbackPrefix . ':page:' . ($page - 1),
      ];
    }

    if ($page < $totalPages) {
      $navigation[] = [
        'text' => 'Next',
        'callback_data' => $callbackPrefix . ':page:' . ($page + 1),
      ];
    }

    if (!empty($navigation)) {
      $keyboard->row($navigation);
    }

    return $keyboard->inline();
  }

  private function addButton(array $button): void
  {
    $lastRow = count($this->buttons) - 1;

    if ($lastRow < 0) {
      $this->buttons[] = [];
      $lastRow = 0;
    }

    $this->buttons[$lastRow][] = $button;
  }
}
