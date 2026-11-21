<?php

declare(strict_types=1);

namespace Botforge\Container;

interface ServiceProviderInterface
{
    public function register(Container $container): void;
}

//وضیفه پروایدر ثبت وابستگی هاست