<?php

namespace App\component\RedisTool;

/**
 * redis 缓存模块具体划分
 */
class RedisCode
{
    /*********************redis缓存集合********************/

    /* 缓存设置缓存块模组 便于统一刷新缓存 */
    public const CACHE_KEY_LIST = 9999;
    public const CACHE_KEY_OTHER = 0;
    public const CACHE_KEY_COMM = 0;

    /*********************模块划分********************/
    /**
     * 项目文件
     */
    public const PROJECT_FILE_MODULE = 1;
    /**
     * 个人版用户
     */
    public const PROJECT_PUB_USER_MODULE = 2;
    /**
     * 企业版用户
     */
    public const PROJECT_ENT_USER_MODULE = 3;
    /**
     * 任务看板
     */
    public const TASK_PANELS_MODULE = 4;
    /**
     * 任务看板 日历视图
     */
    public const TASK_PANELS_CALENDAR_MODULE = 5;
    /**
     * 缓存默认模块
     */
    public const COMMON_MODULE = 6;
    /**
     * 统计模块
     */
    public const STATIC_MODULE = 7;
    /**
     * 审评标签初始化
     */
    public const GLOBAL_LABEL_INIT_MODULE = 100;
    /**
     * 审评标签模块
     */
    public const GLOBAL_LABEL_MODULE = 713;
    /**
     * 审评标签筛选模块
     */
    public const GLOBAL_FILTER_MODULE = 10;
    /**
     * 分享模块
     */
    public const LINK_MODULE = 30;
    /**
     * 浏览记录模块
     */
    public const LINK_VISIT_MODULE = 12;
    /**
     * 命令脚本限制
     */
    public const COMMAND_MODULE = 111;
    /**
     * 第三方登录token
     */
    public const THIRD_TOKEN_MODULE = 120;
    /**
     * 飞书
     */
    public const LOGIN_BY_FLY_BOOK = 50;

    /**
     * 第三方ticket 交互
     */
    public const THIRD_WITH_TICKET_INFO = 62;

    /**
     * 请求锁
     */
    public const REQUEST_LIMIT_MODULE = 101;
    public const REQUEST_MODULE = 15;

    public const SHARE_LIST_MODULE = 321;
    /**
     * 统计队列
     */
    public const STATISTICAL_ASYNC_MODULE = 21;
    /**
     * 缓存刷新队列
     */
    public const CACHE_FUNC_FLUSH_MODULE = 210;
}
