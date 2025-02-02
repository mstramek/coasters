<?php

namespace App\Services;

use App\Events\EventTypeEnum;
use App\Models\CheckResult;
use App\Models\Coaster;
use Clue\React\Redis\Client;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Entity\Cast\JsonCast;
use CodeIgniter\Events\Events;
use Config\Services;
use DateTimeImmutable;
use Exception;

class StatsService
{
    public function printAll(array $messageData): void
    {
        $this->readAllStats(
            function (?array $stats = null, ?Exception $e = null) use ($messageData) {
                if ($stats) {
                    $statsData = $stats
                        ? array_combine(
                            array_filter($stats, fn ($k) => $k % 2 === 0, ARRAY_FILTER_USE_KEY),
                            array_filter($stats, fn ($k) => $k % 2 !== 0, ARRAY_FILTER_USE_KEY)
                        )
                        : null;

                    ksort($statsData);

                    CLI::write(PHP_EOL . '---------------------------------');
                    CLI::write(sprintf(
                        '[Godzina %s]',
                        (new DateTimeImmutable($messageData[PubSubService::OCCURRED_ON]))->format('H:i'))
                    );

                    foreach ($statsData as $coasterId => $statDataString) {
                        $statData = JsonCast::get($statDataString, ['array']);
                        $coaster = $statData[CheckResult::COASTER];
                        $calculations = $statData[CheckResult::CALCULATIONS];

                        CLI::write(PHP_EOL . sprintf('[Kolejka %s]', $coasterId));

                        CLI::write(sprintf(
                            "\tGodziny działania: %s - %s",
                            $coaster[Coaster::HOURS_FROM],
                            $coaster[Coaster::HOURS_TO])
                        );
                        CLI::write(sprintf(
                            "\tLiczba wagonów: %d/%d",
                            $calculations[CheckResult::CALCULATIONS_NUMBER_OF_WAGONS],
                            $calculations[CheckResult::CALCULATIONS_EXPECTED_NUMBER_OF_WAGONS])
                        );
                        CLI::write(sprintf(
                            "\tLiczba wagonów wykorzystana na trasie: %d/%d",
                            $calculations[CheckResult::CALCULATIONS_USED_NUMBER_OF_WAGONS],
                            $calculations[CheckResult::CALCULATIONS_EXPECTED_NUMBER_OF_WAGONS])
                        );
                        CLI::write(sprintf(
                            "\tDostępny personel: %d/%d",
                            $coaster['numberOfPersonnel'],
                            $calculations[CheckResult::CALCULATIONS_EXPECTED_NUMBER_OF_PERSONNEL])
                        );
                        CLI::write(sprintf(
                            "\tPersonel wykorzystany: %d/%d",
                            $calculations[CheckResult::CALCULATIONS_USED_NUMBER_OF_PERSONNEL],
                            $calculations[CheckResult::CALCULATIONS_EXPECTED_NUMBER_OF_PERSONNEL])
                        );
                        CLI::write(sprintf(
                            "\tKlienci dziennie: %d/%d",
                            $calculations[CheckResult::CALCULATIONS_POSSIBLE_NUMBER_OF_CUSTOMERS],
                            $coaster[Coaster::NUMBER_OF_CUSTOMERS])
                        );
                    }
                } elseif ($e) {
                    log_message('error', $e->getMessage());
                }
            },
        );
    }

    public function saveStatsAsync(array $data): string
    {
        /** @var Client $asyncRedis */
        $asyncRedis = single_service(Services::ASYNC_REDIS);

        $coasterId = $data[CheckResult::COASTER_ID];

        $asyncRedis
            ->hset(static::key(), $coasterId, JsonCast::set($data))
            ->then(function () use ($data, $coasterId) {
                log_message('info', sprintf('Coaster stats updated [%s]', $coasterId));
                Events::trigger(EventTypeEnum::COASTER_STATS_UPDATED->value, $coasterId);
            })->catch(function ($error) {
                log_message('error', $error);
            })->always(function () use ($asyncRedis) {
                $asyncRedis->end();
            });

        return $coasterId;
    }

    public function readAllStats(callable $callback): void
    {
        /** @var Client $asyncRedis */
        $asyncRedis = single_service(Services::ASYNC_REDIS);

        $asyncRedis
            ->hgetall(static::key())
            ->then(
                function ($values) use ($callback) {
                    $callback($values);
                },
                fn (Exception $e) => $callback(null, $e)
            )->finally(function () use ($asyncRedis) {
                $asyncRedis->end();
            });
    }

    public static function key(): string
    {
        return 'coaster_stats';
    }
}