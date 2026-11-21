<?php

declare(strict_types=1);

namespace Botforge\Middleware;

use Botforge\Context\Context;
use Botforge\Storage\StorageInterface;
use Closure;

class ThrottleMiddleware implements MiddlewareInterface
{
  private StorageInterface $storage;
  private int $limit;
  private int $seconds;
  private Closure $resolver;
  private Closure $onLimited;

  public function __construct(
    StorageInterface $storage,
    int $limit,
    int $seconds,
    callable $resolver,
    callable $onLimited
  ) {
    $this->storage = $storage;
    $this->limit = $limit;
    $this->seconds = $seconds;
    $this->resolver = Closure::fromCallable($resolver);
    $this->onLimited = Closure::fromCallable($onLimited);
  }

  public function handle(array $route, Context $context, callable $next): void
  {
    $key = 'throttle:' . ($this->resolver)($context);
    $count = (int) $this->storage->get($key);

    if ($count >= $this->limit) {
      ($this->onLimited)($context);
      return;
    }

    $this->storage->set($key, $count + 1, $this->seconds);

    $next();
  }
}
