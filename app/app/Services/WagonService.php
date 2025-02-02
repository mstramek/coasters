<?php

namespace App\Services;

use App\Events\EventTypeEnum;
use App\Models\Wagon;
use Clue\React\Redis\Client;
use CodeIgniter\Entity\Cast\JsonCast;
use CodeIgniter\Events\Events;
use Config\Services;
use Exception;
use React\Promise;
use Redis;

class WagonService
{
    public function saveWagonAsync(array $data, string $coasterId): string
    {
        /** @var CoasterService $coasterService */
        $coasterService = service(Services::COASTER_SERVICE);
        /** @var Client $asyncRedis */
        $asyncRedis = single_service(Services::ASYNC_REDIS);

        $wagonId = $this->generateId($coasterId);
        $dataToSave = $data + [Wagon::ID => $wagonId, Wagon::COASTER_ID => $coasterId];
        $coasterKey = $coasterService::keyById($coasterId);

        $asyncRedis
            ->exists($coasterKey)
            ->then(function ($exists) use ($coasterId, $dataToSave, $wagonId) {
                if ($exists) {
                    /** @var Client $asyncRedis */
                    $asyncRedis = single_service(Services::ASYNC_REDIS);
                    $asyncRedis
                        ->rpush(static::keyById($coasterId), JsonCast::set($dataToSave))
                        ->then(function () use ($dataToSave, $coasterId, $wagonId) {
                            log_message(
                                'info',
                                sprintf('Wagon has been created [%s, %s]', $coasterId, $wagonId)
                            );
                            Events::trigger(EventTypeEnum::WAGON_CREATED->value, $dataToSave);
                        })->otherwise(function (Exception $e) use ($coasterId, $wagonId) {
                            log_message(
                                'error',
                                sprintf(
                                    'Error occurred during creating wagon [%s, %s]: %s',
                                    $coasterId,
                                    $wagonId,
                                    $e->getMessage()
                                )
                            );
                        })->always(function () use ($asyncRedis) {
                            $asyncRedis->end();
                        });
                } else {
                    log_message(
                        'error',
                        sprintf('Can\'t create and assign wagon to not existed coaster [%s, %s]', $coasterId, $wagonId)
                    );
                }
            })->otherwise(function (Exception $e) use ($coasterId) {
                log_message(
                    'error',
                    sprintf('Error occurred during checking coaster [%s]: %s', $coasterId, $e->getMessage())
                );
            })->always(function () use ($asyncRedis) {
                $asyncRedis->end();
            });

        return $wagonId;
    }

    public function deleteWagon(string $coasterId, string $wagonId): bool
    {
        /** @var Redis $syncRedis */
        $syncRedis = service(Services::SYNC_REDIS);

        $result = false;
        $coasterWagonsId = static::keyById($coasterId);
        $list = $syncRedis->lrange($coasterWagonsId, 0, -1);

        foreach ($list as $item) {
            $wagon = json_decode($item, true);
            if ($wagon[Wagon::ID] === $wagonId) {
                $result = (bool)$syncRedis->lrem($coasterWagonsId, $item, 0);
                Events::trigger(EventTypeEnum::WAGON_DELETED->value, $wagon);
                break;
            }
        }

        return $result;
    }

    public function readWagonsByCoasterIdAsync(string $coasterId, callable $callback): void
    {
        /** @var Client $asyncRedis */
        $asyncRedis = single_service(Services::ASYNC_REDIS);

        $coasterPromise = $asyncRedis->hgetall(CoasterService::keyById($coasterId));
        $wagonsPromise = $asyncRedis->lrange(static::keyById($coasterId), 0, -1);

        Promise\all([$coasterPromise, $wagonsPromise])
            ->then(
                function ($values) use ($callback) {
                    list($coasterData, $wagonsData) = $values;

                    $coaster = $coasterData
                        ? array_combine(
                            array_filter($coasterData, fn ($k) => $k % 2 === 0, ARRAY_FILTER_USE_KEY),
                            array_filter($coasterData, fn ($k) => $k % 2 !== 0, ARRAY_FILTER_USE_KEY)
                        )
                        : null;

                    $wagons = array_map(
                        function ($wagonData) {
                            return json_decode($wagonData, true);
                        },
                        $wagonsData
                    );

                    $callback($coaster, $wagons);
                },
                fn (Exception $e) => $callback(null, null, $e)
            )->finally(function () use ($asyncRedis) {
                $asyncRedis->end();
            });
    }

    public static function keyById(string $coasterId): string
    {
        return 'wagons:' . $coasterId;
    }

    protected function generateId(string $coasterId): string
    {
        return 'W' . service(Services::SYNC_REDIS)->incr(sprintf('coaster_%s_wagons_counter', $coasterId));
    }
}