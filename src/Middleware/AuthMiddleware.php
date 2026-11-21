<?php

declare(strict_types=1);

namespace Botforge\Middleware;

use Botforge\Context\Context;
use Closure;

class AuthMiddleware implements MiddlewareInterface
{
  private array $allowedUsers; //اراسه شناسه کاربران مجاز
  private Closure $resolver; //تابعی برای استخراج شناسه کاربر از کانتکست
  private Closure $onDenied; ///تابعی که هنگام نداشتن دسترسی اجرا میشود

  public function __construct(array $allowedUsers, callable $resolver, callable $onDenied)
  {
    $this->allowedUsers = $allowedUsers;
    $this->resolver = Closure::fromCallable($resolver);
    $this->onDenied = Closure::fromCallable($onDenied);
  }

  public function handle(array $route, Context $context, callable $next): void
  {
    $userId = ($this->resolver)($context);

    if (!in_array($userId, $this->allowedUsers, true)) {
      ($this->onDenied)($context);
      return;
    }

    $next();
  }
}
