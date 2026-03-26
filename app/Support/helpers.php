<?php

use App\Support\CountryList;

if (! function_exists('cachedCountries')) {
    /**
     * Get and cache countries forever.
     *
     * @return array<int, string>
     */
    function cachedCountries(): array
    {
        /** @var array<int, string> $countries */
        $countries = cache()->rememberForever('countries', fn () => CountryList::names());

        return $countries;
    }
}
