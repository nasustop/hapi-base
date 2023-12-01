# HapiBase

使用Hyperf框架时，一些基础类的集合

## Repository
新建repository类`App\Repository\Repository`
```php
<?php

namespace App\Repository;

class Repository extends \Nasustop\HapiBase\Repository\Repository
{
    // TODO: 单独建立一个基础类的好处是，如果组件中的方法有不符合自己业务的，可选择重写该方法
}
```

## filter查询
```php
$filter = [
    'name' => 'test',
    'age|gte' => 18,
];

$filter = [
    'sex' => '男',
    [
        ['name' => 'test'],
        'id' => 1,
        ['age|lte' => 18],
    ],
];
$filter = [
    'name' => 'test',
    'or' => [
        ['age' => 18, 'sex' => '男'],
        ['age' => 19, 'sex' => '女'],
        ['age' => 19, 'sex' => '女'],
        'name' => 'test01',
    ],
];
```