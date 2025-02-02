<?php

namespace App\Services;

use App\Events\EventTypeEnum;
use App\Models\CheckResult;
use App\Models\Coaster;
use CodeIgniter\Events\Events;
use Config\CoasterConfig;
use Config\Services;
use Exception;

class CoasterCheckerService
{
    public function checkByCoaster(string $coasterId): void
    {
        /** @var WagonService $wagonService */
        $wagonService = service(Services::WAGON_SERVICE);

        $wagonService->readWagonsByCoasterIdAsync(
            $coasterId,
            function (?array $coaster = null, ?array $wagons = null, ?Exception $error = null) use ($coasterId) {
                if ($error) {
                    log_message('error', $error);
                } elseif (empty($coaster)) {
                    log_message('error', 'Trying to analyze coaster that doesn\'t exists');
                } else {
                    $this->analyze($coaster, $wagons);
                }
            }
        );
    }

    protected function analyze(array $coaster, array $wagons = []): void
    {
        $coasterId = $coaster[Coaster::ID];
        $checkDateTime = date('Y-m-d H:i:s');

        /** @var CoasterConfig $coasterConfig */
        $coasterConfig = config(CoasterConfig::NAME);
        $configWagonBreakTime = $coasterConfig->wagonBreakTime;
        $configExcessClientFactor = $coasterConfig->excessClientsFactor;
        $configRequiredCoasterPersonnel = $coasterConfig->requiredCoasterPersonnel;
        $configRequiredWagonPersonnel = $coasterConfig->requiredWagonPersonnel;

        $numberOfPersonnel = $coaster[Coaster::NUMBER_OF_PERSONNEL];
        $coasterNumberOfCustomers = $coaster[Coaster::NUMBER_OF_CUSTOMERS];
        $coasterRouteLength = $coaster[Coaster::ROUTE_LENGTH];
        $coasterHoursFrom = $coaster[Coaster::HOURS_FROM];
        $coasterHoursTo = $coaster[Coaster::HOURS_TO];
        $coasterHoursFromTime = strtotime($coasterHoursFrom);
        $coasterHoursToTime = strtotime($coasterHoursTo);

        $wagonMinSpeed = $coasterConfig->wagonMinSpeed;
        $wagonMaxSpeed = $coasterConfig->wagonMaxSpeed;
        $wagonMinNumberOfSeats = $coasterConfig->wagonMinNumberOfSeats;
        $wagonMaxNumberOfSeats = $coasterConfig->wagonMaxNumberOfSeats;

        $numberOfSeats = 0;
        $numberOfWagons = count($wagons);
        $trainSpeed = null;
        $problems = [];

        if ($numberOfWagons === 0) {
            $problems[] = 'Brak wagonów. Kolejka nie może działać.';
            $trainSpeed = $wagonMinSpeed;
            $numberOfSeats = $wagonMinNumberOfSeats;
        } else {
            foreach ($wagons as $wagon) {
                $wagonSpeed = max($wagonMinSpeed, min($wagonMaxSpeed, $wagon['speed']));
                $wagonSeats = max($wagonMinNumberOfSeats, min($wagonMaxNumberOfSeats, $wagon['numberOfSeats']));

                $numberOfSeats += $wagonSeats;
                $trainSpeed = $trainSpeed === null ? $wagonSpeed : min($trainSpeed, $wagonSpeed);
            }
        }

        $cycleTime = ($coasterRouteLength / $trainSpeed) + $configWagonBreakTime;
        $operatingTime = $coasterHoursToTime - $coasterHoursFromTime;
        $cycles = floor($operatingTime / $cycleTime);

        $usedNumberOfPersonnel = min(
            $numberOfPersonnel,
            $numberOfWagons * $configRequiredWagonPersonnel + $configRequiredCoasterPersonnel
        );
        $usedNumberOfWagons = min(
            $numberOfWagons,
            floor(($usedNumberOfPersonnel - $configRequiredCoasterPersonnel) / $configRequiredWagonPersonnel)
        );

        $expectedNumberOfWagons = ceil(($coasterNumberOfCustomers / ($numberOfSeats / max(1, $numberOfWagons))) / $cycles);
        $expectedNumberOfPersonnel = $expectedNumberOfWagons * $configRequiredWagonPersonnel + $configRequiredCoasterPersonnel;

        $possibleNumberOfCustomers = floor($usedNumberOfWagons * ($numberOfSeats / max(1, $numberOfWagons)) * $cycles);

        if ($numberOfWagons < $expectedNumberOfWagons) {
            $problems[] = "Brakuje wagonów w puli - dostępnych $numberOfWagons, potrzeba $expectedNumberOfWagons";
        } elseif ($usedNumberOfWagons < $expectedNumberOfWagons) {
            $problems[] = "Brak wagonów - obecnie $usedNumberOfWagons, potrzeba $expectedNumberOfWagons";
        } elseif ($numberOfWagons > $usedNumberOfWagons) {
            $problems[] = "Nie wszystkie wagony z puli wyjechały na trasę - dostępnych $numberOfWagons, na trasie $usedNumberOfWagons";
        } elseif ($usedNumberOfWagons > $expectedNumberOfWagons) {
            $problems[] = "Zbyt dużo wagonów na trasie - obecnie $usedNumberOfWagons, potrzeba na trasie $expectedNumberOfWagons o najlepszych parametrach";
        }

        if ($numberOfPersonnel < $expectedNumberOfPersonnel) {
            $problems[] = "Brakuje personelu w puli - dostępnych $numberOfPersonnel, potrzeba $expectedNumberOfPersonnel";
        } elseif ($usedNumberOfPersonnel < $expectedNumberOfPersonnel) {
            $problems[] = "Za mało personelu - obecnie $usedNumberOfPersonnel, potrzeba $expectedNumberOfPersonnel";
        } elseif ($numberOfPersonnel > $usedNumberOfPersonnel) {
            $problems[] = "Nie cały personel dostępny w puli obsługuje kolejkę - dostępnych $numberOfPersonnel, obsługuje $usedNumberOfPersonnel";
        } elseif ($usedNumberOfPersonnel > $expectedNumberOfPersonnel) {
            $problems[] = "Zbyt dużo personelu - obecnie $usedNumberOfPersonnel, potrzeba $expectedNumberOfPersonnel w wagonach o najlepszych parametrach";
        }

        if ($possibleNumberOfCustomers < $coasterNumberOfCustomers) {
            $problems[] = "Możliwa liczba klientów - $possibleNumberOfCustomers, oczekiwano $coasterNumberOfCustomers";
        } elseif ($possibleNumberOfCustomers > $coasterNumberOfCustomers * $configExcessClientFactor) {
            $problems[] = "Zbyt duża obsługa klientów - możliwe $possibleNumberOfCustomers, oczekiwano $coasterNumberOfCustomers";
        }

        $status = empty($problems);

        $summary = $status
            ? 'Status: OK'
            : sprintf(
                "Problem: %s",
                join('; ', array_map(fn ($problem) => lcfirst($problem), $problems))
            );

        $checkResult = [
            CheckResult::DATE_TIME => $checkDateTime,
            CheckResult::COASTER_ID => $coasterId,
            CheckResult::COASTER => $coaster,
            CheckResult::WAGONS => $wagons,
            CheckResult::CONFIG => [
                CheckResult::CONFIG_WAGON_BREAK_TIME => $configWagonBreakTime,
                CheckResult::CONFIG_EXCESS_CLIENT_FACTOR => $configExcessClientFactor,
                CheckResult::CONFIG_REQUIRED_COASTER_PERSONNEL => $configRequiredCoasterPersonnel,
                CheckResult::CONFIG_REQUIRED_WAGON_PERSONNEL => $configRequiredWagonPersonnel,
            ],
            CheckResult::CALCULATIONS => [
                CheckResult::CALCULATIONS_NUMBER_OF_WAGONS => $numberOfWagons,
                CheckResult::CALCULATIONS_USED_NUMBER_OF_WAGONS => $usedNumberOfWagons,
                CheckResult::CALCULATIONS_EXPECTED_NUMBER_OF_WAGONS => $expectedNumberOfWagons,
                CheckResult::CALCULATIONS_USED_NUMBER_OF_PERSONNEL => $usedNumberOfPersonnel,
                CheckResult::CALCULATIONS_EXPECTED_NUMBER_OF_PERSONNEL => $expectedNumberOfPersonnel,
                CheckResult::CALCULATIONS_POSSIBLE_NUMBER_OF_CUSTOMERS => $possibleNumberOfCustomers,
            ],
            CheckResult::PROBLEMS => $problems,
            CheckResult::STATUS => $status,
            CheckResult::SUMMARY => $summary,
        ];

        Events::trigger(EventTypeEnum::COASTER_CHECKED->value, $checkResult);
    }
}