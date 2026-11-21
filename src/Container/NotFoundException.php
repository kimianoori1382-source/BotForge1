<?php

declare(strict_types=1);

namespace Botforge\Container;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends ContainerException implements NotFoundExceptionInterface {

}
//برای رعایت خطای psr-11