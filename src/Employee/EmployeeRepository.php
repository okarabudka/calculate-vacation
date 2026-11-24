<?php

declare(strict_types=1);

namespace App\Employee;

interface EmployeeRepository
{
    /**
     * @return list<Employee>
     *
     * @throws InvalidEmployeeData
     */
    public function findAll(): array;
}
