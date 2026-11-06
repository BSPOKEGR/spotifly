<?php

declare(strict_types=1);

namespace App\Contracts;

use Closure;

interface ValidationRuleInterface extends GenericValidationRuleInterface
{
    /**
     * @param \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void;
}
