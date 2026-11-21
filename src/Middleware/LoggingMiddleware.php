<?php

declare(strict_types=1);

namespace Botforge\Middleware;

use Botforge\Context\Context;
use Closure;

class LoggingMiddleware implements MiddlewareInterface
{
  private Closure $logger;

  public function __construct(callable $logger)
  {
    $this->logger = Closure::fromCallable($logger);
  }

  public function handle(array $route, Context $context, callable $next): void
  {
    ($this->logger)('Route started', $route);

    try {
      $next();
      ($this->logger)('Route completed', $route);
    } catch (\Throwable $exception) {
      ($this->logger)('Route failed: ' . $exception->getMessage(), $route);

      throw $exception;
    }
  }
}
