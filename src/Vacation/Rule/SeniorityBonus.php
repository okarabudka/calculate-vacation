<?php

declare(strict_types=1);

namespace App\Vacation\Rule;

use App\Employee\Employee;
use App\Vacation\VacationRule;
use App\Vacation\Year;

/**
 * Employees aged 30 or older get one extra day for every 5 years of employment.
 * Age and years of employment are taken on the last day of the year.
 */
final readonly class SeniorityBonus implements VacationRule
{
    private const int MINIMUM_AGE = 30;
    private const int YEARS_PER_EXTRA_DAY = 5;

    public function apply(int $days, Employee $employee, Year $year): int
    {
        $lastDay = $year->lastDay();

        if ($employee->ageOn($lastDay) < self::MINIMUM_AGE) {
            return $days;
        }

        return $days + intdiv($employee->yearsEmployedOn($lastDay), self::YEARS_PER_EXTRA_DAY);
    }
}
