<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use App\Dto\Users\LoginData;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\LaravelData\WithData;

class Login extends FormRequest
{
    /** @use WithData<LoginData> */
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
        return LoginData::class;
    }
}
