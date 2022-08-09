<?php


namespace App\component\CacheTool;

/**
 * Interface Cache
 * @package App\component\CacheTool
 */
interface Cache
{
    /**
     * @param int $ttl
     * @param bool $isRandom
     * @return CacheToolFunc 设置过期时间
     * 设置过期时间
     */
    public function setTTl(int $ttl = -1, bool $isRandom = true): CacheToolFunc;

    /**
     * @param string $key
     * @return CacheToolFunc
     * 设置key前缀
     */
    public function setSuffix(string $key): CacheToolFunc;

    /**
     * @param string $connection
     * @return CacheToolFunc
     * 设置数据链接
     */
    public function setConnection(string $connection): CacheToolFunc;

    /**
     * @param int $maxSliceSize
     * @return CacheToolFunc
     * 最大切片数
     */
    public function setMaxSliceSize(int $maxSliceSize): CacheToolFunc;

    /**
     * @return CacheToolFunc
     * 刷新缓存
     */
    public function flush(): CacheToolFunc;
}
