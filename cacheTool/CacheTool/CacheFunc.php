<?php


namespace App\component\CacheTool;


use App\component\RedisTool\RedisCache;
use App\component\RedisTool\RedisCode;
use App\Services\HelperServiceTrait;
use Exception;

class CacheFunc
{
    /**
     * @var string redis 链接
     */
    protected string $connection = 'business';

    /**
     * @var int 最大切片数
     */
    protected int $maxSliceSize = 40;
    /**
     * @var int 过期时间
     */
    protected int $ttl = 5 * 60;

    /**
     * @var bool 过期随机
     */
    protected bool $ttlIsRandom;

    /**
     * @var string key 前缀
     */
    protected string $suffixKey = 'slice';

    /**
     * @var string 调用类
     */
    protected string $callClass;

    /**
     * @var string 调用方法
     */
    protected string $callStaticsFunc;
    /**
     * @var string 调用类
     */
    protected string $callStaticsClass;

    /**
     * @var string 调用方法
     */
    protected string $callFunc;

    /**
     * @var array 数据集
     */
    protected array $backInfo = [];
    /**
     * @var bool 执行结果
     */
    protected bool $isCallExec = false;

    /**
     * @var int 保持更新 时长 分钟
     */
    protected int $keepMin = 0;

    /**
     * @var array 参数
     */
    protected array $params;

    /**
     * @var bool 是否未静态调用
     */
    protected bool $isCallStatic = false;

    /**
     * @var int 缓存归属模块
     */
    protected int $cacheModule = 1;
    /**
     * @var int Redis 选库
     */
    protected int $select = 0;
    /**
     * @var bool 刷新缓存
     */
    protected bool $flushCache = false;

    /**
     * @var int 持续更新动作提前量
     */
    protected int $advanceSec = 100;

    /**
     * @var array 调用链路集合
     */
    protected array $funcArgsMap;
    /**
     * @var array 模板
     */
    protected array $template = [];


    /**
     * 初始化配置
     */
    protected function initConfig()
    {

    }

    /**
     * @throws Exception
     */
    protected function execute()
    {
        $this->initConfig();
        if (!$this->flushCache) {
            $info = $this->selectCache();
            if ($info) {
                $this->backInfo = $info;
                return;
            }
        }
        $this->backInfo = $this->selectDB();
        $this->processTemplate();
        $this->execCacheData();
    }


    /**
     * @throws Exception
     */
    protected function execCacheData()
    {
        RedisCache::getInstance($this->select, $this->connection)
            ->pipeline()
            ->pushSliceData($this->backInfo, $this->suffixKey, $this->maxSliceSize, $this->cacheModule, $this->ttl, $this->ttlIsRandom)
            ->exec();
        if ($this->keepMin > 0) {
            $this->asyncKeepFlushCache();
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function selectCache(): array
    {
        return RedisCache::getInstance(0, $this->connection)
            ->pipeline()
            ->popSliceData($this->suffixKey, $this->maxSliceSize, $this->cacheModule)
            ->exec();
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function selectDB(): array
    {
        if ($this->isCallStatic) {
            return $this->callStatic();
        } else {
            return $this->call();
        }
    }


    /**
     * @return array
     * @throws Exception
     */
    private function callStatic(): array
    {
        if (!$this->callStaticsClass || !$this->callStaticsFunc || !$this->params) {
            throw new Exception('缺少调用方法');
        }
        return $this->callStaticsClass::{$this->callStaticsFunc}(...$this->params);
    }

    /**
     * @return array
     * @throws Exception
     */
    private function call(): array
    {
        if (!$this->callStaticsClass || !$this->callStaticsFunc || !$this->params) {
            throw new Exception('缺少调用方法');
        }
        return app($this->callClass)->{$this->callFunc}(...$this->params);
    }

    /**
     * 异步软更新
     * @throws Exception
     */
    private function asyncKeepFlushCache()
    {
        if ($this->ttl <= $this->advanceSec) {
            throw new Exception(sprintf('过期时间不得少于%d秒', $this->advanceSec));
        }
        $maxEachSize = floor(($this->keepMin * 60) / ($this->ttl - $this->advanceSec));
        $maxDelaySec = floor(($this->keepMin * 60) / $maxEachSize);
        $params = [
            'dealFuncMap' => $this->funcArgsMap,
            'maxEachSize' => $maxEachSize,
            'maxDelaySec' => $maxDelaySec,
        ];
        $lock = !RedisCache::getInstance()->getCache($params, $this->cacheModule);
        if ($lock && $maxDelaySec > 0 && $maxEachSize > 0) {
            RedisCache::getInstance()->setCache($params, 1, $this->cacheModule);
            FlushHotData::dispatch($params)->delay($maxDelaySec)->onQueue(config('queue.high'));
        }
    }


    /**
     * 列表重组
     */
    private function processTemplate()
    {
        if ($this->template) {
            foreach ($this->backInfo as &$item) {
                $item = self::outputList($item, $this->template);
            }
        }
    }

    /**
     * @param $dataList
     * @param array $template
     * @return array
     */
    public static function outputList($dataList, array $template): array
    {
        $item = [];
        if ($template) {
            foreach ($template as $origin => $output) {
                self::setItem($output, self::findItem($origin, $dataList), $item);
            }
        }
        return $item;
    }

    /**
     * @param $path
     * @param $data
     * @return mixed|null
     * 查值
     */
    private static function findItem($path, $data)
    {
        $key = explode('.', $path);
        $val = null;
        foreach ($key as $item) {
            if (isset($data[$item])) {
                $val = $data[$item];
            }
            if (isset($val[$item])) {
                $val = $val[$item];
            }
        }
        return $val;
    }

    /**
     * @param string $path
     * @param $val
     * @param $outputTemplate
     * @return void
     * 设值
     */
    private static function setItem(string $path, $val, &$outputTemplate)
    {
        $keyList = explode('.', $path);
        switch (($key = count($keyList))) {
            case 1:
                $outputTemplate[$keyList[$key - 1]] = $val;
                break;
            case 2:
                $outputTemplate[$keyList[0]][$keyList[1]] = $val;
                break;
            case 3:
                $outputTemplate[$keyList[0]][$keyList[1]][$keyList[2]] = $val;
                break;
        }
        return;
    }

}

