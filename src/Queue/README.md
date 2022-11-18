# 队列
hyperf的redis和amqp队列消费时都是需要重启一个process进程来消费

1、生成一个process消费进程

# 调用队列的方式
```php
$job = new DemoJob(['name' => 'hapi']);
(new Producer($job))->onQueue('test')->dispatcher();
```

# 监听队列
```shell
php bin/hyperf.php hapi:queue:work [queue]
```