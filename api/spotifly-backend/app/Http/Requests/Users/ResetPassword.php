<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use App\Dto\Users\ResetPasswordData;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\LaravelData\WithData;

class ResetPassword extends FormRequest
{
    /** @use WithData<ResetPasswordData> */
    use WithData;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function dataClass(): string
    {
        return ResetPasswordData::class;
    }
}
