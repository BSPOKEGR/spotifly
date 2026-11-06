<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    /**
     * The datatable.
     *
     * @var string
     */
    protected $fillable = [
        'parcel_id',
        'user_id',
        'date',
        'type',
        'severity',
        'message',
        'data',
    ];

    /**
     * The attributes that are hidden.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that are cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'data' => 'array',
        'read' => 'boolean',
    ];

        /**
        * Get the user that owns the alert.
        */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
