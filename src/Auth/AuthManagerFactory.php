<?php

declare(strict_types=1);
/**
 * This file is part of HapiBase.
 *
 * @link     https://www.nasus.top
 * @document https://wiki.nasus.top
 * @contact  xupengfei@xupengfei.net
 * @license  https://github.com/nasustop/hapi-base/blob/master/LICENSE
 */
namespace Nasustop\HapiBase\Auth;

use Psr\Container\ContainerInterface;

class AuthManagerFactory
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    public function guard(string $guard): AuthManager
    {
        return new AuthManager($this->container, $guard);
    }
}
