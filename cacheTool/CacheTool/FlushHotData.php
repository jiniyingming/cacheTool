<?php

namespace App\component\CacheTool;

use App\component\RedisTool\RedisCache;
use App\component\RedisTool\RedisCode;
use App\Services\ProjectShareService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class FlushHotData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $args;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($args)
    {
        $this->args = $args;
    }

    /**
     * @throws Exception
     */
    public function handle()
    {
        if (isset($this->args['dealFuncMap'], $this->args['maxEachSize'], $this->args['maxDelaySec'])) {
            $forEachSize = (int)RedisCache::getInstance()->get($this->args['dealFuncMap'], RedisCode::CACHE_FUNC_FLUSH_MODULE);
            if ($forEachSize <= (int)$this->args['maxEachSize']) {
                if ($this->incrDelayed()) {
                    $cacheFunc = CacheToolFunc::instance();
                    foreach ($this->args['dealFuncMap'] as $func => $args) {
                        $cacheFunc = $cacheFunc->$func(...$args);
                    }
                    if ($cacheFunc->flush()->exec()) {
                        self::dispatch($this->args)->delay($this->args['maxDelaySec'])->onQueue(config('queue.high'));
                    }
                }
            }
        }
    }


    /**
     * @return int
     * @throws Exception
     */
    private function incrDelayed(): int
    {
        return RedisCache::getInstance()->incr($this->args['dealFuncMap'], RedisCode::CACHE_FUNC_FLUSH_MODULE);
    }
}
