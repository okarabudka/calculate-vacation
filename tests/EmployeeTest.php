<?php

declare(strict_types=1);

namespace Tests;

use App\Employee\Employee;
use App\Services\EmployeeService;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EmployeeTest extends TestCase
{
    #[DataProvider('vacationDataProvider')]
    public function testVacationCalculation(
        string $name,
        string $birthDate,
        string $contractStart,
        ?int $specialDays,
        int $expected
    ): void {
        $employee = new Employee(
            $name,
            new DateTimeImmutable($birthDate),
            new DateTimeImmutable($contractStart),
            $specialDays
        );

        $actual = EmployeeService::calculateVacation($employee, 2024);
        $this->assertEquals($expected, $actual);
    }

    public static function vacationDataProvider(): array
    {
        return [
            // name, birthDate, contractStart, specialDays, expected
            'minimum with bonus' => [
                'Test Employee', '1990-01-01', '2010-01-01', null, 28
            ],
            'special contract full year' => [
                'Special Contract', '1990-01-01', '2020-01-01', 32, 32
            ],
            'partial from 15th March counts April to December' => [
                'Partial', '1990-01-01', '2024-03-15', null, 19
            ],
            'starts after the year' => [
                'Future Employee', '1980-01-01', '2025-01-01', null, 0
            ],
            'older with 14 years' => [
                'Older', '1980-01-01', '2010-01-01', null, 28
            ],
        ];
    }
}
