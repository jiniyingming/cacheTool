<?php /** @noinspection PhpUndefinedMethodInspection */

namespace App\component\RedisTool;

use App\Services\HelperServiceTrait;
use Closure;
use Exception;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Class RedisCache
 * @package App\component\RedisTool
 * Redis 使用类
 */
class RedisCache
{

    use HelperServiceTrait;

    /**
     * @var float|int 默认过期时间
     */
    private static $defaultCacheExpireTime = 3600 * 3;
    /**
     * 缓存key 默认前缀
     */
    private const CACHE_NAMESPACE = 'svr2';

    /**
     * @var RedisCache
     * 实例对象
     */
    private static $_instance;
    /**
     * @var int Redis DB
     */
    protected int $database;

    /**
     * @var string 链接配置
     */
    protected $connection = 'business';
    /**
     * @var Connection
     */
    private $client;

    /**
     * RedisCache constructor.
     */
    private function __construct()
    {
        $this->client = Redis::connection($this->connection)->client();
    }

    /**
     * @param int $database
     * @param string $connection
     * @return RedisCache
     */
    public static function getInstance(int $database = 0, string $connection = 'default'): RedisCache
    {
        if (self::$_instance instanceof self && self::$_instance->connection !== $connection) {
            self::setInstance(new self(), $connection);
            self::setSelectDB($database);
        }
        if (!self::$_instance instanceof self) {
            self::setInstance(new self(), $connection);
            self::setSelectDB($database);
        }
        if (self::$_instance instanceof self && self::$_instance->database !== $database) {
            self::setSelectDB($database);
        }

        return self::$_instance;
    }

    /**
     * @param $database
     */
    private static function setSelectDB($database)
    {
        self::$_instance->client->select($database);
        self::$_instance->database = $database;
    }

    /**
     * @param mixed $instance
     */
    private static function setInstance($instance, $connection): void
    {
        self::$_instance = $instance;
        self::$_instance->connection = $connection;
    }

    /**
     * @param $value
     * @return false|string
     */
    private function serializeValue($value)
    {
        $type = gettype($value);
        switch ($type) {
            case 'integer':
            case 'string':
            case 'boolean':
            case 'NULL':
                break;
            case 'array':
            case 'object':
            default:
                $value = serialize($value);
                break;
        }
        return json_encode(['type' => $type, 'value' => $value]);
    }

    /**
     * @param $value
     * @return mixed|null
     */
    private function unSerializeValue($value)
    {
        if ($value === false) {
            return null;
        }
        if (is_null($value)) {
            return null;
        }
        if (!is_string($value)) {
            return null;
        }
        $value = json_decode($value, true);
        switch ($value['type']) {
            case 'integer':
            case 'string':
            case 'boolean':
            case 'NULL':
                $res = $value['value'];
                break;
            case 'array':
            case 'object':
            default:
                $res = unserialize($value['value']);
                break;
        }

        return $res;
    }

    /**
     * @param string|int|array $key
     * @param string|int|array $val
     * @param int $moduleType
     * @param int $ttl
     * @param bool $isRandom
     * @return void
     * @throws Exception
     */
    public function setCache($key, $val, int $moduleType = RedisCode::COMMON_MODULE, int $ttl = -1, bool $isRandom = true): void
    {
        $ttl = $this->getTTl($ttl, $isRandom);
        $key = $this->generateKey($key, $moduleType);
        $this->client->set($key, $this->serializeValue($val));
        $this->client->expire($key, $ttl);
        if ($this->nameSpace) {
            $this->setCacheListByModuleType($key);
        }
    }

    /**
     * @param int $ttl
     * @param bool $isRandom
     * @return int
     * @throws Exception
     */
    private function getTTl(int $ttl = -1, bool $isRandom = true): int
    {
        $ttl = (int)($ttl < 0 ? self::$defaultCacheExpireTime : $ttl);
        $ttl += ($isRandom ? random_int(60, 3600) : 0);
        return $ttl;
    }

    /**
     * @param $key
     * @param int $ttl
     * @param int $moduleType
     * @throws Exception
     */
    public function expire($key, int $ttl = -1, int $moduleType = RedisCode::COMMON_MODULE): void
    {
        $key = $this->generateKey($key, $moduleType);
        $this->client->expire($key, $ttl);
    }

    /**
     * @param $key
     * @param int $moduleType
     * @return void
     * @throws Exception
     */
    public function delCache($key, int $moduleType = RedisCode::COMMON_MODULE): void
    {
        $key = $this->generateKey($key, $moduleType);
        $this->client->del($key);
    }

    /**
     * @param string|int|array $key 原始key名
     * @param int $moduleType Redis Key 分组
     * @return string
     * @throws Exception
     * 重写RedisKey
     */
    private function generateKey($key, int $moduleType): string
    {
        $objClass = new \ReflectionClass(RedisCode::class);
        if (!in_array($moduleType, $objClass->getConstants(), true)) {
            throw new Exception('moduleType value Undefined');
        }
        $suffix = $key;
        if (is_array($key)) {
            $suffix = md5(json_encode($key));
        }

        return sprintf('%s:%s:%s', self::CACHE_NAMESPACE, $moduleType, $suffix);
    }

