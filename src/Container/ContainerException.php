<?php

declare(strict_types=1);

namespace Botforge\Container;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

class ContainerException extends RuntimeException implements ContainerExceptionInterface {

}
 //برای رعایت خطای psr-11