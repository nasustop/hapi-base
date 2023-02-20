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