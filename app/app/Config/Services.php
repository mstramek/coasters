<?php

namespace Config;

use App\Models\BaseModel;
use App\Models\Coaster;
use App\Models\Wagon;
use App\Services\CoasterCheckerService;
use App\Services\CoasterService;
use App\Services\PubSubService;
use App\Services\StatsService;
use App\Services\WagonService;
use Clue\React\Redis\Client;
use Clue\React\Redis\Factory;
use CodeIgniter\Config\BaseService;
use Redis;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    public const SYNC_REDIS = 'syncRedis';
    public const ASYNC_REDIS = 'asyncRedis';
    public const COASTER_CHECKER = 'coasterChecker';
    public const COASTER_SERVICE = 'coasterService';
    public const WAGON_SERVICE = 'wagonService';
    public const COASTER_PUB_SUB = 'coasterPubSub';
    public const STATS = 'stats';
    public const COASTER_MODEL = 'coasterModel';
    public const WAGON_MODEL = 'wagonModel';

    /** @uses self::SYNC_REDIS */
    public static function syncRedis($getShared = true): Redis
    {
        if ($getShared) {
            /** @var Redis $redis */
            $redis = static::getSharedInstance(self::SYNC_REDIS);
        } else {
            $redis = new Redis();
            $redis->connect(env('REDIS_HOST'), env('REDIS_PORT'));
            $redis->select(env('REDIS_DB'));
        }

        return $redis;
    }

    /** @uses self::ASYNC_REDIS */
    public static function asyncRedis($getShared = false): Client
    {
        /** @var Client $asyncRedis */
        $asyncRedis = $getShared ?
            static::getSharedInstance(self::ASYNC_REDIS)
            : (new Factory())->createLazyClient(sprintf('redis://%s:%s', env('REDIS_HOST'), env('REDIS_PORT')));
        $asyncRedis->select(env('REDIS_DB'));

        return $asyncRedis;
    }

    /** @uses self::COASTER_CHECKER */
    public static function coasterChecker($getShared = true): CoasterCheckerService
    {
        /** @var CoasterCheckerService $coasterChecker */
        $coasterChecker = $getShared ? static::getSharedInstance(self::COASTER_CHECKER) : new CoasterCheckerService();

        return $coasterChecker;
    }

    /** @uses self::COASTER_SERVICE */
    public static function coasterService($getShared = true): CoasterService
    {
        /** @var CoasterService $service */
        $service = $getShared ? static::getSharedInstance(self::COASTER_SERVICE) : new CoasterService();

        return $service;
    }

    /** @uses self::WAGON_SERVICE */
    public static function wagonService($getShared = true): WagonService
    {
        /** @var WagonService $service */
        $service = $getShared ? static::getSharedInstance(self::WAGON_SERVICE) : new WagonService();

        return $service;
    }

    /** @uses self::COASTER_PUB_SUB */
    public static function coasterPubSub($getShared = true): PubSubService
    {
        /** @var PubSubService $service */
        $service = $getShared ? static::getSharedInstance(self::COASTER_PUB_SUB) : new PubSubService();

        return $service;
    }

    /** @uses self::STATS */
    public static function stats($getShared = true): StatsService
    {
        /** @var StatsService $service */
        $service = $getShared ? static::getSharedInstance(self::STATS) : new StatsService();

        return $service;
    }

    /** @uses self::COASTER_MODEL */
    public static function coasterModel($getShared = true): BaseModel
    {
        /** @var Coaster $model */
        $model = $getShared ? static::getSharedInstance(self::COASTER_MODEL) : new Coaster();

        return $model;
    }

    /** @uses self::WAGON_MODEL */
    public static function wagonModel($getShared = true): BaseModel
    {
        /** @var Wagon $model */
        $model = $getShared ? static::getSharedInstance(self::WAGON_MODEL) : new Wagon();

        return $model;
    }
}
