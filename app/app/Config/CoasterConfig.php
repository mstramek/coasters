<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class CoasterConfig extends BaseConfig
{
    public const NAME = 'CoasterConfig';
    public int $excessClientsFactor = 2;
    public int $requiredCoasterPersonnel = 1;
    public int $requiredWagonPersonnel = 2;
    public int $wagonBreakTime = 300; // seconds
    public int $wagonMinSpeed = 1; // m/s
    public int $wagonMaxSpeed = 30; // m/s
    public int $wagonMinNumberOfSeats = 6;
    public int $wagonMaxNumberOfSeats = 50;
}
