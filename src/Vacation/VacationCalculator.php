<?php

declare(strict_types=1);

namespace App\Vacation;

use App\Employee\Employee;

final readonly class VacationCalculator
{
    public const int DEFAULT_MINIMUM_DAYS = 26;

    /** @var list<VacationRule> */
    private array $rules;

    /**
     * @param VacationRule ...$rules applied in the given order to the yearly minimum
     */
    public function __construct(VacationRule ...$rules)
    {
        $this->rules = array_values($rules);
    }

    public function calculate(Employee $employee, Year $year): int
    {
        if ($year->isBefore($employee->contractStart)) {
            return 0;
        }

        $days = $employee->minimumVacationDays ?? self::DEFAULT_MINIMUM_DAYS;

        foreach ($this->rules as $rule) {
            $days = $rule->apply($days, $employee, $year);
        }

        return $days;
    }
}
