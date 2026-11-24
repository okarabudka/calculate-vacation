<?php

declare(strict_types=1);

namespace App\Vacation;

use App\Employee\Employee;

/**
 * One rule of the vacation policy: takes the days so far and returns the adjusted days.
 */
interface VacationRule
{
    public function apply(int $days, Employee $employee, Year $year): int;
}
