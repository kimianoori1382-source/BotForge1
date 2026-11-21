<?php

declare(strict_types=1);

namespace Botforge\Routing;

use Closure;

class Router
{
  private array $routes = [];

  public function command(string $command, callable $handler): void
  {
    $this->addRoute('command', $command, $handler);
  }

  public function text(string $text, callable $handler): void
  {
    $this->addRoute('text', $text, $handler);
  }

  public function textRegex(string $pattern, callable $handler): void
  {
    $this->addRoute('regex', $pattern, $handler);
  }

  public function textContains(string $text, callable $handler): void
  {
    $this->addRoute('contains', $text, $handler);
  }

  public function callback(string $data, callable $handler): void
  {
    $this->addRoute('callback', $data, $handler);
  }

  public function callbackRegex(string $pattern, callable $handler): void
  {
    $this->addRoute('callback_regex', $pattern, $handler);
  }

  public function inlineQuery(string $pattern, callable $handler): void
  {
    $this->addRoute('inline_query', $pattern, $handler);
  }

  public function messageType(string $messageType, callable $handler): void
  {
    $this->addRoute('message_type', $messageType, $handler);
  }

  public function fallback(callable $handler): void
  {
    $this->addRoute('fallback', '*', $handler);
  }

  private function addRoute(string $type, string $pattern, callable $handler): void
  {
    $this->routes[] = [
      'type' => $type,
      'pattern' => $pattern,
      'handler' => Closure::fromCallable($handler),
    ];
  }

  public function chatMember(callable $handler): void
  {
    $this->addRoute('chat_member', '*', $handler);
  }

  public function myChatMember(callable $handler): void
  {
    $this->addRoute('my_chat_member', '*', $handler);
  }

  public function updateType(string $type, callable $handler): void
  {
    $this->addRoute($type, '*', $handler);
  }

  public function findMatch(string $type, string $value): array
  {
    foreach ($this->routes as $route) {
      if ($route['type'] !== $type) {
        continue;
      }

      $matched = false;
      $arguments = [];

      if ($route['pattern'] === '*') {
        $matched = true;
      }

      if ($type === 'command') {
        $parts = preg_split('/\s+/', trim($value));

        if (!is_array($parts) || empty($parts)) {
          continue;
        }

        $receivedCommand = ltrim($parts[0], '/');
        $receivedCommand = explode('@', $receivedCommand, 2)[0];

        $registeredCommand = ltrim($route['pattern'], '/');
        $registeredCommand = explode('@', $registeredCommand, 2)[0];

        $matched = $receivedCommand === $registeredCommand;
        $arguments = array_slice($parts, 1);
      }

      if ($type === 'text' || $type === 'callback') {
        $matched = $value === $route['pattern'];
      }

      if ($type === 'regex' || $type === 'callback_regex') {
        $matches = [];
        $matched = preg_match($route['pattern'], $value, $matches) === 1;

        if ($matched) {
          $arguments = array_slice($matches, 1);
        }
      }

      if ($type === 'contains') {
        $matched = strpos($value, $route['pattern']) !== false;
      }

      if ($type === 'inline_query') {
        $matched = $route['pattern'] === '*'
          || strpos($value, $route['pattern']) === 0;
      }

      if ($type === 'message_type') {
        $matched = $value === $route['pattern'];
      }

      if ($matched) {
        $route['arguments'] = $arguments;

        return $route;
      }
    }

    if ($type === 'fallback') {
      foreach ($this->routes as $route) {
        if ($route['type'] === 'fallback') {
          return $route;
        }
      }
    }

    return [];
  }

  public function group(string $prefix, callable $callback): void
  {
    $group = new self();

    $callback($group);

    foreach ($group->getRoutes() as $route) {
      if ($route['type'] === 'command' || $route['type'] === 'callback') {
        $route['pattern'] = $prefix . $route['pattern'];
      }

      $this->routes[] = $route;
    }
  }

  public function getRoutes(): array
  {
    return $this->routes;
  }
}
