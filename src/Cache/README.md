# 使用说明
对`Hyperf/Cache`做了功能扩展
1、增加`MemcachedDriver`
2、增加指定`redis`和`memcached`的`pool`

# 配置文件

## 愿配置文件
```php
<?php

declare(strict_types=1);

return [
    'default' => [
        'driver' => Hyperf\Cache\Driver\RedisDriver::class,
        'packer' => Hyperf\Utils\Packer\PhpSerializerPacker::class,
        'prefix' => 'c:',
    ],
];
```

## 增加扩展后的配置文件
```php
<?php

declare(strict_types=1);

return [
    'default' => [
        'driver' => \Nasustop\HapiBase\Cache\RedisDriver::class,
        'packer' => Hyperf\Utils\Packer\PhpSerializerPacker::class,
        'prefix' => 'c:',
        'pool' => 'default',
    ],
    'memcached' => [
        'driver' => \Nasustop\HapiBase\Cache\MemcachedDriver::class,
        'packer' => Hyperf\Utils\Packer\PhpSerializerPacker::class,
        'prefix' => 'c:',
        'pool' => 'default',
    ],
];
```