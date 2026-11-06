<?php

declare(strict_types=1);

namespace Tests\Unit\Dto\User;

use App\Dto\Users\ForgotPasswordData;
use Tests\TestCase;

class ForgotPasswordDataTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testValidationRules(): void
    {
        $validationRules = ForgotPasswordData::rules();

        $expected = [
            'email' => [
                'email',
                'max:100',
                'required',
            ],
        ];

        $this->assertEquals(
            array_map('array_values', $expected),
            array_map('array_values', $validationRules)
        );
    }
}
