<?php

declare(strict_types=1);

namespace Tests\Unit\Dto\User;

use App\Dto\Users\ValidationRules;
use Tests\TestCase;

class ValidationRulesTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testValidationRules(): void
    {
        $nameRules = ValidationRules::Name->rules();

        $expected = [
            'string',
            'min:3',
            'max:50',
        ];

        $this->assertEquals($expected, $nameRules);

        $emailRules = ValidationRules::Email->rules();

        $expected = [
            'email',
            'max:100',
        ];

        $this->assertEquals($expected, $emailRules);

        $passwordRules = ValidationRules::Password->rules();

        $expected = [
            'string',
            'min:8',
        ];

        $this->assertEquals($expected, $passwordRules);

        $tokenRules = ValidationRules::Token->rules();

        $expected = [
            'string'
        ];

        $this->assertEquals($expected, $tokenRules);
    }
}
