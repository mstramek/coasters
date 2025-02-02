<?php

namespace App\Models;

use Config\CoasterConfig;

class Wagon extends BaseModel
{
    public const ID = 'id';
    public const COASTER_ID = 'coasterId';
    public const NUMBER_OF_SEATS = 'numberOfSeats';
    public const SPEED = 'speed';

    public function __construct()
    {
        parent::__construct();

        /** @var CoasterConfig $coasterConfig */
        $coasterConfig = config(CoasterConfig::NAME);

        $this->rules = [
            self::NUMBER_OF_SEATS => sprintf(
                'required|is_natural|greater_than_equal_to[%d]|less_than_equal_to[%d]',
                $coasterConfig->wagonMinNumberOfSeats,
                $coasterConfig->wagonMaxNumberOfSeats
            ),
            self::SPEED => sprintf(
                'required|greater_than_equal_to[%d]|less_than_equal_to[%d]',
                $coasterConfig->wagonMinSpeed,
                $coasterConfig->wagonMaxSpeed
            ),
        ];
    }
}
