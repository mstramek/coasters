<?php

namespace App\Models;

class Coaster extends BaseModel
{
    public const ID = 'id';
    public const NUMBER_OF_PERSONNEL = 'numberOfPersonnel';
    public const NUMBER_OF_CUSTOMERS = 'numberOfCustomers';
    public const ROUTE_LENGTH = 'routeLength';
    public const HOURS_FROM = 'hoursFrom';
    public const HOURS_TO = 'hoursTo';

    protected array $rules = [
        self::NUMBER_OF_PERSONNEL => 'required|is_natural_no_zero',
        self::NUMBER_OF_CUSTOMERS => 'required|is_natural_no_zero',
        self::ROUTE_LENGTH => 'required|is_natural_no_zero',
        self::HOURS_FROM => 'required|valid_date[H:i]',
        self::HOURS_TO => 'required|valid_date[H:i]',
    ];
}
