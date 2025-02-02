<?php

namespace App\Commands;

use App\Events\ChannelEnum;
use App\Events\EventGroupEnum;
use App\Events\EventTypeEnum;
use App\Services\CoasterCheckerService;
use App\Services\PubSubService;
use App\Services\StatsService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;
use Exception;

class CoasterCheckerCommand extends BaseCommand
{
    /** @var @inheritDoc */
    protected $group = 'App';
    /** @var @inheritDoc */
    protected $name = 'coaster:checker';

    /**
     * @inheritDoc
     */
    public function run(array $params)
    {
        $asyncRedis = single_service(Services::ASYNC_REDIS);

        $asyncRedis
            ->subscribe(ChannelEnum::COASTER_EVENTS->value)
            ->then(
                function () use ($asyncRedis) {
                    $asyncRedis->on('message', function ($channel, $message) {
                        $messageData = json_decode($message, true);
                        $eventType = EventTypeEnum::tryFrom($messageData[PubSubService::TYPE]);

                        log_message('debug', sprintf('Event occurred: %s', $eventType->value));

                        if ($eventType->inGroup(EventGroupEnum::COASTER_STATS)) {
                            /** @var StatsService $statsService */
                            $statsService = service(Services::STATS);
                            $statsService->printAll($messageData);
                        } elseif ($eventType->inGroup(EventGroupEnum::COASTER_DATA)) {
                            $targetId = $messageData[PubSubService::TARGET_ID];

                            /** @var CoasterCheckerService $coasterChecker */
                            $coasterChecker = service(Services::COASTER_CHECKER);
                            $coasterChecker->checkByCoaster($targetId);
                        } else {
                            // NOP
                        }
                    });
                },
                function (Exception $e) {
                    CLI::error($e->getMessage());;
                    log_message('error', $e->getMessage());
                }
            );
    }
}
