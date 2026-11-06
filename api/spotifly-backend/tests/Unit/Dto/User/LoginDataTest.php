<?php

declare(strict_types=1);

namespace Tests\Unit\Dto\User;

use App\Dto\Users\LoginData;
use Tests\TestCase;

class LoginDataTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testValidationRules(): void
    {
        $validationRules = LoginData::rules();

        $expected = [
            'email' => [
                'email',
                'max:100',
                'required',
            ],
            'password' => [
                'string',
                'required',
            ],
        ];

        $this->assertEquals(
            array_map('array_values', $expected),
            array_map('array_values', $validationRules)
        );
    }
}
