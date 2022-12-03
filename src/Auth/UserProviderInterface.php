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

interface UserProviderInterface
{
    public function getInfo(array $payload): array;

    public function login(array $inputData): array;

    public function logout(array $payload): bool;

    public function validateToken(array $payload): array;

    public function setJwtConfig(): array;
}
