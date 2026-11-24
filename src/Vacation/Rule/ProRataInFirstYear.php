<?php

declare(strict_types=1);

namespace App\Vacation\Rule;

use App\Employee\Employee;
use App\Vacation\VacationRule;
use App\Vacation\Year;

/**
 * In the year the contract starts, the employee gets 1/12 of the days for each full month worked,
 * rounded down. A contract starting after the 1st of a month counts from the next month.
 */
final readonly class ProRataInFirstYear implements VacationRule
{
    private const int MONTHS_PER_YEAR = 12;

    public function apply(int $days, Employee $employee, Year $year): int
    {
        $start = $employee->contractStart;

        if (!$year->contains($start)) {
            return $days;
        }

        $firstFullMonth = (int) $start->format('n') + ((int) $start->format('j') === 1 ? 0 : 1);
        $fullMonths = self::MONTHS_PER_YEAR - $firstFullMonth + 1;

        return intdiv($days * $fullMonths, self::MONTHS_PER_YEAR);
    }
}
