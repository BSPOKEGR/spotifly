<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParcelWeatherForecast extends Model
{
    protected $fillable = [
        'parcel_id',
        'forecast_time',
        'ambient_temperature',
        'ambient_humidity',
        'wind_speed',
        'wind_direction',
        'precipitation',
    ];

    protected $casts = [
        'forecast_time' => 'datetime',
        'ambient_temperature' => 'float',
        'ambient_humidity' => 'integer',
        'wind_speed' => 'float',
        'wind_direction' => 'integer',
        'precipitation' => 'float',
    ];
}
