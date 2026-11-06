<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @use HasFactory<UserSettingFactory> */
class UserSetting extends Model
{
    /** @use HasFactory<UserSettingFactory> */
    use HasFactory;

    protected $table = 'user_settings';

    protected $fillable = [
        'language_id',
    ];

    /**
     * The attributes that are hidden.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_at',
        'updated_at',
        'user_id',
        'id'
    ];

    /**
     * Many-to-one association for user.
     *
     * @return BelongsTo<User, UserSetting>
     */
    public function user(): BelongsTo
    {
        /** @var BelongsTo<User, UserSetting> */
        return $this->belongsTo(User::class);
    }

    /**
     * Many-to-one association for user language.
     *
     * @return BelongsTo<Language, UserSetting>
     */
    public function language(): BelongsTo
    {
        /** @var BelongsTo<Language, UserSetting> */
        return $this->belongsTo(Language::class);
    }
}
