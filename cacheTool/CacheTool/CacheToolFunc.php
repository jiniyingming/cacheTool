<?php

namespace App\component\CacheTool;

use App\component\RedisTool\RedisCache;
use Exception;

class CacheToolFunc extends CacheFunc implements CacheTool, Cache
{

    /**
     * CacheToolFunc constructor.
     */
    private function __construct()
    {
    }

    /**
     * @var CacheToolFunc 实例
     */
    protected static $_instance;

    /**
     * @return CacheToolFunc|null
     */
    public static function instance(): CacheToolFunc
    {
        self::$_instance = new self();
        return self::$_instance;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function inputParams(array $params): CacheToolFunc
    {
        $this->funcArgsMap[__FUNCTION__] = func_get_args();
        $this->params = $params;
        return self::$_instance;
    }

    /**
     * @param string $className
     * @param string $funcName
     * @return $this
     */
    public function callFunc(string $className, string $funcName): CacheToolFunc
    {
        $this->funcArgsMap[__FUNCTION__] = func_get_args();
        $this->callClass = $className;
        $this->callFunc = $funcName;
        return $this;
    }

    /**
     * @param string $className
     * @param string $funcName
     * @return $this
     */
    public function callStaticFunc(string $className, string $funcName): CacheToolFunc
    {
        $this->funcArgsMap[__FUNCTION__] = func_get_args();
        $this->callStaticsClass = $className;
        $this->callStaticsFunc = $funcName;
        $this->isCallStatic = true;
        return $this;
    }

    /**
     * @param int $ttl
     * @param bool $isRandom
     * @return CacheToolFunc
     */
    public function setTTl(int $ttl = -1, bool $isRandom = true): CacheToolFunc
    {
        $this->ttl = $ttl;
        $this->ttlIsRandom = $isRandom;
        $this->funcArgsMap[__FUNCTION__] = func_get_args();

        return $this;
    }

    /**
     * @param string $key
     * @return CacheToolFunc
     */
    public function setSuffix(string $key): CacheToolFunc
    {
        $this->suffixKey = $key;
        $this->funcArgsMap[__FUNCTION__] = func_get_args();
        return $this;
    }


    /**
     * @return array
     * @throws Exception
     */
    public function output(): array
    {
        if (!$this->isCallExec) {
            $this->exec();
        }
        return $this->backInfo;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function exec(): bool
    {
        $this->execute();
        $this->isCallExec = true;
        return !empty($this->backInfo);
    }

    /**
     * @param int $min
     * @return $this
     */
    public function keepTime(int $min = 30): CacheToolFunc
    {
        $this->keepMin = $min;
        return $this;
    }

    /**
     * @param string $connection
     * @return $this
     */
    public function setConnection(string $connection): CacheToolFunc
    {
        $this->connection = $connection;
        $this->funcArgsMap[__FUNCTION__] = func_get_args();
        return $this;
    }

    /**
     * @param array $template
     * @return $this
     */
    public function setTemplate(array $template): CacheToolFunc
    {
        $this->template = $template;
        $this->funcArgsMap[__FUNCTION__] = func_get_args();
        return $this;
    }

    /**
     * @param int $maxSliceSize
     * @return $this
     * 最大切片数
     */
    public function setMaxSliceSize(int $maxSliceSize): CacheToolFunc
    {
        $this->maxSliceSize = $maxSliceSize;
        $this->funcArgsMap[__FUNCTION__] = func_get_args();
        return $this;
    }

    /**
     * @return $this
     */
    public function flush(): CacheToolFunc
    {
        $this->flushCache = true;
        return $this;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function asyncFlushCache(): bool
    {
        $this->asyncFlushCache = true;
        $this->execute();
        $this->isCallExec = true;
        return true;
    }


}
