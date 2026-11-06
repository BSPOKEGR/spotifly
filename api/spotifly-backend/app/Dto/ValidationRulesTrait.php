<?php

declare(strict_types=1);

namespace App\Dto;

use App\Contracts\GenericValidationRuleInterface;

trait ValidationRulesTrait
{
    /**
     * @return array<string|GenericValidationRuleInterface>
     */
    public function addRequired(): array
    {
        return $this->addRules('required');
    }

    /**
     * @return array<string|GenericValidationRuleInterface>
     */
    public function addNullable(): array
    {
        return $this->addRules('nullable');
    }

    /**
     * @return array<string|GenericValidationRuleInterface>
     */
    public function addRules(string|GenericValidationRuleInterface ...$rules): array
    {
        $rules = \array_merge($this->rules(), $rules);

        // remove duplicates
        return \array_unique($rules);
    }

    /**
     * @return array<string|GenericValidationRuleInterface>
     */
    public function removeRules(string|GenericValidationRuleInterface ...$rules): array
    {
        return \array_diff($this->rules(), $rules);
    }

    /**
     * @param array<string|GenericValidationRuleInterface> $addRules
     * @param array<string|GenericValidationRuleInterface> $removeRules
     *
     * @return array <string|GenericValidationRuleInterface>
     */
    public function updateRules(array $addRules, array $removeRules): array
    {
        $rules = $this->addRules(...$addRules);

        return \array_diff($rules, $removeRules);
    }
}
