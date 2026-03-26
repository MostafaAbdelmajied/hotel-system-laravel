<?php

namespace App\Support;

class CountryList
{
    /**
     * Get country names from umpirsky/country-list package.
     *
     * @return array<int, string>
     */
    public static function names(): array
    {
        /** @var array<string, string> $countries */
        $countries = require base_path('vendor/umpirsky/country-list/data/en/country.php');

        return array_values($countries);
    }
}
