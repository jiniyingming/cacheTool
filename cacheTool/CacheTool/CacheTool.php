<?php

namespace App\component\CacheTool;

/**
 * Interface CacheTool
 * @package App\component\CacheTool
 */
interface CacheTool
{

    /**
     * @param array $params
     * @return CacheToolFunc
     * 输入参数
     */
    public function inputParams(array $params): CacheToolFunc;

    /**
     * @param string $className
     * @param string $funcName
     * @return CacheToolFunc
     * 调用数据方法
     */
    public function callFunc(string $className, string $funcName): CacheToolFunc;

    /**
     * @param string $className
     * @param string $funcName
     * @return CacheToolFunc
     * 调用数据方法
     */
    public function callStaticFunc(string $className, string $funcName): CacheToolFunc;


    /**
     * @return array
     */
    public function output(): array;

    /**
     * @return bool
     */
    public function exec(): bool;


    /**
     * @return CacheToolFunc
     * 软更新下 缓存保持时间
     */
    public function keepTime(int $min = 30): CacheToolFunc;
}
