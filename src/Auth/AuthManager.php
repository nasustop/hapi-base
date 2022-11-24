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

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class AuthManager
{
    protected JwtFactory $jwtFactory;

    protected ConfigInterface $config;

    protected UserProviderInterface $userProvider;

    protected array $payload;

    public function __construct(protected ContainerInterface $container, protected string $guard)
    {
        $providerName = $this->getConfig("auth.{$guard}.provider");
        if (! class_exists($providerName)) {
            throw new \InvalidArgumentException("auth.{$guard}.provider is not exists");
        }
        $provider = new $providerName($this->container, $guard);
        if (! $provider instanceof UserProviderInterface) {
            throw new \InvalidArgumentException("auth.{$guard}.provider is not UserProviderInterface type class");
        }
        $this->userProvider = $provider;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function attempt(array $inputData): string
    {
        $user = $this->userProvider->login($inputData);
        return $this->getJwtFactory()->encode($user);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function user(): array
    {
        $payload = $this->payload();
        return $this->userProvider->getInfo($payload);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function logout(): bool
    {
        $payload = $this->payload();
        return $this->userProvider->logout($payload);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function payload(): array
    {
        if (empty($this->payload)) {
            $this->payload = $this->getJwtFactory()->decode();
        }
        return $this->payload;
    }

    /**
     * get JwtFactory.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getJwtFactory(): JwtFactory
    {
        if (empty($this->jwtFactory)) {
            $this->jwtFactory = new JwtFactory($this->container, $this->guard);
            $this->jwtFactory->setJwtConfig($this->userProvider->setJwtConfig());
        }
        return $this->jwtFactory;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getConfig(string $key, mixed $default = null)
    {
        if (empty($this->config)) {
            $this->config = $this->container->get(ConfigInterface::class);
        }
        return $this->config->get($key, $default);
    }
}
