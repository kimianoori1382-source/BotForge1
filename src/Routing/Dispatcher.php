<?php

declare(strict_types=1);

namespace Botforge\Routing;

use Botforge\Container\Container;
use Botforge\Context\Context;
use Botforge\Middleware\MiddlewareInterface;

class Dispatcher
{
  private Router $router;
  private Container $container;
  private array $middleware = [];

  public function __construct(Router $router, Container $container)
  {
    $this->router = $router;
    $this->container = $container;
  }

  public function addMiddleware(MiddlewareInterface $middleware): void
  {
    $this->middleware[] = $middleware;
  }

  public function dispatch(string $type, string $value, Context $context): bool
  {
    $route = $this->router->findMatch($type, $value);

    if (empty($route)) {
      return false;
    }

    $handler = $route['handler'];
    $arguments = [];

    if (isset($route['arguments']) && is_array($route['arguments'])) {
      $arguments = $route['arguments'];
    }

    array_unshift($arguments, $context);

    $next = function () use ($handler, $arguments): void {
      $this->container->call($handler, $arguments);
    };

    foreach (array_reverse($this->middleware) as $middleware) {
      $currentMiddleware = $middleware;
      $nextHandler = $next;

      $next = function () use ($currentMiddleware, $nextHandler, $route, $context): void {
        $currentMiddleware->handle($route, $context, $nextHandler);
      };
    }

    $next();

    return true;
  }
}
