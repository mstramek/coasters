<?php

namespace App\Models;

class CheckResult
{
    public const DATE_TIME = 'dateTime';
    public const COASTER_ID = 'coasterId';
    public const COASTER = 'coaster';
    public const WAGONS = 'wagons';
    public const CONFIG = 'config';
    public const CALCULATIONS = 'calculations';
    public const PROBLEMS = 'problems';
    public const STATUS = 'status';
    public const SUMMARY = 'summary';
    public const CONFIG_WAGON_BREAK_TIME = 'wagonBreakTime';
    public const CONFIG_EXCESS_CLIENT_FACTOR = 'excessClientFactor';
    public const CONFIG_REQUIRED_COASTER_PERSONNEL = 'requiredCoasterPersonnel';
    public const CONFIG_REQUIRED_WAGON_PERSONNEL = 'requiredWagonPersonnel';
    public const CALCULATIONS_NUMBER_OF_WAGONS = 'numberOfWagons';
    public const CALCULATIONS_USED_NUMBER_OF_WAGONS = 'usedNumberOfWagons';
    public const CALCULATIONS_EXPECTED_NUMBER_OF_WAGONS = 'expectedNumberOfWagons';
    public const CALCULATIONS_USED_NUMBER_OF_PERSONNEL = 'usedNumberOfPersonnel';
    public const CALCULATIONS_EXPECTED_NUMBER_OF_PERSONNEL = 'expectedNumberOfPersonnel';
    public const CALCULATIONS_POSSIBLE_NUMBER_OF_CUSTOMERS = 'possibleNumberOfCustomers';
}