<?php

declare(strict_types=1);

namespace App\Rules;

use App\Contracts\GenericValidationRuleInterface;
use Illuminate\Validation\Rules\Enum;

class CustomEnum extends Enum implements GenericValidationRuleInterface
{
    public function __toString(): string
    {
        return __CLASS__.'_'.$this->type;
    }
}
