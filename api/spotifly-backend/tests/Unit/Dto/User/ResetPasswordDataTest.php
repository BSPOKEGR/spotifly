<?php

declare(strict_types=1);

namespace Tests\Unit\Dto\User;

use App\Dto\Users\ResetPasswordData;
use Tests\TestCase;

class ResetPasswordDataTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testValidationRules(): void
    {
        $validationRules = ResetPasswordData::rules();

        $expected = [
            'password' => [
                'string',
                'min:8',
                'required'
            ],
            'email' => [
                'email',
                'max:100',
                'required',
            ],
            'token' => [
                'string',
                'required'
            ],
        ];

        $this->assertEquals($expected, $validationRules);
    }
}
