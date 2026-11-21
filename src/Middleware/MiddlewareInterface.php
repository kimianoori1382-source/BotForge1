<?php

declare(strict_types=1);

namespace Botforge\Middleware;

use Botforge\Context\Context;

interface MiddlewareInterface
{
  public function handle(array $route, Context $context, callable $next): void;
}
