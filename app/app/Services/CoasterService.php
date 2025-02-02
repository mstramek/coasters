<?php

namespace App\Services;

use App\Events\EventTypeEnum;
use App\Models\Coaster;
use Clue\React\Redis\Client;
use CodeIgniter\Events\Events;
use Config\Services;
use Redis;

class CoasterService
{
    public function checkIfCoasterExists(string $id): bool
    {
        /** @var Redis $redis */
        $redis = service(Services::SYNC_REDIS);

        return (bool)$redis->exists(static::keyById($id));
    }

    public function saveCoasterAsync(array $data, ?string $customId = null): string
    {
        /** @var Client $asyncRedis */
        $asyncRedis = single_service(Services::ASYNC_REDIS);

        $create = $customId === null;
        $id = $customId ?? $this->generateId();
        $key = static::keyById($id);

        $asyncRedis
            ->exists($key)
            ->then(function ($exists) use ($create, $key, $id, $data) {
                if ($exists && $create) {
                    log_message('error', 'Coaster creation failed. Coaster with same id currently exists.');
                } elseif (!$exists && !$create) {
                    log_message('error', 'Coaster update failed. Coaster not exists.');
                } else {
                    /** @var Client $asyncRedis */
                    $asyncRedis = single_service(Services::ASYNC_REDIS);
                    $dataToSave = $data + [Coaster::ID => $id];

                    $asyncRedis
                        ->hset($key, ...array_merge(...array_map(null, array_keys($dataToSave), $dataToSave)))
                        ->then(function () use ($create, $dataToSave, $id) {
                            log_message('info', sprintf('Coaster %s [%s]', $create ? 'created' : 'updated', $id));
                            Events::trigger(
                                ($create ? EventTypeEnum::COASTER_CREATED : EventTypeEnum::COASTER_PATCHED)->value,
                                $dataToSave
                            );
                        })->catch(function ($error) {
                            log_message('error', $error);
                        })->always(function () use ($asyncRedis) {
                            $asyncRedis->end();
                        });
                }
            })->catch(function ($error) {
                log_message('error', $error);
            })->always(function () use ($asyncRedis) {
                $asyncRedis->end();
            });

        return $id;
    }

    public static function keyById(mixed $id): string
    {
        return 'coaster:' . $id;
    }

    protected function generateId(): string
    {
        return 'A' . service(Services::SYNC_REDIS)->incr('coasters_counter');
    }
}