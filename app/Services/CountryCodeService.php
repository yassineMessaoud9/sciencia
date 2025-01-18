<?php

namespace App\Services;


use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PragmaRX\Countries\Package\Countries;

class CountryCodeService
{

    /**
     * @throws Exception
     */
    public function list()
    {
        try {
            $countryArray = [];
            $countries    = Countries::all();
            foreach ($countries as $key => $country) {
                if (isset($country['calling_codes'][0]) && isset($country['flag']['emoji'])) {
                    $countryArray[] = (object)[
                        'country_code' => $key,
                        'country_name' => $country['admin'] . ' (' . $key . ')',
                        'calling_code' => $country['calling_codes'][0]  == '+1201' ? '+1' : $country['calling_codes'][0],
                        'flag_emoji'   => $country['flag']['emoji'],
                    ];
                }
            }
            return ['data' => $countryArray];
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function show($country)
    {
        try {
            return Countries::where('cca3', $country)->first();
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }

    public function callingCode($callingCode)
    {
        $countries = Countries::all();
        $callingCodeArray = Collection::make($countries)
            ->filter(function ($country, $key) {
                return isset($country['calling_codes'][0]) && isset($country['flag']['emoji']);
            })
            ->mapWithKeys(function ($country, $key) {
                return [
                    $country['calling_codes'][0] => (object)[
                        'country_code' => $key,
                        'calling_code' => $country['calling_codes'][0],
                        'flag_emoji'   => $country['flag']['emoji'],
                    ]
                ];
            })
            ->toArray();
        if (array_key_exists($callingCode, $callingCodeArray)) {
            return ['data' => $callingCodeArray[$callingCode]];
        }
        try {
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception($exception->getMessage(), 422);
        }
    }
}