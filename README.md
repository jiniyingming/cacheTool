# CacheTool

主要针对数据量大页面流量高的环境做的缓存策略,数据会进行切片缓存处理并使用延时队列保证数据在阶段时间内的数据更新。

要求
------------

- PHP >= 7.0.0
- Laravel >= 5.5.0
- Redis

### 调用方式

```PHP
<?php
CacheToolFunc::instance()->inputParams($params)
            ->callStaticFunc(CacheFunc::class, 'getData')
            ->setMaxSliceSize(40)
            ->setTemplate([
                'id' => '_id',
                ...
            ])
            ->setTTl(120)
            ->keepTime(2)
            ->output();
```

### 函数解释

#### 调用参数

```PHP
CacheToolFunc::instance()->inputParams($params);
```

#### 目标数据方法(静态调用)

```PHP
CacheToolFunc::instance()->callStaticFunc($class, $method);
```

#### 目标数据方法(非静态调用)

```PHP
CacheToolFunc::instance()->callFunc($class, $method);
```

#### 数据切片数量

```PHP
CacheToolFunc::instance()->setMaxSliceSize($sliceSize);
```

#### 设置输出模板

```PHP
CacheToolFunc::instance()->setTemplate([
                'id' => 'doc_id',
                'name' => 'project_name'
            ]);
```

#### 设置过期时间 秒级

```PHP
CacheToolFunc::instance()->setTTl(60);
```

#### 设置保持更新时间段 分级

```PHP
CacheToolFunc::instance()->keepTime(60);
```

#### 设置Redis 实例

```PHP
CacheToolFunc::instance()->setConnection('default');
```

#### 刷新缓存

```PHP
CacheToolFunc::instance()->flush();
```

#### 设置缓存key前缀

```PHP
CacheToolFunc::instance()->setSuffix('key');
```

Redis 二次封装类
------------

### 数据切片并推送

```PHP
<?php
  RedisCache::getInstance($this->select, $this->connection)
            ->pipeline()
            ->pushSliceData($this->backInfo, $this->suffixKey, $this->maxSliceSize, $this->cacheModule, $this->ttl, $this->ttlIsRandom)
            ->exec();
```

### 取出切片缓存数据

```PHP
<?php
 RedisCache::getInstance(0, $this->connection)
            ->pipeline()
            ->popSliceData($this->suffixKey, $this->maxSliceSize, $this->cacheModule)
            ->exec();
```

