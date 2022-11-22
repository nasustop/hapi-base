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
use Hyperf\Contract\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class AuthManager
{
    protected JwtFactory $jwtFactory;

    protected ConfigInterface $config;

    protected UserProviderInterface $userProvider;

    protected array $payload;

    protected string $guard;

    public function __construct(protected ContainerInterface $container)
    {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function guard(string $guard = 'default'): static
    {
        $this->guard = $guard;
        $providerName = $this->getConfig("auth.{$guard}.provider");
        if (! class_exists($providerName)) {
            throw new \InvalidArgumentException("auth.{$guard}.provider is not exists");
        }
        $provider = new $providerName();
        if (! $provider instanceof UserProviderInterface) {
            throw new \InvalidArgumentException("auth.{$guard}.provider is not UserProviderInterface type class");
        }
        $this->userProvider = $provider;
        return $this;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function attempt(array $inputData): string
    {
        if (empty($this->guard)) {
            $this->guard();
        }
        $user = $this->userProvider->login($inputData);
        return $this->getJwtFactory()->encode($user);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function user(): array
    {
        if (empty($this->guard)) {
            $this->guard();
        }
        $payload = $this->payload();
        return $this->userProvider->getInfo($payload);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
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
