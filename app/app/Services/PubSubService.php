<?php

namespace App\Services;

use App\Events\ChannelEnum;
use App\Events\EventTypeEnum;
use App\Models\Coaster;
use App\Models\Wagon;
use Clue\React\Redis\Client;
use CodeIgniter\Entity\Cast\JsonCast;
use Config\Services;
use Exception;
use Ramsey\Uuid\Uuid;

class PubSubService
{
    public const ID = 'id';
    public const TYPE = 'type';
    public const TARGET_ID = 'targetId';
    public const OCCURRED_ON = 'occurredOn';
    public const PAYLOAD = 'payload';
    private const DEFAULT_CHANNEL = ChannelEnum::COASTER_EVENTS;

    public function publish(
        EventTypeEnum $eventType,
        string $targetId,
        array $data,
        ChannelEnum $channel = self::DEFAULT_CHANNEL
    ): void {
        /** @var Client $asyncRedis */
        $asyncRedis = single_service(Services::ASYNC_REDIS);

        $eventId = Uuid::uuid4()->toString();

        $asyncRedis->publish(
            $channel->value,
            JsonCast::set([
                self::ID => $eventId,
                self::TYPE => $eventType->value,
                self::TARGET_ID => $targetId,
                self::OCCURRED_ON => date('Y-m-d H:i:s'),
                self::PAYLOAD => $data,
            ])
        )->then(
            function () use ($eventId, $eventType, $targetId) {
                log_message(
                    'debug',
                    sprintf(
                        'Event published [eventId: %s, coasterId: %s, %s]',
                        $eventId,
                        $targetId,
                        $eventType->value
                    )
                );
            },
            function (Exception $e) use ($eventId, $eventType, $targetId){
                log_message(
                    'error',
                    sprintf(
                        'Publish of event failed: [eventId: %s, coasterId: %s, %s]: %s',
                        $eventId,
                        $targetId,
                        $eventType->value,
                        $e->getMessage()
                    )
                );
            }
        )->always(function () use ($asyncRedis) {
            $asyncRedis->end();
        });
    }

    public function publishCoasterCreated(array $data): void
    {
        $this->publish(EventTypeEnum::COASTER_CREATED, $data[Coaster::ID], $data);
    }

    public function publishCoasterPatched(array $data): void
    {
        $this->publish(EventTypeEnum::COASTER_PATCHED, $data[Coaster::ID], $data);
    }

    public function publishWagonCreated(array $data): void
    {
        $this->publish(EventTypeEnum::WAGON_CREATED, $data[Wagon::COASTER_ID], $data);
    }

    public function publishWagonDeleted(array $data): void
    {
        $this->publish(EventTypeEnum::WAGON_DELETED, $data[Wagon::COASTER_ID], $data);
    }

    public function publishStatsSaved(string $coasterId): void
    {
        $this->publish(EventTypeEnum::COASTER_STATS_UPDATED, $coasterId, []);
    }
}