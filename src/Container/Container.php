<?php

declare(strict_types=1);

namespace Botforge\Container;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;

class Container implements ContainerInterface
{
  private array $bindings = [];

  private array $instances = [];

  public function bind(string $abstract, Closure $concrete): void
  {
    $this->bindings[$abstract] = $concrete;
    unset($this->instances[$abstract]);
  }

  public function singleton(string $abstract, Closure $concrete): void
  {
    $this->bindings[$abstract] = $concrete;
    $this->instances[$abstract] = null;
  }

  public function instance(string $abstract, object $instance): void
  {
    $this->instances[$abstract] = $instance;
  }

  public function get(string $id): mixed
  {
      return $this->make($id);
  }

  public function has(string $id): bool
  {
    if (
      array_key_exists($id, $this->bindings)
      || isset($this->instances[$id])
    ) {
      return true;
    }

    if (!class_exists($id)) {
      return false;
    }

    $reflection = new ReflectionClass($id);

    return $reflection->isInstantiable();
  }

  public function make(string $abstract): object
  {
    if (isset($this->instances[$abstract])) {
      return $this->instances[$abstract];
    }

    if (
      !array_key_exists($abstract, $this->bindings)
      && !class_exists($abstract)
    ) {
      throw new NotFoundException(
        'Service not found: ' . $abstract
      );
    }

    if (array_key_exists($abstract, $this->bindings)) {
      $object = ($this->bindings[$abstract])($this);

      if (!is_object($object)) {
        throw new ContainerException(
          'Container binding must return an object.'
        );
      }

      if (array_key_exists($abstract, $this->instances)) {
        $this->instances[$abstract] = $object;
      }

      return $object;
    }

    $reflection = new ReflectionClass($abstract);

    if (!$reflection->isInstantiable()) {
      throw new ContainerException(
        'Class cannot be instantiated: ' . $abstract
      );
    }

    $constructor = $reflection->getConstructor();

    if ($constructor === null) {
      return new $abstract();
    }

    $dependencies = [];

    foreach ($constructor->getParameters() as $parameter) {
      $type = $parameter->getType();

      if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
        $dependencies[] = $this->make($type->getName());
        continue;
      }

      if ($parameter->isDefaultValueAvailable()) {
        $dependencies[] = $parameter->getDefaultValue();
        continue;
      }

      throw new ContainerException(
        'Cannot resolve dependency: ' . $parameter->getName()
      );
    }

    return $reflection->newInstanceArgs($dependencies);
  }

  public function call(callable $callback, array $arguments = []): mixed
  {
    if (is_array($callback)) {
      $class = $callback[0];
      $method = $callback[1];

      if (is_string($class)) {
        $reflection = new ReflectionMethod($class, $method);

        if (!$reflection->isStatic()) {
          $callback = [
            $this->make($class),
            $method,
          ];
        }
      } else {
        $reflection = new ReflectionMethod($class, $method);
      }
    } elseif ($callback instanceof Closure) {
      $reflection = new ReflectionFunction($callback);
    } elseif (is_object($callback)) {
      $reflection = new ReflectionMethod($callback, '__invoke');
    } elseif (is_string($callback) && strpos($callback, '::') !== false) {
      $parts = explode('::', $callback, 2);
      $reflection = new ReflectionMethod($parts[0], $parts[1]);

      if (!$reflection->isStatic()) {
        $callback = [
          $this->make($parts[0]),
          $parts[1],
        ];
      }
    } else {
      $reflection = new ReflectionFunction($callback);
    }

    $dependencies = [];

    foreach ($reflection->getParameters() as $index => $parameter) {
      if (array_key_exists($parameter->getName(), $arguments)) {
        $dependencies[] = $arguments[$parameter->getName()];
        continue;
      }

      $type = $parameter->getType();

      if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
        $resolved = false;

        foreach ($arguments as $argument) {
          if (is_object($argument) && is_a($argument, $type->getName())) {
            $dependencies[] = $argument;
            $resolved = true;
            break;
          }
        }

        if ($resolved) {
          continue;
        }

        $dependencies[] = $this->make($type->getName());
        continue;
      }

      if (array_key_exists($index, $arguments)) {
        $dependencies[] = $arguments[$index];
        continue;
      }

      if ($parameter->isDefaultValueAvailable()) {
        $dependencies[] = $parameter->getDefaultValue();
        continue;
      }

      throw new ContainerException(
        'Cannot resolve parameter: ' . $parameter->getName()
      );
    }

    return call_user_func_array($callback, $dependencies);
  }

  public function registerProvider(ServiceProviderInterface $provider): void
  {
    $provider->register($this);
  }
}
