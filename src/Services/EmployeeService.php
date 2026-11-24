<?php

declare(strict_types=1);

namespace App\Services;

use App\Employee\Employee;
use App\Repositories\EmployeeRepository;
use DateTimeImmutable;

final class EmployeeService
{
    public function __construct(private readonly EmployeeRepository $employeeRepository)
    {

    }

    public function getEmployees(int $year): array
    {
        $results = [];

        /** @var Employee $employee */
        foreach ($this->employeeRepository->findAll() as $employee) {
            $results[] = [
                'name' => $employee->name,
                'days' => $this->calculateVacation($employee, $year),
            ];
        }

        return $results;
    }

    public static function calculateVacation(Employee $employee, int $year): int
    {
        if ((int) $employee->contractStart->format('Y') > $year) {
            return 0;
        }

        $baseDays = $employee->minimumVacationDays ?? 26;
        $endOfYear = new DateTimeImmutable("$year-12-31");

        if ($employee->ageOn($endOfYear) >= 30) {
            $baseDays += intdiv($employee->yearsEmployedOn($endOfYear), 5);
        }

        $startMonth = 1;
        if ((int) $employee->contractStart->format('Y') === $year) {
            $month = (int) $employee->contractStart->format('n');
            $day = (int) $employee->contractStart->format('j');
            // Only full months count: a contract starting after the 1st begins with the next month.
            $startMonth = $day === 1 ? $month : $month + 1;
        }
        $monthsWorked = 12 - $startMonth + 1;

        return (int) floor(($baseDays / 12) * $monthsWorked);
    }
}