    /**
     * @param $key
     * @param int $moduleType
     * @param null $default
     * @return mixed|null
     * @throws Exception
     */
    public function getCache($key, int $moduleType = RedisCode::COMMON_MODULE, $default = null)
    {
        return $this->unSerializeValue($this->client->get($this->generateKey($key, $moduleType))) ?? ($default instanceof Closure ? $default() : $default);
    }

    /**
     * @param $key
     * @param int $moduleType
     * @return false|mixed|string
     * @throws Exception
     */
    public function get($key, int $moduleType = RedisCode::COMMON_MODULE)
    {
        return $this->client->get($this->generateKey($key, $moduleType));
    }

    /**
     * @throws Exception
     */
    public function clear(): void
    {
        if (!$this->nameSpace) {
            throw new Exception('nameSpace not found');
        }
        $listKey = $this->generateKey($this->nameSpace, RedisCode::CACHE_KEY_LIST);
        $cacheKeyList = $this->client->smembers($listKey);
        if ($cacheKeyList) {
            $cacheKeyList[] = $listKey;
            Log::info('clearCacheByModuleType', ['body' => json_encode(['list' => $cacheKeyList])]);
            foreach ($cacheKeyList as $key) {
                $this->client->expire($key, 0);
            }
        }
    }

    /**
     * @param $cacheKey
     * @throws Exception
     * 存储片段域产生的RedisKey集合
     */
    private function setCacheListByModuleType($cacheKey): void
    {
        $key = $this->generateKey($this->nameSpace, RedisCode::CACHE_KEY_LIST);
        if ($cacheKey !== $key) {
            $this->client->sadd($key, $cacheKey);

            $this->client->expire($key, 86400);
        }
    }

    /**
     * @var ?|string|array|int 存储域
     */
    private $nameSpace;

    /**
     * @param $nameSpace
     * @return RedisCache
     * @throws Exception
     * 定义存储域
     */
    public function cacheKey($nameSpace): RedisCache
    {
        $this->nameSpace = $nameSpace;
        return $this;
    }


    /**
     * @param $cacheKey
     * @param int $moduleType
     * @param $key
     * @param $value
     * @return mixed
     * @throws Exception hash Set
     */
    public function hSet($cacheKey, $key, $value, int $moduleType = RedisCode::COMMON_MODULE)
    {
        return $this->client->hSet($this->generateKey($cacheKey, $moduleType), $key, $this->serializeValue($value));
    }

    /**
     * @param $cacheKey
     * @param int $moduleType
     * @param $key
     * @param $value
     * @return mixed
     * @throws Exception hash hSetNx  有则忽略
     */
    public function hSetNx($cacheKey, $key, $value, int $moduleType = RedisCode::COMMON_MODULE)
    {
        return $this->client->hSet($this->generateKey($cacheKey, $moduleType), $key, $this->serializeValue($value));
    }

    /**
     * @param $cacheKey
     * @param int $moduleType
     * @param $key
     * @return mixed|null
     * @throws Exception
     */
    public function hGet($cacheKey, $key, int $moduleType = RedisCode::COMMON_MODULE)
    {
        $val = $this->client->hGet($this->generateKey($cacheKey, $moduleType), $key,);
        return $val ? $this->unSerializeValue($val) : null;
    }

    /**
     * @param $cacheKey
     * @param int $moduleType
     * @param $key
     * @return mixed|null
     * @throws Exception
     */
    public function hExists($cacheKey, $key, int $moduleType = RedisCode::COMMON_MODULE)
    {
        return $this->client->hExists($this->generateKey($cacheKey, $moduleType), $key,);
    }

    /**
     * @param $cacheKey
     * @param int $moduleType
     * @return mixed|null
     * @throws Exception
     */
    public function hGetAll($cacheKey, int $moduleType = RedisCode::COMMON_MODULE)
    {
        $val = $this->client->hGetAll($this->generateKey($cacheKey, $moduleType));
        if (is_array($val)) {
            return array_values(array_map(function ($val) {
                return $this->unSerializeValue($val);
            }, $val));
        }
        return $val;
    }

    /**
     * @param $cacheKey
     * @param int $moduleType
     * @return mixed|null
     * @throws Exception
     */
    public function hDel($cacheKey, int $moduleType = RedisCode::COMMON_MODULE)
    {
        return $this->client->delete($this->generateKey($cacheKey, $moduleType));
    }

    /**
     * @param $key
     * @param int $moduleType
     * @return int
     * @throws Exception
     * 计数
     */
    public function incr($key, int $moduleType = RedisCode::COMMON_MODULE): int
    {
        return $this->client->incr($this->generateKey($key, $moduleType));
    }

