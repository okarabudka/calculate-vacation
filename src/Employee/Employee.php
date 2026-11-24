<?php

declare(strict_types=1);

namespace App\Employee;

use DateTimeImmutable;

final readonly class Employee
{
    public function __construct(
        public string $name,
        public DateTimeImmutable $birthDate,
        public DateTimeImmutable $contractStart,
        public ?int $minimumVacationDays = null,
    ) {}

    public function ageOn(DateTimeImmutable $date): int
    {
        return $this->birthDate->diff($date)->y;
    }

    public function yearsEmployedOn(DateTimeImmutable $date): int
    {
        return $this->contractStart->diff($date)->y;
    }
}
