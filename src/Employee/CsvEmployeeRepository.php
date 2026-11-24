<?php

declare(strict_types=1);

namespace App\Employee;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Reads employees from a CSV file with the columns of data/employees-list.csv.
 * Dates use the format dd.mm.yyyy; the optional fourth column reads "<n> minimum vacation days".
 */
final readonly class CsvEmployeeRepository implements EmployeeRepository
{
    private const array HEADER = ['Name', 'Date of Birth', 'Contract Start Date', 'Special Contract'];
    private const string DATE_FORMAT = 'd.m.Y';
    private const string SPECIAL_CONTRACT_PATTERN = '/^(\d+) minimum vacation days$/';
    private const array CONTRACT_START_DAYS = [1, 15];
    private const string UTF8_BOM = "\u{FEFF}";

    public function __construct(private string $path) {}

    public function findAll(): array
    {
        $handle = is_file($this->path) && is_readable($this->path) ? fopen($this->path, 'r') : false;
        if ($handle === false) {
            throw InvalidEmployeeData::unreadableFile($this->path);
        }

        try {
            return $this->read($handle);
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param resource $handle
     *
     * @return list<Employee>
     */
    private function read($handle): array
    {
        $employees = [];
        $line = 0;

        while (($row = fgetcsv($handle, escape: '')) !== false) {
            ++$line;
            $row = array_map(static fn(?string $value): string => trim((string) $value), $row);

            if ($line === 1) {
                $row[0] = str_replace(self::UTF8_BOM, '', $row[0]);
                if ($row !== self::HEADER) {
                    throw $this->missingHeader();
                }
                continue;
            }

            if ($row === ['']) {
                continue;
            }

            $employees[] = $this->employee($row, $line);
        }

        if ($line === 0) {
            throw $this->missingHeader();
        }

        return $employees;
    }

    /**
     * @param array<int, string> $row
     */
    private function employee(array $row, int $line): Employee
    {
        if (count($row) < 3 || count($row) > 4) {
            throw InvalidEmployeeData::inLine($this->path, $line, sprintf('expected 3 or 4 columns, got %d.', count($row)));
        }

        [$name, $birthDate, $contractStart] = $row;
        $specialContract = $row[3] ?? '';

        if ($name === '') {
            throw InvalidEmployeeData::inLine($this->path, $line, 'the name is empty.');
        }

        $contractStartDate = $this->date($contractStart, 'contract start date', $line);
        if (!in_array((int) $contractStartDate->format('j'), self::CONTRACT_START_DAYS, true)) {
            throw InvalidEmployeeData::inLine($this->path, $line, sprintf('a contract starts on the 1st or the 15th of a month, got "%s".', $contractStart));
        }

        return new Employee(
            $name,
            $this->date($birthDate, 'date of birth', $line),
            $contractStartDate,
            $this->minimumVacationDays($specialContract, $line),
        );
    }

    private function missingHeader(): InvalidEmployeeData
    {
        return InvalidEmployeeData::inLine($this->path, 1, sprintf('expected the header "%s".', implode(',', self::HEADER)));
    }

    private function date(string $value, string $column, int $line): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!' . self::DATE_FORMAT, $value, new DateTimeZone('UTC'));

        // Round-tripping rejects dates PHP would silently roll over, such as 31.02.
        if ($date === false || $date->format(self::DATE_FORMAT) !== $value) {
            throw InvalidEmployeeData::inLine($this->path, $line, sprintf('invalid %s "%s", expected dd.mm.yyyy.', $column, $value));
        }

        return $date;
    }

    private function minimumVacationDays(string $specialContract, int $line): ?int
    {
        if ($specialContract === '') {
            return null;
        }

        if (preg_match(self::SPECIAL_CONTRACT_PATTERN, $specialContract, $matches) !== 1) {
            throw InvalidEmployeeData::inLine($this->path, $line, sprintf('unknown special contract "%s", expected "<n> minimum vacation days".', $specialContract));
        }

        return (int) $matches[1];
    }
}