    /**
     * @return bool
     * 关闭链接
     */
    public function close(): bool
    {
        self::$_instance = null;
        return $this->client->close();
    }

    /**
     * @var bool 回调管道状态
     */
    protected bool $callPipeline = false;

    /**
     * @return $this
     * 建立管道
     */
    public function pipeline(): RedisCache
    {
        $this->client = $this->client->pipeline()->multi(2);
        $this->callPipeline = true;
        return $this;
    }

    /**
     * @param array $dataMap
     * @param string $suffixKey
     * @param int $chunkLimit
     * @param int $moduleType
     * @param int $ttl
     * @param bool $isRandom
     * @return $this
     * @throws Exception
     * 大数据列表 切片存储
     */
    public function pushSliceData(array $dataMap, string $suffixKey, int $chunkLimit = 5, int $moduleType = RedisCode::COMMON_MODULE, int $ttl = -1, bool $isRandom = true): RedisCache
    {
        if (!$this->callPipeline) {
            throw new Exception('未启用管道命令');
        }

        $ttl = $this->getTTl($ttl, $isRandom);
        $isRandom = false;
        $this->chunkLimit = $chunkLimit;
        $chunk = ceil(($max = count($dataMap)) / $chunkLimit);
        if ($max > $chunk) {
            $dataMap = array_chunk($dataMap, $chunk);
        } else {
            $item = $dataMap;
            $dataMap = [];
            $dataMap[] = $item;
        }
        if (empty($dataMap)) {
            $this->setCache($suffixKey . 0, $dataMap, $moduleType, $ttl, $isRandom);
            return $this;
        }
        foreach ($dataMap as $key => $value) {
            $this->setCache($suffixKey . $key, $value, $moduleType, $ttl, $isRandom);
        }
        return $this;
    }

    public int $chunkLimit;

    /**
     * @return array
     * @throws Exception
     * 执行管道命令
     */
    public function exec(): array
    {
        if (!$this->callPipeline) {
            throw new Exception('未启用管道命令');
        }
        return $this->processData($this->client->exec());
    }

    /**
     * @param string $suffixKey
     * @param int $chunkLimit
     * @param int $moduleType
     * @return $this
     * @throws Exception
     * 大数据列表 取出切片数据
     */
    public function popSliceData(string $suffixKey, int $chunkLimit = 5, int $moduleType = RedisCode::COMMON_MODULE): RedisCache
    {
        if (!$this->callPipeline) {
            throw new Exception('未启用管道命令');
        }
        $this->chunkLimit = $chunkLimit;
        $i = 0;
        while ($i < $chunkLimit) {
            $this->getCache($suffixKey . $i, $moduleType);
            $i++;
        }
        return $this;
    }


    /**
     * @param $key
     * @param array $value
     * @param int $moduleType
     * @return false|int
     * @throws Exception
     */
    public function lPush($key, array $value, int $moduleType = RedisCode::COMMON_MODULE)
    {
        return $this->client->lPush($this->generateKey($key, $moduleType), $this->serializeValue($value));
    }

    /**
     * @param $key
     * @param array $value
     * @param int $moduleType
     * @return false|int
     * @throws Exception
     */
    public function rPush($key, array $value, int $moduleType = RedisCode::COMMON_MODULE)
    {
        return $this->client->rPush($this->generateKey($key, $moduleType), ...array_map(function ($item) {
            return $this->serializeValue($item);
        }, $value));
    }

    /**
     * @param $key
     * @param int $moduleType
     * @return false|int
     * @throws Exception
     */
    public function lPop($key, int $moduleType = RedisCode::COMMON_MODULE)
    {
        $item = $this->client->lPop($this->generateKey($key, $moduleType));
        if ($item) {
            $item = $this->unSerializeValue($item);
        }
        return $item;
    }

    /**
     * @param $key
     * @param int $moduleType
     * @return false|int
     * @throws Exception
     */
    public function rPop($key, int $moduleType = RedisCode::COMMON_MODULE)
    {
        $item = $this->client->rPop($this->generateKey($key, $moduleType));
        if ($item) {
            $item = $this->unSerializeValue($item);
        }
        return $item;
    }

    /**
     * @param $key
     * @param int $moduleType
     * @return bool|int
     * @throws Exception
     */
    public function lLen($key, int $moduleType = RedisCode::COMMON_MODULE)
    {
        return $this->client->lLen($this->generateKey($key, $moduleType));
    }

    /**
     * @param $key
     * @param $start
     * @param $end
     * @param int $moduleType
     * @return array
     * @throws Exception
     */
    public function lRange($key, $start, $end, int $moduleType = RedisCode::COMMON_MODULE): array
    {
        return $this->client->lRange($this->generateKey($key, $moduleType), $start, $end);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws Exception
     * 回调函数
     */
    public function __call($name, $arguments)
    {
        try {
            return $this->client->$name(...$arguments);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
