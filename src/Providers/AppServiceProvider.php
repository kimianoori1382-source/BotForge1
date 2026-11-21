<?php

declare(strict_types=1);

namespace Botforge\Providers;

use Botforge\Container\Container;
use Botforge\Container\ServiceProviderInterface;

class AppServiceProvider implements ServiceProviderInterface
{
  public function register(Container $container): void
  {
    $container->singleton(
      'app.name',
      fn($container) => new \stdClass()
    );
  }
}
