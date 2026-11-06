<?php

declare(strict_types=1);

namespace Tests\Unit\Dto\User;

use App\Dto\Users\RegisterData;
use Tests\TestCase;

class RegisterDataTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testValidationRules(): void
    {
        $validationRules = RegisterData::rules();

        $expected = [
            'name' => [
                'string',
                'min:3',
                'max:50',
                'required',
            ],
            'email' => [
                'email',
                'max:100',
                'required',
                'unique:users,email',
            ],
            'password' => [
                'string',
                'min:8',
                'required'
            ],
        ];

        $this->assertEquals($expected, $validationRules);
    }
}
