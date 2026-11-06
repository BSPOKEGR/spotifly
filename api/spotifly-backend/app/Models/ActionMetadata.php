<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActionMetadata extends Model
{
    protected $table = 'actions_metadata';

    protected $fillable = [
        'action_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
