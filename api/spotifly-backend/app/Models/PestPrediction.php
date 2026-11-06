<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PestPrediction extends Model
{
    protected $table = 'pest_predictions';

    protected $fillable = [
        'parcel_id',
        'response_data',
        'fetched_at',
    ];

    protected $casts = [
        'response_data' => 'array',
        'fetched_at' => 'datetime',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
