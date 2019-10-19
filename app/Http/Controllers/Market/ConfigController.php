<?php


namespace App\Http\Controllers\Market;


use Carbon\Carbon;

class ConfigController
{
    public function index()
    {
        $config = [
            "supports_search"          => true,
            "supports_group_request"   => false,
            "supports_marks"           => true,
            "supports_timescale_marks" => true,
            "supports_time"            => true,
            "exchanges"                => [
                ["value" => "", "name" => "All Exchanges", "desc" => ""],
                ["value" => "HULK", "name" => "HULK", "desc" => "HULK"],
            ],
            "symbols_types"            => [
                ["name" => "All types", "value" => "",],
                ["name" => "Stock", "value" => "Stock",],
                ["name" => "Index", "value" => "Index",],
            ],
            "supported_resolutions"    => [
                "1",
                "15",
                "60",
                "240",
                "D",
                "W"
            ],
            "intraday_multipliers"     => [
                "1",
                "15",
                "60",
                "240",
                "D",
                "W"
            ]
        ];

        return $config;
    }

    public function time()
    {
        return Carbon::now()->timestamp;
    }

}
