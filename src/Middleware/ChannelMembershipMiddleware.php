<?php

declare(strict_types=1);

namespace Botforge\Middleware;

use Botforge\Context\Context;
use Closure;

class ChannelMembershipMiddleware implements MiddlewareInterface
{
  private Closure $checker;  //تابع بررسی عضویت کاربر
  private Closure $onDenied;  // تابع اجرا شده در صورت عضو نبودن

  public function __construct(callable $checker, callable $onDenied)
  {
    $this->checker = Closure::fromCallable($checker);
    $this->onDenied = Closure::fromCallable($onDenied);
  }

  public function handle(array $route, Context $context, callable $next): void
  {
    $isMember = ($this->checker)($context);

    if (!$isMember) {
      ($this->onDenied)($context);
      return;
    }

    $next();
  }
}
