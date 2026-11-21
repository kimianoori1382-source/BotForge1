<?php

declare(strict_types=1);

namespace Botforge\Middleware;

use Botforge\Context\Context;
use Closure;

class MaintenanceMiddleware implements MiddlewareInterface
{
  private bool $enabled;
  private array $allowedUsers;
  private Closure $resolver;
  private Closure $onMaintenance;

  public function __construct(
    bool $enabled,  //روشن یا خاموش بودن حالت تعمیرات
    array $allowedUsers,
    callable $resolver,
    callable $onMaintenance  //پیام نمایس داده به کاربران غیر مجاز
  ) {
    $this->enabled = $enabled;
    $this->allowedUsers = $allowedUsers;
    $this->resolver = Closure::fromCallable($resolver);
    $this->onMaintenance = Closure::fromCallable($onMaintenance);
  }

  public function handle(array $route, Context $context, callable $next): void
  {
    if (!$this->enabled) {
      $next();
      return;
    }

    $userId = ($this->resolver)($context);

    if (in_array($userId, $this->allowedUsers, true)) {
      $next();
      return;
    }

    ($this->onMaintenance)($context);
  }
}
